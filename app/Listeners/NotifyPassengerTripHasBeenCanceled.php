<?php

namespace App\Listeners;

use App\Events\TripCanceledByDriver;
use App\Models\TripMember;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyPassengerTripHasBeenCanceled implements ShouldQueue
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
     * @param  TripCanceledByDriver  $event
     * @return void
     */
    public function handle(TripCanceledByDriver $event)
    {
        $trip    = $event->trip;
        $members = $event->members;

        $mixedPassengers = array_unique($members);
        info('Passenger post Debuging:', $mixedPassengers);

        $tripRide = $trip->getGoingRideOfTrip();
        foreach ($mixedPassengers as $passenger) {
            User::find($passenger)->createNotification(TripMember::TYPE_PASSENGER, 'Trip has been canceled', [
                'message' => 'A trip you were part of has been canceled',
                'type' => 'trip_canceled',
            ])->notActionable()
            ->customPayload([
                'click_action' => 'trip_canceled',
                'trip_id'      => $tripRide->id,
            ])->throwNotificationsVia('push')->build();
        }
    }
}
