<?php

namespace App\Listeners;

use App\Events\RideCanceledByDriver;
use App\Models\TripMember;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyPassengerRideHasBeenCanceled implements ShouldQueue
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
     * @param  RideCanceledByDriver  $event
     * @return void
     */
    public function handle(RideCanceledByDriver $event)
    {
        $tripRide = $event->ride;
        $members  = $event->members;

        $mixedPassengers = array_unique($members);
        info('Passenger post Debuging:', $mixedPassengers);

        foreach ($mixedPassengers as $passenger) {
            User::find($passenger)->createNotification(TripMember::TYPE_PASSENGER, 'Trip has been cancelled.', [
                'message' => 'A trip you were part of has been cancelled.',
                'type' => 'trip_canceled',
            ])->notActionable()
            ->customPayload([
                'click_action' => 'trip_canceled',
                'trip_id'      => $tripRide->id,
            ])->throwNotificationsVia('push')->build();
        }
    }
}
