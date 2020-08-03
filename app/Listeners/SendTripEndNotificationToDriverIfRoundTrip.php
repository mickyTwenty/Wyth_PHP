<?php

namespace App\Listeners;

use App\Events\TripEnded;
use App\Models\TripMember;

//use Illuminate\Queue\InteractsWithQueue;
//use Illuminate\Contracts\Queue\ShouldQueue;

class SendTripEndNotificationToDriverIfRoundTrip
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
     * @param  TripEnded  $event
     * @return void
     */
    public function handle(TripEnded $event)
    {
        $tripRide = $event->tripRide;

        // Send push notification to driver for returning ride if round trip and the ride itself is not the returning one
        if ($tripRide->trip->isRoundTrip()) {
            if ( $tripRide->isReturningRideOfTrip() ) {
                return;
            }

            $driver = $tripRide->trip->driver;

            $driver->createNotification(TripMember::TYPE_DRIVER, 'Please update time for returning ride.', [
                'message' => 'Please update time for returning ride.',
                'type'    => 'update_returning_ride_time',
            ])->customPayload([
                'click_action'     => 'update_returning_ride_time',
                'trip_id'          => $tripRide->id,
                'driver_id'        => $tripRide->trip->driver->id,
                'passenger_id'     => $driver->id,
                'trip_name'        => $tripRide->trip->trip_name,
                'origin_text'      => $tripRide->origin_title,
                'destination_text' => $tripRide->destination_title,
            ])->throwNotificationsVia('push')->build();
        }
    }
}
