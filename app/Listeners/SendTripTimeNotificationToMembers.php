<?php

namespace App\Listeners;

use App\Events\TripPickupTimeUpdated;
use App\Models\TripMember;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

//use Illuminate\Queue\InteractsWithQueue;
//use Illuminate\Contracts\Queue\ShouldQueue;

class SendTripTimeNotificationToMembers implements ShouldQueue
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
        $members  = $tripRide->members()->confirmed()->get();

        if (count($members)) {
            foreach ($members as $member) {
                User::find($member->user_id)->createNotification(TripMember::TYPE_PASSENGER, 'Trip departure time updated.', [
                    'message' => 'Departure time updated for your trip.',
                    'type'    => 'pickup_time_updated',
                ])->customPayload([
                    'click_action'     => 'pickup_time_updated',
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
