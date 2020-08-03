<?php

namespace App\Listeners;

use App\Events\TripMembersAdded;
use App\Models\TripMember;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyUsersOnAddedToTrip
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
     * @param  TripMembersAdded  $event
     * @return void
     */
    public function handle(TripMembersAdded $event)
    {
        $tripRide = $event->tripRide;
        $userIds  = $event->userIds;
        $payload  = $event->payload;

        $invitedBy = is_null($payload->get('invited_by')) ? null : User::extractUserId($payload->get('invited_by'));

        foreach ($userIds as $userId) {
            // Don't send notification to invited other passenger.
            if ( $userId != $invitedBy ) {
                User::find($userId)->createNotification(TripMember::TYPE_PASSENGER, 'You have been added to trip.', [
                    'message' => 'You have been added to trip.',
                    'type'    => 'added_to_trip',
                ])->customPayload([
                    'click_action'     => 'added_to_trip',
                    'trip_id'          => $tripRide->id,
                ])->throwNotificationsVia('push')->build();
            }

        }
    }
}
