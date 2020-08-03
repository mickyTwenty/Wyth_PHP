<?php

namespace App\Listeners;

use App\Classes\FireStoreHandler;
use App\Classes\PushNotification;
use App\Events\TerminateOfferUponTimeChange;
use App\Models\TripMember;
use App\Models\TripRideOffer;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RemoveOfferFromDatabase implements ShouldQueue
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
     * @param  TerminateOfferUponTimeChange  $event
     * @return void
     */
    public function handle(TerminateOfferUponTimeChange $event)
    {
        $intimatePassengersOfRide = $event->intimatePassengersOfRide;

        foreach ($intimatePassengersOfRide as $rideParentList) {
            $passengers = $rideParentList['passenger_id'];
            $ride       = $rideParentList['ride'];

            $offerMembers = $ride
                ->offers()
                ->where(function($query) use ($passengers) {
                    $query
                        ->where('from_user_id', '<>', $passengers)
                        ->orWhere('from_user_type', '<>', TripMember::TYPE_PASSENGER);
                })->where(function($query) use ($passengers) {
                    $query
                        ->where('to_user_id', '<>', $passengers)
                        ->orWhere('to_user_type', '<>', TripMember::TYPE_PASSENGER);
                })->where('has_accepted', 0);

            if ( !empty($rideParentList['group_id']) ) {
                $offerMembers->where('group_id', '<>', $rideParentList['group_id']);
            }

            $offerMembers = $offerMembers->get();

            if ($offerMembers->count() == 0) {
                continue;
            }

            $offerMembersUserIds = $offerMembers->map(function($row) {
                return $row->extractUserIdByUserType(TripMember::TYPE_PASSENGER);
            });

            $groupIdToRemove = $offerMembers->map(function($row) use ($rideParentList) {
                return 'offers_' . generateGroupName($row->from_user_id, $row->to_user_id, '');
            });

            // NOTE: Not handling time range filter to expire offer.

            // Intimate user via push that their offer has been voided
            foreach ($offerMembersUserIds as $passengerId) {
                User::find($passengerId)->createNotification(TripMember::TYPE_PASSENGER, 'Offer expired', [
                    'message' => 'Your offer has been expired for trip ' . strval($ride->trip->trip_name),
                    'type' => 'offer_contradicted',
                ])->customPayload([
                    'click_action' => 'offer_contradicted',
                    'trip_id' => $ride->id,
                ])->throwNotificationsVia('push')->build();
            }

            // Delete firestore node for chats/offers
            foreach ($groupIdToRemove as $offerParentNode) {
                FireStoreHandler::addDocument('groups/'.$ride->id.'/'.$offerParentNode, null, [
                    'delete' => true,
                ]);
            }

            // Remove entry from database
            TripRideOffer::whereIn('id', $offerMembers->pluck('id'))->delete();
        }
    }
}
