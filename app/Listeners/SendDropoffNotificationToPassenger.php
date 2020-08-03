<?php

namespace App\Listeners;

use App\Events\PassengerDropoffMarked;
use App\Models\TripMember;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

//use Illuminate\Queue\InteractsWithQueue;
//use Illuminate\Contracts\Queue\ShouldQueue;

class SendDropoffNotificationToPassenger implements ShouldQueue
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
     * @param  PassengerDropoffMarked  $event
     * @return void
     */
    public function handle(PassengerDropoffMarked $event)
    {
        $tripRide = $event->tripRide;
        $members  = $event->members;

        if (count($members)) {
            foreach ($members as $member) {
                User::find($member->user_id)->createNotification(TripMember::TYPE_PASSENGER, 'Your trip has been completed.', [
                    'message' => 'Your trip has been completed.',
                    'type'    => 'marked_dropoff',
                ])->customPayload([
                    'click_action'     => 'marked_dropoff',
                    'trip_id'          => $tripRide->id,
                    'driver_id'        => $tripRide->trip->driver->id,
                    'driver_name'      => $tripRide->trip->driver->full_name,
                    'passenger_id'     => $member->user_id,
                    'trip_name'        => $tripRide->trip->trip_name,
                    'origin_text'      => $tripRide->origin_title,
                    'destination_text' => $tripRide->destination_title,
                    'date'             => $tripRide->expected_start_date,
                ])->throwNotificationsVia('push')->build();
            }
        }
    }
}
