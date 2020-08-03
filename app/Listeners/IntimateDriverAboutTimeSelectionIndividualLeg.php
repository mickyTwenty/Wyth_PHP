<?php

namespace App\Listeners;

use App\Classes\PushNotification;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\TripRide;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

//use Illuminate\Contracts\Queue\ShouldQueue;
//use Illuminate\Queue\InteractsWithQueue;

class IntimateDriverAboutTimeSelectionIndividualLeg implements ShouldQueue
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
        $shouldIntimateDriver = false;

        foreach ($trip->rides as $iterateRide) {

            // Skip if processed
            if ($iterateRide->getMetaObject('log.filled_notification_sent')->exists()) {
                continue;
            }

            if (intval($iterateRide->seats_available) === 0) {
                $passengers = $iterateRide->members;
                $confirmed  = $passengers->filter(function ($row) {
                    return $row->isReadyToFly();
                });

                if ($passengers->count() === $confirmed->count() && intval($iterateRide->seats_available) === 0) {
                    // This will ensure to send one push notification either both or one leg completed.
                    $shouldIntimateDriver = !$shouldIntimateDriver;
                } else {
                    continue;
                }

                $iterateRide->updateRideStatus(TripRide::RIDE_STATUS_FILLED);

                if (true === $shouldIntimateDriver) {
                    // Intimate driver to select the ride time since all passengers confirmed and none seats left
                    User::find($driverId)->createNotification(TripMember::TYPE_DRIVER, 'All passengers confirmed', [
                        'message' => 'Passengers of ' . strval($iterateRide->origin_title) . ' trip have been confirmed.',
                        'type' => 'passengers_confirmed',
                    ])->customPayload([
                        'click_action' => 'passengers_confirmed',
                        'trip_id' => $iterateRide->id,
                    ])->throwNotificationsVia('push')->build();

                    $iterateRide->setMeta([
                        'log.filled_notification_sent' => true
                    ]);
                    $iterateRide->save();
                }
            }
        }
    }
}
