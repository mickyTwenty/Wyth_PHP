<?php

namespace App\Listeners;

use App\Events\UserDeleted;
use Exception;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeleteUserData
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

        try {
            $user->facebook()->delete();
            $user->devices()->delete();

        } catch (Exception $e) {}
    }
}
