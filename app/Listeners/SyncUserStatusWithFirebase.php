<?php

namespace App\Listeners;

use App\Classes\FirebaseHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SyncUserStatusWithFirebase
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
     * @param  $event
     * @return void
     */
    public function handle($event)
    {
        $user = $event->user;

        FirebaseHandler::update('/users/'.$user->prefix_uid, [
            'is_active' => ($user->active == 1 && !($event instanceof \App\Events\UserDeleted))
                ? ((method_exists($user, 'trashed') && $user->trashed()) ? false : true)
                : false,
        ]);
    }
}
