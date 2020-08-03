<?php

namespace App\Listeners;

use App\Models\TripMember;
use App\Models\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendNotificationToRouteSubscribers implements ShouldQueue
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
        $trip            = $event->trip;

        $ride            = $trip->getGoingRideOfTrip();
        $subscribedUsers = collect((new \App\Models\RideSubscriber)->extractUserOfRouteSubscribers($trip, $ride));
        info('found user ids: ' . var_export($subscribedUsers->toArray(), true));
        $uniqueUsersId   = $subscribedUsers->pluck('passenger_id')->unique()->values();
        $tripPassengers  = $ride->members->pluck('user_id')->push($trip->user_id);
        info('tripPassengers: ' . var_export($tripPassengers->toArray(), true));

        $markSentForUsers = [];
        foreach ($uniqueUsersId as $userId) {

            // Ignore those users who are already in ride including driver
            if ($tripPassengers->contains($userId)) {

                // Driver can also make request from passenger and create ride
                // if ($userId == $trip->user_id) {
                //     $markSentForUsers[] = $userId;
                // }

                // Marking every one and removing requests since its found inside radius defined.
                $markSentForUsers[] = $userId;

                continue;
            }

            $markSentForUsers[] = $userId;

            info('Sending trip suggestion to: ' . $userId);

            User::find($userId)->createNotification(TripMember::TYPE_PASSENGER, 'Trip Suggestion', [
                'message' => 'There\'s a ride that now available which matches your criteria.',
                'type' => 'new_trip_suggestion',
            ])->customPayload([
                'click_action' => 'new_trip_suggestion',
                'trip_id' => $ride->id,
            ])->throwNotificationsVia('push')->build();
        }

        # Extract search_id of user's subscribed route first
        $searchIds = [];
        foreach ($subscribedUsers as $record) {
            if (in_array($record->passenger_id, $markSentForUsers)) {
                $searchIds[] = $record->id;
            }
        }

        info('Search IDs: ' . var_export($searchIds, true));

        \App\Models\RideSubscriber::whereIn('id', $searchIds)->update([
            'is_processed' => 1,
        ]);
    }
}
