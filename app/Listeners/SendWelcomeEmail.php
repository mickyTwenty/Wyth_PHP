<?php

namespace App\Listeners;

use App\Classes\Email;
use App\Events\Api\JWTUserRegistration;

class SendWelcomeEmail
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

        // Send welcome email
        $emailBody = "Hello,

Thank you for signing up on ".constants('global.site.name').", enjoy your stay here.

Thanks.";

        Email::shoot( $user->email, 'Welcome on Board', $emailBody );
    }
}
