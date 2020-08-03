<?php

namespace App\Listeners\Api;

use App\Classes\FireStoreHandler;
use App\Events\Api\NotificationsListed;

class UpdateNotificationsCounterOnFireStore
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
     * @param  NotificationsListed  $event
     * @return void
     */
    public function handle(NotificationsListed $event)
    {
        $user = $event->user;

        FireStoreHandler::updateDocument('users', $user->id, [
            'unread_notifications' => 0,
        ]);
    }
}
