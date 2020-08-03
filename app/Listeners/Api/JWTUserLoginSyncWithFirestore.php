<?php

namespace App\Listeners\Api;

use App\Classes\FireStoreHandler;
use App\Events\Api\JWTUserLogin;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class JWTUserLoginSyncWithFirestore
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
     * @param  JWTUserLogin  $event
     * @return void
     */
    public function handle(JWTUserLogin $event)
    {
        $user = $event->user;

        FireStoreHandler::updateDocument('users', $user->id, [
            'push_token' => data_get($user->device(), 'device_token', ''),
        ]);
    }

    /**
     * Method will trigger once queue failed to complete.
     *
     * @param  JWTUserLogin  $event
     * @return void
     */
    public function failed(JWTUserLogin $event, $exception)
    {
        //
    }
}
