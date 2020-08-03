<?php

namespace App\Listeners\Api;

use App\Classes\FirebaseHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class JWTUserLogout implements ShouldQueue
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
        $attributes = data_get($event, 'attributes', []);

        // Remove firebase push token
        /*FirebaseHandler::update('/users/'.$user->prefix_uid, [
            'push_token' => '',
        ]);*/

        // Send silent push notification to logout all logged-in devices
        if ( constants('api.config.sendHiddenLogoutPush') ) {
            if (
                data_get($attributes, 'sendLogoutPush', true) &&
                isset($user->device()->device_token)
            ) {
                $user->devices->map(function($record) {
                    fcmNotification( $record->device_token, null, null, [
                        'priority'          => 'high',
                        'content_available' => true,
                        'data'              => [
                            'action' => 'logout',
                            'event'  => 'device_login',
                        ]
                    ]);
                });
            }
        }
    }
}
