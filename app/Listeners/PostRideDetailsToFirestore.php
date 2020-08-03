<?php

namespace App\Listeners;

use App\Classes\FireStoreHandler;
use App\Events\TripRideCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PostRideDetailsToFirestore
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
     * @param  TripRideCreated  $event
     * @return void
     */
    public function handle(TripRideCreated $event)
    {
        $ride   = $event->tripRide;

        $driver = $ride->trip->user_id;

        FireStoreHandler::addDocument('groups', $ride->id, [
            'trip_id'      => strval($ride->id),
            'members' => [
                strval($driver)
            ],
        ]);
    }
}
