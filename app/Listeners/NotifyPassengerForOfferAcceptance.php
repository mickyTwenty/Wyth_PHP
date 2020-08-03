<?php

namespace App\Listeners;

use App\Classes\PushNotification;
use App\Events\OfferAcceptedByDriver;
use App\Models\TripMember;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyPassengerForOfferAcceptance implements ShouldQueue
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
     * @param  OfferAcceptedByDriver  $event
     * @return void
     */
    public function handle(OfferAcceptedByDriver $event)
    {
        $ride      = $event->ride;
        $driver    = $event->driver;
        $passenger = $event->passenger;
        $offer     = $event->offer;

        $ride->load('trip.rides');

        if ($offer->is_roundtrip == '1') {
            $retrunTripDate = $ride->trip->getReturningRideOfTrip();
        }

        User::find($passenger)->createNotification(TripMember::TYPE_PASSENGER, 'Your offer has been accepted', [
            'message' => 'Your offer has been accepted by driver. Please accept it from your side as well.',
            'type' => 'offer_accepted_driver',
        ])->customPayload([
            'click_action'     => 'offer_accepted_driver',
            'trip_id'          => $ride->id,
            'driver_id'        => $driver->id,
            'driver_name'      => $driver->full_name,
            'passenger_id'     => $passenger,
            'trip_name'        => $ride->trip->trip_name,
            'origin_text'      => $ride->origin_title,
            'destination_text' => $ride->destination_title,
            'proposed_amount'  => $offer->proposed_amount,
            'bags_quantity'    => $offer->bags_quantity,
            'date'             => $ride->start_time,
            'return_date'      => isset($retrunTripDate) ? $retrunTripDate->start_time : '',
        ])->throwNotificationsVia('push')->build();

        /*PushNotification::sendToUserConditionally($passenger, [
            'content' => [
                'title'   => 'Your offer has been accepted',
                'message' => 'Your offer has been accepted by driver. Please accept it from your side as well.',
                'action'  => 'offer_accepted_driver',
            ],
            'data' => [
                'trip_id'           => $ride->id,
                'driver_id'         => $driver->id,
                'passenger_id'      => $passenger,
                'trip_name'         => $ride->trip->trip_name,
                'origin_text'       => $ride->origin_title,
                'destination_text'  => $ride->destination_title,
                'proposed_amount'   => $offer->proposed_amount,
                'bags_quantity'     => $offer->bags_quantity,
            ],
        ]);*/
    }
}
