<?php

namespace App\Listeners\Api;

use App\Classes\FireStoreHandler;
use App\Events\Api\JWTUserUpdate;

class JWTUserUpdateSyncWithFirestore
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
     * @param  JWTUserUpdate  $event
     * @return void
     */
    public function handle(JWTUserUpdate $event)
    {
        $user = $event->user;

        FireStoreHandler::updateDocument('users', $user->id, [
            'user_id' => $user->id,
            'user_type' => $user->user_role_key,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'is_active' => true,
            'profile_picture' => $user->profile_picture_auto,
            'push_token' => data_get($user->device(), 'device_token', ''),
        ]);
    }
}
