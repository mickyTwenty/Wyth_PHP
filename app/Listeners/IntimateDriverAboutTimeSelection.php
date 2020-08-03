<?php

namespace App\Listeners;

use App\Classes\PushNotification;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\TripRide;
use App\Models\User;

//use Illuminate\Contracts\Queue\ShouldQueue;
//use Illuminate\Queue\InteractsWithQueue;

class IntimateDriverAboutTimeSelection
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
     * @param $event
     * @return void
     */
    public function handle($event)
    {
        $ride      = $event->ride;
        $passenger = $event->passenger;
        $driverId  = $event->driver;

        // Get fresh object of Ride because values modified on object cannot be access here
        $ride = TripRide::with('trip.rides')->find($ride->id);

        $trip                 = Trip::with(['rides.members'])->find($ride->trip_id);
        $shouldIntimateDriver = true;

        // Lets optimize the work-flow, you perhaps won't understand this but trust me its kinda optimized :)
        if (intval($ride->seats_available) === 0) {
            foreach ($trip->rides as $iterateRide) {
                $passengers = $iterateRide->members;
                $confirmed  = $passengers->filter(function ($row) {
                    return $row->isReadyToFly();
                });

                if ($passengers->count() === $confirmed->count() && intval($iterateRide->seats_available) === 0) {
                    // All good, look for round-trip now
                } else {
                    $shouldIntimateDriver = false;
                    break;
                }
            }

            if (true === $shouldIntimateDriver) {
                $ride = $trip->rides->first();
                $ride->updateRideStatus(TripRide::RIDE_STATUS_FILLED);

                // Intimate driver to select the ride time since all passengers confirmed and none seats left
                User::find($driverId)->createNotification(TripMember::TYPE_DRIVER, 'All passengers confirmed', [
                    'message' => 'Passengers of ' . strval($trip->trip_name) . ' trip have been confirmed.',
                    'type' => 'passengers_confirmed',
                ])->customPayload([
                    'click_action' => 'passengers_confirmed',
                    'trip_id' => $ride->id,
                ])->throwNotificationsVia('push')->build();

                /*PushNotification::sendToUserConditionally($driverId, [
                    'content' => [
                        'title'   => 'All passengers confirmed',
                        'message' => 'Passengers of ' . strval($trip->trip_name) . ' trip have been confirmed.',
                        'action'  => 'passengers_confirmed',
                    ],
                    'data'    => [
                        'trip_id' => $ride->id,
                    ],
                ]);*/
            }
        }
    }
}
