<?php

namespace App\Listeners;

use App\Classes\FirebaseHandler;
use App\Events\NewUnfollowingEvent;

class NewUnfollowingListener
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
     * @param  NewUnfollowing  $event
     * @return void
     */
    public function handle(NewUnfollowingEvent $event)
    {
        $unfollower  = $event->unfollower;
        $unfollowing = $event->unfollowing;

        FirebaseHandler::delete('/friends/'.$unfollower->prefix_uid.'/'.$unfollowing->prefix_uid);
        FirebaseHandler::delete('/friends/'.$unfollowing->prefix_uid.'/'.$unfollower->prefix_uid);
    }
}
