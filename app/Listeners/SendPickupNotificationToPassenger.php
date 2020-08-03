<?php

namespace App\Listeners;

use App\Events\PassengerPickupMarked;
use App\Models\TripMember;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

//use Illuminate\Queue\InteractsWithQueue;
//use Illuminate\Contracts\Queue\ShouldQueue;

class SendPickupNotificationToPassenger implements ShouldQueue
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
     * @param  PassengerPickupMarked  $event
     * @return void
     */
    public function handle(PassengerPickupMarked $event)
    {
        $tripRide = $event->tripRide;
        $members  = $event->members;

        if (count($members)) {
            foreach ($members as $member) {
                User::find($member->user_id)->createNotification(TripMember::TYPE_PASSENGER, 'Your trip has been started.', [
                    'message' => 'Your trip has been started.',
                    'type'    => 'marked_pickup',
                ])->customPayload([
                    'click_action'     => 'marked_pickup',
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
