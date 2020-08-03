<?php

namespace App\Listeners;

use App\Events\PassengerCanceledTrip;
use App\Models\TripMember;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendNotificationToDriverAboutPassengerRemoval implements ShouldQueue
{
    use InteractsWithQueue;

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
     * @param  PassengerCanceledTrip  $event
     * @return void
     */
    public function handle(PassengerCanceledTrip $event)
    {
        $ride        = $event->ride;
        $driver      = $event->driver;
        $passengerId = $event->passengerId;

        $ride->load('trip');

        $driver->createNotification(TripMember::TYPE_DRIVER, 'The passenger has left this ride.', [
            'message' => 'The passenger has left this ride.',
            'type'    => 'passenger_left',
        ])->customPayload([
            'click_action' => 'passenger_left',
            'trip_id'      => $ride->id,
        ])->throwNotificationsVia('push')->build();
    }
}
