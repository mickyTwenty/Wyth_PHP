<?php

namespace App\Listeners\Api;

use App\Events\Api\NotificationsListed;
use App\Models\UserMeta;

class UpdateUserUnreadNotificationsMeta
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
        $user->setMeta(['unread_notifications' => 0], UserMeta::GROUPING_PROFILE);
        $user->setMeta(['unread_notifications' => 0], UserMeta::GROUPING_DRIVER);
        $user->save();
    }
}
