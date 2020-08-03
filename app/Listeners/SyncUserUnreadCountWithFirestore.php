<?php

namespace App\Listeners;

use App\Events\NewNotificationAdded;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncUserUnreadCountWithFirestore
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
     * @param  NewNotificationAdded  $event
     * @return void
     */
    public function handle(NewNotificationAdded $event)
    {
        $notification = $event->notification;
        $user         = $event->user;
    }
}
