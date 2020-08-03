'use strict';

const functions = require('firebase-functions');
const admin     = require('firebase-admin');
admin.initializeApp(functions.config().firebase);
const db        = admin.firestore();
const request   = require('request-promise');

// Push Messaging
const pushOptions = {
  // Removed, not required for now as per iOS developers in iOS 11 probably
  // priority:"high",
  // content_available: true
}

exports.proxypush = functions.database.ref('/notifications/{userId}').onWrite(event => {
  const snapshot = event.data;

  // Exit when the data is deleted.
  if (!snapshot.exists()) {
    console.log( 'Node has been deleted.', event.params );
    return;
  }

  // if ( !snapshot.previous.exists() ) { // run only on new insert

    let userId = event.params.userId;
    let userRef = db.collection('users').doc(userId.toString());
    return userRef.get().then(userObject => {
      // For iOS
      let badgeCounter = ((userObject.get('unread_chats') || 0) + (userObject.get('unread_notifications') || 0))
      let notification = snapshot.child('notification').val()
      notification.badge = (badgeCounter + 1).toString()

      // For Android/iOS
      let dataPayload = convertAllToString(snapshot.child('data').val());

      // Assign notification payload to android with 'data_' prefix
      for( var key in notification ) {
        dataPayload[`data_${key}`] = notification[key]
      }

      let payload = {
        data: dataPayload
      }

      // Android Push
      console.log( 'Android Push', `'user_${userId}' in topics && 'android1' in topics`, payload, pushOptions );

      var notificationPromises = []

      // payload.notification = {}
      notificationPromises.push(
        admin.messaging().sendToCondition(`'user_${userId}' in topics && 'android1' in topics`, payload, pushOptions)
      )

      // iOS Push
      let ios_payload = JSON.parse(JSON.stringify(payload)) // copy object
      Object.assign(ios_payload, {notification: notification})

      console.log( 'iOS Push', `'user_${userId}' in topics && 'ios1' in topics`, ios_payload );

      notificationPromises.push(
        admin.messaging().sendToCondition(`'user_${userId}' in topics && 'ios1' in topics`, ios_payload, pushOptions)
      )

      // Increase counter
      notificationPromises.push(
        userRef.update({
          unread_notifications: ((userObject.get('unread_notifications') || 0) + 1)
        })
      )

      return Promise.all( notificationPromises )
    })

  // } else {
  //   console.log( 'Notification node updated.' );
  // }

  return true;
});

exports.tripOffer = functions.firestore.document('groups/{tripId}/{offerGroupNode}/{offerId}').onCreate(event => {
  var newValue = event.data.data();
  console.log( 'Current node data', newValue );

  if (typeof event.data.get('delete') !== 'undefined') {
    console.log( 'Deleting', `groups/${event.params.tripId}/${event.params.offerGroupNode}` );
    return deleteCollection(db, `groups/${event.params.tripId}/${event.params.offerGroupNode}`, 100)
    return;
  }

  let userId = event.data.get('sender') == 'passenger' ? event.data.get('driver_id') : event.data.get('passenger_id')
  let senderName = event.data.get('sender').toLowerCase().capitalize()
  // try {
  //   senderName = event.data.get('first_name').toLowerCase().capitalize() + ' ' + event.data.get('last_name').toLowerCase().capitalize()
  // } catch (err) {}

  let userRef = db.collection('users').doc(userId.toString());
  return userRef.get().then(userObject => {

    // Customize push case basis
    let customObject = {
      trip_id: event.data.get('trip_id').toString(),
      driver_id: event.data.get('driver_id').toString(),
      passenger_id: event.data.get('passenger_id').toString(),
      sender: event.data.get('sender')
    }

    // For iOS
    let badgeCounter = ((userObject.get('unread_chats') || 0) + (userObject.get('unread_notifications') || 0))
    let notification = {
      title: `New offer received`,
      body: `${senderName} has sent you a new offer`,
      click_action: 'trip_offer',
      sound: 'default',
      badge: (badgeCounter + 1).toString()
    }

    // For Android/iOS
    let dataPayload = Object.assign({
      data_title: notification.title,
      data_message: notification.body,
      data_click_action: notification.click_action
    }, customObject);

    let payload = {
      data: dataPayload
    }

    // Android Push
    console.log( 'Android Push', `'user_${userId}' in topics && 'android1' in topics`, payload, pushOptions );

    var notificationPromises = []

    // payload.notification = {}
    notificationPromises.push(
      admin.messaging().sendToCondition(`'user_${userId}' in topics && 'android1' in topics`, payload, pushOptions)
    )

    // iOS Push
    let ios_payload = JSON.parse(JSON.stringify(payload)) // copy object
    Object.assign(ios_payload, {notification: notification})

    console.log( 'iOS Push', `'user_${userId}' in topics && 'ios1' in topics`, ios_payload );

    notificationPromises.push(
      admin.messaging().sendToCondition(`'user_${userId}' in topics && 'ios1' in topics`, ios_payload, pushOptions)
    )

    // Increase counter
    notificationPromises.push(
      userRef.update({
        unread_notifications: ((userObject.get('unread_notifications') || 0) + 1)
      })
    )

    // Post notification to server
    notificationPromises.push(
      postNotificationToServer( userId, (event.data.get('sender') == 'passenger' ? 'driver' : 'passenger'), JSON.stringify(ios_payload) )
    )

    return Promise.all( notificationPromises )

  })

})

exports.chatMessage = functions.firestore.document('groups/{tripId}/chat/{messageId}').onCreate(event => {
  var newValue = event.data.data();
  console.log( 'Current node data', newValue );

  return event.data._ref.parent.parent.get().then(tripData => {
    console.log( 'parentData', tripData.data() );

    if ( typeof tripData.get('members') !== 'object' ) {
      console.log( 'Execution ended with other than object' );
      return;
    }

    let senderName = 'Your co-passenger'
    try {
      senderName = event.data.get('first_name').toLowerCase().capitalize() + ' ' + event.data.get('last_name').toLowerCase().capitalize()
    } catch (err) {}

    // assign and remove self user from list
    let passengersList = tripData.get('members').filter(function (item) {
      return item.toString() != event.data.get('user_id').toString()
    })

    if ( passengersList.length == 0 ) {
      console.log( 'No passengers found to send message to' );
      return
    }

    // Customize push case basis
    let chatObject = (typeof tripData.data()) == 'object' ? tripData.data() : {}
    delete chatObject['members']

    // Convert all values to string
    Object.keys(chatObject).map(function(key, index) {
       chatObject[key] = chatObject[key].toString();
    });

    let notificationPromises = []

    for( var userId of passengersList ) {

      let userRef = db.collection('users').doc(userId.toString());
      userRef.get().then(userObject => {

        // For iOS
        let badgeCounter = ((userObject.get('unread_chats') || 0) + (userObject.get('unread_notifications') || 0))
        let notification = {
          title: `${senderName.trim()} has sent you a message`,
          body: event.data.get('message_text'),
          click_action: 'chat_message',
          sound: 'default',
          badge: (badgeCounter + 1).toString()
        }

        // For Android/iOS
        let dataPayload = Object.assign({
          data_title: notification.title,
          data_message: notification.body,
          data_click_action: notification.click_action
        }, chatObject);

        let payload = {
          data: dataPayload
        }

        // Android Push
        console.log( 'Android Push', `'user_${userObject.get('user_id').toString()}' in topics && 'android1' in topics`, payload, pushOptions );

        // payload.notification = {}
        notificationPromises.push(
          admin.messaging().sendToCondition(`'user_${userObject.get('user_id').toString()}' in topics && 'android1' in topics`, payload, pushOptions)
        )

        // iOS Push
        let ios_payload = JSON.parse(JSON.stringify(payload)) // copy object
        Object.assign(ios_payload, {notification: notification})

        console.log( 'iOS Push', `'user_${userObject.get('user_id').toString()}' in topics && 'ios1' in topics`, ios_payload );

        notificationPromises.push(
          admin.messaging().sendToCondition(`'user_${userObject.get('user_id').toString()}' in topics && 'ios1' in topics`, ios_payload, pushOptions)
        )

        // Increase counter
        notificationPromises.push(
          userRef.update({
            unread_chats: ((userObject.get('unread_chats') || 0) + 1)
          })
        )

        // return Promise.all( notificationPromises )
      }) // end userObject
    } // end passengerList

    return Promise.all( notificationPromises )
  })
})

exports.tripInvitation = functions.firestore.document('users/{userId}/invited_members/{friendId}').onCreate(event => {

    var newValue = event.data.data();
    console.log( 'Current node data', newValue );

    // access a particular field as you would any JS property
    var ref = newValue.ref;
    return ref.get().then(friend => {
      console.log( 'Friend data', friend.data() );

      return event.data._ref.parent.parent.get().then(user => {
        console.log( 'User', user.data() );

        // Customize push case basis
        let tripObject = (typeof user.get('trip_search_data')) == 'object' ? user.get('trip_search_data') : {}

        // For iOS
        let badgeCounter = ((friend.get('unread_chats') || 0) + (friend.get('unread_notifications') || 0))
        let notification = {
          title: `Trip Invitation`,
          body: `${typeof user.get('full_name') == 'undefined' ? 'Your friend' : user.get('full_name')} has invited you to a trip`,
          click_action: 'trip_invitation',
          sound: 'default',
          badge: (badgeCounter + 1).toString()
        }

        // For Android/iOS
        let dataPayload = Object.assign({
          data_title: notification.title,
          data_message: notification.body,
          data_click_action: notification.click_action
        }, tripObject);

        let payload = {
          data: dataPayload
        }

        payload.data.date       = new Date(payload.data.date.toString()).getTime().toString()
        payload.data.time_range = payload.data.time_range.toString()

        console.log( 'Android Push', `'user_${friend.get('user_id')}' in topics && 'android1' in topics`, payload, pushOptions );

        // return admin.messaging().sendToDevice(friend.get('push_token'), payload, pushOptions);

        var notificationPromises = []

        // Android Push
        // payload.notification = {}
        notificationPromises.push(
          admin.messaging().sendToCondition(`'user_${friend.get('user_id')}' in topics && 'android1' in topics`, payload, pushOptions)
        )

        // iOS Push
        let ios_payload = JSON.parse(JSON.stringify(payload)) // copy object
        Object.assign(ios_payload, {notification: notification})

        console.log( 'iOS Push', `'user_${friend.get('user_id')}' in topics && 'ios1' in topics`, ios_payload );

        notificationPromises.push(
          admin.messaging().sendToCondition(`'user_${friend.get('user_id')}' in topics && 'ios1' in topics`, ios_payload, pushOptions)
        )

        // Increase counter
        notificationPromises.push(
          ref.update({
            unread_notifications: ((friend.get('unread_notifications') || 0) + 1)
          })
        )

        // Post notification to server
        notificationPromises.push(
          postNotificationToServer( friend.get('user_id'), 'passenger', JSON.stringify(ios_payload) )
        )

        return Promise.all( notificationPromises )
      })
    })

    console.log( 'Params', event.params );
})

exports.tripInvitationResponded = functions.firestore.document('users/{userId}/invited_members/{friendId}').onUpdate(event => {

    var newValue = event.data.data();
    const previousData = event.data.previous.data();
    console.log( 'Current node data', newValue );

    if (
      newValue.hasOwnProperty('status')
      && false === previousData.hasOwnProperty('status')
    ) {
      // access a particular field as you would any JS property
      var friendRef = newValue.ref;
      return friendRef.get().then(friend => {
        console.log( 'Friend data', friend.data() );

        return event.data._ref.parent.parent.get().then(user => {
          console.log( 'User', user.data() );

          let senderName = 'Your friend'
          try {
            senderName = friend.get('first_name').toLowerCase().capitalize() + ' ' + friend.get('last_name').toLowerCase().capitalize()
          } catch (err) {}

          // For iOS
          let badgeCounter = ((user.get('unread_chats') || 0) + (user.get('unread_notifications') || 0))
          let notification = {
            title: `Trip Invitation Responded`,
            body: `${senderName.trim()} has ${newValue.status.toString() == '1' ? 'accepted' : 'rejected' } your invitation.`,
            click_action: 'trip_invitation_responded',
            sound: 'default',
            badge: (badgeCounter + 1).toString()
          }

          // For Android/iOS
          let dataPayload = Object.assign({
            data_title: notification.title,
            data_message: notification.body,
            data_click_action: notification.click_action
          });

          let payload = {
            data: dataPayload
          }

          console.log( 'Android Push', `'user_${user.get('user_id')}' in topics && 'android1' in topics`, payload, pushOptions );

          var notificationPromises = []

          // Android Push
          notificationPromises.push(
            admin.messaging().sendToCondition(`'user_${user.get('user_id')}' in topics && 'android1' in topics`, payload, pushOptions)
          )

          // iOS Push
          let ios_payload = JSON.parse(JSON.stringify(payload)) // copy object
          Object.assign(ios_payload, {notification: notification})

          console.log( 'iOS Push', `'user_${user.get('user_id')}' in topics && 'ios1' in topics`, ios_payload );

          notificationPromises.push(
            admin.messaging().sendToCondition(`'user_${user.get('user_id')}' in topics && 'ios1' in topics`, ios_payload, pushOptions)
          )

          // Increase counter
          notificationPromises.push(
            event.data._ref.parent.parent.update({
              unread_notifications: ((user.get('unread_notifications') || 0) + 1)
            })
          )

          // Post notification to server
          notificationPromises.push(
            postNotificationToServer( user.get('user_id'), (user.get('invite_type') == 'driver_create' ? 'driver' : 'passenger'), JSON.stringify(ios_payload) )
          )

          return Promise.all( notificationPromises )
        })
      })
    }

    console.log( 'Params', event.params );
})

exports.driverTripInvitation = functions.firestore.document('users/{userId}').onUpdate(event => {

    // Retrieve the current and previous value
    const newValue = event.data.data();
    const previousData = event.data.previous.data();
    console.log( 'Current node data', newValue );

    if ( newValue.hasOwnProperty('invited_driver') && false === previousData.hasOwnProperty('invited_driver') ) {
      console.log( 'new invited_driver found in', event.params.userId );

      let userRef = db.collection('users').doc(newValue.invited_driver.user_id.toString());
      return userRef.get().then(friend => {
        console.log( 'Driver data', friend.data() );

        const user = event.data
        console.log( 'User', user.data() );

        // Customize push case basis
        let tripObject = (typeof user.get('trip_search_data')) == 'object' ? user.get('trip_search_data') : {}

        let senderName = 'Your friend'
        try {
          senderName = user.get('first_name').toLowerCase().capitalize() + ' ' + user.get('last_name').toLowerCase().capitalize()
        } catch (err) {}

        // For iOS
        let badgeCounter = ((friend.get('unread_chats') || 0) + (friend.get('unread_notifications') || 0))
        let notification = {
          title: `Trip Invitation`,
          body: `${senderName.trim()} has invited you to a trip as a driver`,
          click_action: 'driver_invitation',
          sound: 'default',
          badge: (badgeCounter + 1).toString()
        }

        // For Android/iOS
        let dataPayload = Object.assign({
          data_title: notification.title,
          data_message: notification.body,
          data_click_action: notification.click_action
        }, tripObject);

        let payload = {
          data: dataPayload
        }

        payload.data.date       = new Date(payload.data.date.toString()).getTime().toString()
        payload.data.time_range = payload.data.time_range.toString()

        console.log( 'Android Push', `'user_${friend.get('user_id')}' in topics && 'android1' in topics`, payload, pushOptions );

        // return admin.messaging().sendToDevice(friend.get('push_token'), payload, pushOptions);

        var notificationPromises = []

        // Android Push
        // payload.notification = {}
        notificationPromises.push(
          admin.messaging().sendToCondition(`'user_${friend.get('user_id')}' in topics && 'android1' in topics`, payload, pushOptions)
        )

        // iOS Push
        let ios_payload = JSON.parse(JSON.stringify(payload)) // copy object
        Object.assign(ios_payload, {notification: notification})

        console.log( 'iOS Push', `'user_${friend.get('user_id')}' in topics && 'ios1' in topics`, ios_payload );

        notificationPromises.push(
          admin.messaging().sendToCondition(`'user_${friend.get('user_id')}' in topics && 'ios1' in topics`, ios_payload, pushOptions)
        )

        // Increase counter
        notificationPromises.push(
          userRef.update({
            unread_notifications: ((friend.get('unread_notifications') || 0) + 1)
          })
        )

        // Post notification to server
        notificationPromises.push(
          postNotificationToServer( friend.get('user_id'), 'driver', JSON.stringify(ios_payload) )
        )

        return Promise.all( notificationPromises )
      })

    } else {
      return false
    }

    console.log( 'Params', event.params );
})

exports.driverTripInvitationResponded = functions.firestore.document('users/{userId}').onUpdate(event => {

    // Retrieve the current and previous value
    const newValue = event.data.data();
    const previousData = event.data.previous.data();
    console.log( 'Current node data', newValue );

    if ( newValue.hasOwnProperty('invited_driver')
      && previousData.hasOwnProperty('invited_driver')
      && newValue.invited_driver.hasOwnProperty('status')
      && false === previousData.invited_driver.hasOwnProperty('status')
    ) {
      console.log( 'driver responded for invitation', newValue.invited_driver.user_id );

      let userRef = db.collection('users').doc(newValue.invited_driver.user_id.toString());
      return userRef.get().then(friend => {
        console.log( 'Driver data', friend.data() );

        const user = event.data
        console.log( 'User', user.data() );

        let senderName = 'Your friend'
        try {
          senderName = friend.get('first_name').toLowerCase().capitalize() + ' ' + friend.get('last_name').toLowerCase().capitalize()
        } catch (err) {}

        // For iOS
        let badgeCounter = ((user.get('unread_chats') || 0) + (user.get('unread_notifications') || 0))
        let notification = {
          title: `Driver Invitation Responded`,
          body: `${senderName.trim()} has ${newValue.invited_driver.status.toString() == '1' ? 'accepted' : 'rejected' } your invitation.`,
          click_action: 'driver_invitation_responded',
          sound: 'default',
          badge: (badgeCounter + 1).toString()
        }

        // For Android/iOS
        let dataPayload = Object.assign({
          data_title: notification.title,
          data_message: notification.body,
          data_click_action: notification.click_action
        });

        let payload = {
          data: dataPayload
        }

        console.log( 'Android Push', `'user_${user.get('user_id')}' in topics && 'android1' in topics`, payload, pushOptions );

        var notificationPromises = []

        // Android Push
        notificationPromises.push(
          admin.messaging().sendToCondition(`'user_${user.get('user_id')}' in topics && 'android1' in topics`, payload, pushOptions)
        )

        // iOS Push
        let ios_payload = JSON.parse(JSON.stringify(payload)) // copy object
        Object.assign(ios_payload, {notification: notification})

        console.log( 'iOS Push', `'user_${user.get('user_id')}' in topics && 'ios1' in topics`, ios_payload );

        notificationPromises.push(
          admin.messaging().sendToCondition(`'user_${user.get('user_id')}' in topics && 'ios1' in topics`, ios_payload, pushOptions)
        )

        // Increase counter
        notificationPromises.push(
          event.data._ref.update({
            unread_notifications: ((user.get('unread_notifications') || 0) + 1)
          })
        )

        // Post notification to server
        notificationPromises.push(
          postNotificationToServer( user.get('user_id'), 'passenger', JSON.stringify(ios_payload) )
        )

        return Promise.all( notificationPromises )
      })

    } else {
      return false
    }

    console.log( 'Params', event.params );
})

function updateUserCounter(userId, key) {
  return db.runTransaction(t => {
      var userRef = db.collection('users').doc(userId.toString())
      return t.get(userRef).then(doc => {
          var object = {}
          object[key] = (doc.get(key) || 0) + 1
          t.update(userRef, object);
      });
  })
}

function deleteCollection(db, collectionPath, batchSize) {
  var collectionRef = db.collection(collectionPath);
  var query = collectionRef.orderBy('__name__').limit(batchSize);

  return new Promise((resolve, reject) => {
      deleteQueryBatch(db, query, batchSize, resolve, reject);
  });
}

function convertAllToString(array) {
  // Convert all values to string
  Object.keys(array).map(function(key, index) {
     array[key] = array[key].toString();
  });

  return array;
}

function postNotificationToServer(userId, userType, payload) {
  var options = {
    method: 'POST',
    uri: 'http://34.213.248.253/api/v1/firebase/notification',
    headers: {
      'Content-Type': 'application/json; charset=utf-8'
    },
    body: {
      user_id: userId,
      user_type: userType,
      payload: payload
    },
    resolveWithFullResponse: true,
    json: true // Automatically parses the JSON string in the response
  };
  return request(options).then(response => {
    console.log( 'Code:', response.statusCode );
    if (response.statusCode === 200) {
      const data = response.body;
      console.log( 'Response', data );
      if ( data.status == true ) {
        return true;
      }
    }

    return false;
  });
}

function deleteQueryBatch(db, query, batchSize, resolve, reject) {
  query.get()
      .then((snapshot) => {
          // When there are no documents left, we are done
          if (snapshot.size == 0) {
              return 0;
          }

          // Delete documents in a batch
          var batch = db.batch();
          snapshot.docs.forEach((doc) => {
              batch.delete(doc.ref);
          });

          return batch.commit().then(() => {
              return snapshot.size;
          });
      }).then((numDeleted) => {
          if (numDeleted === 0) {
              resolve();
              return;
          }

          // Recurse on the next process tick, to avoid
          // exploding the stack.
          process.nextTick(() => {
              deleteQueryBatch(db, query, batchSize, resolve, reject);
          });
      })
      .catch(reject);
}

String.prototype.capitalize = function() {
    return this.charAt(0).toUpperCase() + this.slice(1);
}
