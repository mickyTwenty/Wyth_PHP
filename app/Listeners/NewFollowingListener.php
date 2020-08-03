<?php

namespace App\Listeners;

use App\Classes\FirebaseHandler;
use App\Events\NewFollowingEvent;

class NewFollowingListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  NewFollowing  $event
     * @return void
     */
    public function handle(NewFollowingEvent $event)
    {
        $follower  = $event->follower;
        $following = $event->following;

        // After user1 follows user2, check if user2 also following user1 then make them friend on firebase
        if ( $following->isFollowing($follower) ) {

            FirebaseHandler::update('/friends/'.$follower->prefix_uid, [
                $following->prefix_uid => true,
            ]);

            FirebaseHandler::update('/friends/'.$following->prefix_uid, [
                $follower->prefix_uid => true,
            ]);
        }
    }
}
