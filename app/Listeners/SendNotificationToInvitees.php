<?php

namespace App\Listeners;

use App\Models\TripMember;
use App\Models\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendNotificationToInvitees implements ShouldQueue
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
     * @param  TripCreatedByDriver|TripCreatedByPassenger  $event
     * @return void
     */
    public function handle($event)
    {
        $trip = $event->trip;

        $ride = $trip->getGoingRideOfTrip();

        foreach ($ride->members as $member) {

            // Ignore the author of the ride
            if ($trip->initiated_by == $member->user_id) {
                continue;
            }

            User::find($member->user_id)->createNotification(TripMember::TYPE_PASSENGER, 'New trip created', [
                'message' => 'Your friend has created a new trip and added you.',
                'type' => 'new_trip_created',
            ])->customPayload([
                'click_action' => 'new_trip_created',
                'trip_id' => $ride->id,
            ])->throwNotificationsVia('push')->build();
        }
    }
}
