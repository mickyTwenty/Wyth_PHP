<?php

namespace App\Listeners;

use App\Classes\FireStoreHandler;
use App\Classes\PHPFireStore\FireStoreObject;
use App\Events\PassengerDropoffMarked;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SyncDroppedPassengerWithFirestore
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
     * @param  PassengerDropoffMarked  $event
     * @return void
     */
    public function handle(PassengerDropoffMarked $event)
    {
        $ride        = $event->tripRide;
        $members     = $event->members;
        $coordinates = $event->coordinates;

        try {
            // Get existing dropped passenger with their location so that it can be update accordingly
            $firestore = FireStoreHandler::getDocument('locations', $ride->id);

            // If node doesn't exist create a one.
            $previousData = data_get($firestore->toArray(), 'dropped', []);
        } catch (\Exception $e) {
            $previousData = [];
        }

        foreach ($members as $member) {
            $previousData[$member->user_id] = $coordinates;
        }

        $payload = [
            'dropped' => new FireStoreObject($previousData),
        ];

        FireStoreHandler::updateDocument('locations', $ride->id, $payload);
    }
}
