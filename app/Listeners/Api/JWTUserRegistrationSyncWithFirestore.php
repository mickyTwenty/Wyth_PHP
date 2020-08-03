<?php

namespace App\Listeners\Api;

use App\Classes\FireStoreHandler;

class JWTUserRegistrationSyncWithFirestore
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
        $attributes = $event->attributes;

        // Dont sync with firestore if attributes found.
        if (array_key_exists('syncFirestore', $attributes) && false === $attributes['syncFirestore']) {
            return;
        }

        FireStoreHandler::addDocument('users', $user->id, [
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
