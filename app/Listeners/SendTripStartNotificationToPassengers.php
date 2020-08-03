<?php

namespace App\Listeners;

use App\Events\TripStarted;
use App\Models\TripMember;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

//use Illuminate\Queue\InteractsWithQueue;
//use Illuminate\Contracts\Queue\ShouldQueue;

class SendTripStartNotificationToPassengers implements ShouldQueue
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
     * @param  TripStarted  $event
     * @return void
     */
    public function handle(TripStarted $event)
    {
        $tripRide = $event->tripRide;
        $members  = $event->members;

        if (count($members)) {
            foreach ($members as $member) {
                User::find($member->user_id)->createNotification(TripMember::TYPE_PASSENGER, 'Your driver is on the way.', [
                    'message' => 'Your driver is on the way.',
                    'type'    => 'trip_started_passenger',
                ])->customPayload([
                    'click_action'     => 'trip_started_passenger',
                    'trip_id'          => $tripRide->id,
                    'driver_id'        => $tripRide->trip->driver->id,
                    'passenger_id'     => $member->user_id,
                    'trip_name'        => $tripRide->trip->trip_name,
                    'origin_text'      => $tripRide->origin_title,
                    'destination_text' => $tripRide->destination_title,
                ])->throwNotificationsVia('push')->build();
            }
        }
    }
}
