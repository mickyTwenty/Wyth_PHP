<?php

namespace App\Listeners;

use App\Events\TripPickupTimeUpdated;
use App\Models\TripRide;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateRideStatusUponTimeSelection
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
     * @param  TripPickupTimeUpdated  $event
     * @return void
     */
    public function handle(TripPickupTimeUpdated $event)
    {
        $tripRide = $event->tripRide;

        $rideStatus = TripRide::RIDE_STATUS_CONFIRMED;
        if ( $tripRide->trip->isRoundTrip() && true === $tripRide->isTimeToSwitchTheRide() ) {
            $rideStatus = TripRide::RIDE_STATUS_GOING_CONFIRMED;
        }

        $tripRide->updateRideStatus( $rideStatus );
    }
}
