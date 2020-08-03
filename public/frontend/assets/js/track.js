// var marker, map, debug;
(function() {
'use strict';

    //
    // Utilities
    //
    function guid() {
        function s4() {
            return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1)
        }
        return s4() + s4() + '-' + s4() + '-' + s4() + '-' + s4() + '-' + s4() + s4() + s4()
    }

    function now() {
        return Math.floor(Date.now() / 1000)
    }

    function displayElement(element_id) {
        document.getElementById(element_id).style.display = 'block'
    }

    function hideElement(element_id) {
        document.getElementById(element_id).style.display = 'none'
    }

    function initialize() {
        var myLatlng = new google.maps.LatLng(31.100243, -104.5722739)
        var mapOptions = {
            zoom: 4,
            center: myLatlng,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        }
        map = new google.maps.Map(document.getElementById('map_canvas'), mapOptions)

        marker = new SlidingMarker({
            position: myLatlng,
            map: map,
            easing: 'linear',
            draggable: false
        })
    }

    function waitForTripToStart() {
        hideElement('map_canvas')
        hideElement('welcome')
        displayElement('hold_message')
    }

    function setMarkerIcon() {
        if ( !marker.icon ) {
            var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path d="M9.5 10.287c0-.41-.336-.743-.75-.743s-.75.333-.75.743.336.743.75.743.75-.333.75-.743zm4.5.495c0-.137-.112-.248-.25-.248h-3.5c-.138 0-.25.111-.25.248s.112.248.25.248h3.5c.138-.001.25-.112.25-.248zm2-.495c0-.41-.336-.743-.75-.743s-.75.333-.75.743.336.743.75.743.75-.333.75-.743zm-8.649-3.219h-1.101c-.138 0-.25.111-.25.248v.253c0 .393.463.49.808.49l.543-.991zm9.659 1.569c-.435-.8-.866-1.597-1.342-2.382-.393-.649-.685-.96-1.375-1.083-.698-.124-1.341-.172-2.293-.172s-1.595.048-2.292.172c-.69.123-.982.433-1.375 1.083-.477.785-.907 1.582-1.343 2.382-.344.63-.49 1.194-.49 1.884 0 .653.21 1.195.5 1.89v1.094c0 .273.224.495.5.495h.75c.276 0 .5-.222.5-.495v-.495h6.5v.495c0 .273.224.495.5.495h.75c.276 0 .5-.222.5-.495v-1.094c.29-.695.5-1.237.5-1.89 0-.69-.146-1.254-.49-1.884zm-7.821-1.873c.335-.554.426-.569.695-.617.635-.113 1.228-.157 2.116-.157s1.481.044 2.116.156c.269.048.36.064.695.617.204.337.405.687.597 1.03-.728.11-2.01.266-3.408.266-1.524 0-2.759-.166-3.402-.275.19-.34.389-.686.591-1.02zm5.798 5.256h-5.974c-.836 0-1.513-.671-1.513-1.498 0-.813.253-1.199.592-1.821.52.101 1.984.348 3.908.348 1.74 0 3.28-.225 3.917-.333.332.609.583.995.583 1.805 0 .828-.677 1.499-1.513 1.499zm2.763-4.952c.138 0 .25.111.25.248v.253c0 .393-.463.49-.808.49l-.543-.99h1.101zm-5.75-7.068c-5.523 0-10 4.394-10 9.815 0 5.505 4.375 9.268 10 14.185 5.625-4.917 10-8.68 10-14.185 0-5.421-4.478-9.815-10-9.815zm0 18c-4.419 0-8-3.582-8-8s3.581-8 8-8c4.419 0 8 3.582 8 8s-3.581 8-8 8z"/></svg>'
            var icon = {
                url: 'data:image/svg+xml;charset=UTF-8;base64,' + btoa(svg),
                scaledSize: new google.maps.Size(32, 32),
                optimized: false,
                labelOrigin: new google.maps.Point(18, -10)
            }

            marker.setIcon( icon )
            window.message('Marker icon updated!')
        }
    }

    function setMarkerLabel(label) {
        config.ride.label.text = label
        marker.setLabel(config.ride.label)
    }

    function bootstrapNavigation() {
        setMarkerIcon()

        if ( document.getElementById('map_canvas').style.display == 'none' ) {
          hideElement('welcome')
          hideElement('hold_message')
          displayElement('map_canvas')
        }
    }

    function moveMarker(LatLng) {
        map && bootstrapNavigation()

        window.message( 'Move marker called with', LatLng )
        marker.setComplete(function() {
            window.message('Animation completed' )
            map.panTo(LatLng)
        })
        map.setZoom(16)
        marker.setPosition(LatLng)
    }

    function hasDropped(data) {
        return (data.hasOwnProperty('dropped') && data.dropped.hasOwnProperty(config.ride.userId))
    }

    function getDroppedLocation(data) {
        return data.dropped[config.ride.userId]
    }

    var marker, map;
    var rideId = config.ride.id.toString()

    //
    // Initialize
    //
    initialize()

    //
    // Firebase
    //
    firebase.initializeApp({
      apiKey: config.firebase.apiKey,
      authDomain: config.firebase.authDomain,
      projectId: config.firebase.projectId
    })

    // Initialize Cloud Firestore through Firebase
    var db = firebase.firestore()

    var rideListener = db.collection('locations').doc(rideId).onSnapshot(doc => {
        var source, data, lat, lng, LatLng

        try {
            source = doc.metadata.hasPendingWrites ? "local" : "server"
            data   = doc.data()

            window.message( 'Data', source, data )

            if ( !data ) {
              waitForTripToStart()
              return
            }

            if ( hasDropped(data) ) {
                [lat, lng] = getDroppedLocation(data).split(',')
                window.message( 'Passenger dropped @', getDroppedLocation(data) )

                // Update marker's label
                setMarkerLabel('Passenger Dropped!')

                if (config.ride.showDetails == false && window.location.hasOwnProperty('search') && window.location.search.indexOf('details') == -1) {
                  setTimeout(function() {
                    window.location = window.location.protocol + '//' + window.location.host + window.location.pathname + '?details'
                  }, 2000);
                }

                // rideListener()
            } else {
                [lat, lng] = data.coordinates.split(',')
                window.message(lat, lng)

                // Update marker's label
                setMarkerLabel(config.ride.label.text)
            }

            LatLng = new google.maps.LatLng(lat, lng)

            moveMarker(LatLng)
        } catch(err) {
            console.error( err )
        }
    })

})()

window.message = (function () {
    /* This is a proxy for console.log */
    var isProduction = false, // add your regex here
        bypassFlag = null,
        prefix = '[APP]',
        fn = function () { return; };

    if (!window.console || !window.console.log) {
        window.console = { log: fn, debug: fn, info: fn, warn: fn, error: fn };
    }

    return function (/* arguments */) {
        var bypass = (/^#debug$/gi).test(window.location.hash),
            args = Array.prototype.slice.call(arguments) || [];

        bypassFlag = bypassFlag || (args.length && args[0] === 'bypass') || bypass || false;
        args.splice(0, 0, prefix);

        if (!bypassFlag && isProduction) { return; }
        try {
            window.console.log.apply(window.console, args);
        } catch (err) {
            return;
        }
    };
}());
