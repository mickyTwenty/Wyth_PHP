<?php

namespace App\Listeners;

use App\Events\TripDeleted;
use App\Models\TripMember;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyPassengerTripHasBeenDeleted implements ShouldQueue
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
     * @param  TripDeleted  $event
     * @return void
     */
    public function handle(TripDeleted $event)
    {
        $trip = $event->trip;

        $requestedMembers = [];
        foreach ($trip->rides as $ride) {
            $requestedMembers = array_merge($requestedMembers, $ride->requestedMembers->pluck('user_id')->toArray());
        }

        if ( $trip->isRequest() ) {
            // Remove initiator id from passenger list
            $requestedMembers = array_diff($requestedMembers, [$trip->initiated_by]);
        }

        $mixedPassengers = array_unique($requestedMembers);

        $tripRide = $trip->getGoingRideOfTrip();
        foreach ($mixedPassengers as $passenger) {
            User::find($passenger)->createNotification(TripMember::TYPE_PASSENGER, 'Trip has been cancelled.', [
                'message' => 'A trip you were part of has been cancelled.',
                'type' => 'trip_canceled',
            ])->notActionable()
            ->customPayload([
                'click_action'     => 'trip_canceled',
                'trip_id'          => $tripRide->id,
            ])->throwNotificationsVia('push')->build();
        }
    }
}
