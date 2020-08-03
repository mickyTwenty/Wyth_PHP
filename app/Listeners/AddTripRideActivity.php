<?php

namespace App\Listeners;

use App\Events\TripRideCreated;
use App\Models\TripRide;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AddTripRideActivity
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
        $tripRide = $event->tripRide;

        $tripRide->addTripActivity( TripRide::RIDE_STATUS_ACTIVE );
    }
}
