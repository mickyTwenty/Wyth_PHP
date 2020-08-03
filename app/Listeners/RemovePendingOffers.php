<?php

namespace App\Listeners;

use App\Events\TripMembersAdded;
use App\Models\TripMember;
use App\Models\TripRideOffer;

//use Illuminate\Queue\InteractsWithQueue;
//use Illuminate\Contracts\Queue\ShouldQueue;

class RemovePendingOffers
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

        info("Deleting pending offers of ride #{$tripRide->id} : {$tripRide->seats_available} seats available");

        if ($tripRide->seats_available > count($userIds)) {
            return;
        }

        $offers = TripRideOffer::where('trip_ride_id', $tripRide->id)->notAccepted()->with(['sender', 'receiver'])
            ->where(function ($query) use ($userIds) {
                $query->where(function ($query) use ($userIds) {
                    $query->where(function ($query) use ($userIds) {
                        $query->whereNotIn('from_user_id', $userIds)
                            ->where('from_user_type', TripMember::TYPE_PASSENGER);
                    })->orWhere(function ($query) use ($userIds) {
                        $query->whereNotIn('to_user_id', $userIds)
                            ->where('to_user_type', TripMember::TYPE_PASSENGER);
                    });
                });
            })
            ->get();

        foreach ($offers as $offer) {
            info($offer);

            if ($offer->from_user_type == TripMember::TYPE_PASSENGER) {
                $passenger = $offer->sender;
            } else {
                $passenger = $offer->receiver;
            }

            $offer->delete();

            $passenger->createNotification(TripMember::TYPE_PASSENGER, 'All seats booked, your pending offer will be removed.', [
                'message' => 'All seats booked, your pending offer will be removed.',
                'type'    => 'pending_offer_removed',
            ])->customPayload([
                'click_action' => 'pending_offer_removed',
                'trip_id'      => $tripRide->id,
            ])->throwNotificationsVia('push')->build();
        }
    }
}
