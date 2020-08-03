<?php

namespace App\Listeners;

use App\Notifications\CreateAccount;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPasswordEmail implements ShouldQueue
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
        $user       = $event->user;
        $attributes = $event->attributes;

        try {
            $user->notify(new CreateAccount($user, $attributes));
        } catch (\Exception $e) {}
    }
}
