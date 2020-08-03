<?php

namespace App\Listeners;

use App\Classes\FireStoreHandler;
use App\Classes\PushNotification;
use App\Events\OfferAcceptedByPassenger;
use App\Models\TripMember;
use App\Models\TripRideOffer;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ExpireDriverOffer implements ShouldQueue
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
     * @param  OfferAcceptedByPassenger  $event
     * @return void
     */
    public function handle(OfferAcceptedByPassenger $event)
    {
        $passengerId = $event->passenger;
        $ride        = $event->ride;
        $offer       = $event->offer;
        $driverId    = $event->driver;

        $offerMembers = $ride
            ->offers()
            ->where(function($query) use ($passengerId, $driverId) {
                $query->where(function($query) use ($passengerId, $driverId) {
                    $query
                        ->where('from_user_id', '=', $passengerId)
                        ->where('from_user_type', '=', TripMember::TYPE_PASSENGER)
                        ->where('to_user_id', '<>', $driverId);
                })->orWhere(function($query) use ($passengerId, $driverId) {
                    $query
                        ->where('to_user_id', '=', $passengerId)
                        ->where('to_user_type', '=', TripMember::TYPE_PASSENGER)
                        ->where('from_user_id', '<>', $driverId);
                });
            })
            ->where('has_accepted', 0)
            ->where('id', '<>', $offer->id);

        $offerMembers = $offerMembers->get();

        if ( $offerMembers->count() == 0 ) {
            return;
        }

        $offerMembersUserIds = $offerMembers->map(function($row) {
            return $row->extractUserIdByUserType(TripMember::TYPE_DRIVER);
        });

        $groupIdToRemove = $offerMembers->map(function($row)  {
            return 'offers_' . generateGroupName($row->from_user_id, $row->to_user_id, '');
        });

        // NOTE: Not handling time range filter to expire offer.

        // Intimate user via push that their offer has been voided
        foreach ($offerMembersUserIds as $driverId) {
            User::find($driverId)->createNotification(TripMember::TYPE_DRIVER, 'Offer expired', [
                'message' => 'Your offer has been expired for trip ' . strval($ride->trip->trip_name),
                'type' => 'offer_contradicted',
            ])->customPayload([
                'click_action' => 'offer_contradicted',
                'trip_id' => $ride->id,
            ])->throwNotificationsVia('push')->build();

            // PushNotification::sendToUserConditionally($driverId, [
            //     'content' => [
            //         'title' => 'Offer expired',
            //         'message' => 'Your offer has been expired for trip ' . strval($ride->trip->trip_name),
            //         'action' => 'offer_contradicted',
            //     ],
            //     'data' => [
            //         'trip_id' => $ride->id,
            //     ],
            // ]);
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
