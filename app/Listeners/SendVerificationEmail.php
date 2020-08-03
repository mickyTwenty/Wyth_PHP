<?php

namespace App\Listeners;

use App\Classes\Email;
use App\Events\Api\JWTUserRegistration;
use App\Notifications\Api\Registration;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendVerificationEmail implements ShouldQueue
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
     * @param  JWTUserRegistration  $event
     * @return void
     */
    public function handle(JWTUserRegistration $event)
    {
        $user = $event->user;

        try {
            $user->notify( new Registration($user) );
        } catch (\Exception $e) {}
    }
}
