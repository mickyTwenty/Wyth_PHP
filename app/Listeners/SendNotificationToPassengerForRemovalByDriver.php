<?php

namespace App\Listeners;

use App\Classes\PushNotification;
use App\Events\PassengerRemovedFromTrip;
use App\Models\TripMember;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendNotificationToPassengerForRemovalByDriver implements ShouldQueue
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
     * @param  PassengerRemovedFromTrip  $event
     * @return void
     */
    public function handle(PassengerRemovedFromTrip $event)
    {
        $ride        = $event->ride;
        $driver      = $event->driver;
        $passengerId = $event->passengerId;

        $ride->load('trip');

        User::find($passengerId)->createNotification(TripMember::TYPE_PASSENGER, 'You have been removed from a trip', [
            'message' => 'You have been removed from a trip ' . strval($ride->trip->trip_name) . ')',
            'type' => 'passenger_removed',
        ])->customPayload([
            'click_action' => 'passenger_removed',
            'trip_id' => $ride->id,
        ])->throwNotificationsVia('push')->build();

        /*PushNotification::sendToUserConditionally($passengerId, [
            'content' => [
                'title' => 'You have been removed from a trip',
                'message' => 'You have been removed from a trip ' . strval($ride->trip->trip_name),
                'action' => 'passenger_removed',
            ],
            'data' => [
                'trip_id' => $ride->id,
            ],
        ]);*/
    }
}
