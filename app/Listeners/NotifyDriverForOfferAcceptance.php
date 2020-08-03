<?php

namespace App\Listeners;

use App\Classes\PushNotification;
use App\Events\OfferAcceptedByPassenger;
use App\Models\TripMember;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyDriverForOfferAcceptance implements ShouldQueue
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
        $ride      = $event->ride;
        $passenger = $event->passenger;
        $driver    = $event->driver;

        User::find($driver)->createNotification(TripMember::TYPE_DRIVER, 'Your offer has been accepted', [
            'message' => 'Your offer has been accepted by passenger (' . strval($passenger->full_name) . ')',
            'type' => 'offer_accepted_passenger',
        ])->customPayload([
            'click_action' => 'offer_accepted_passenger',
            'trip_id' => $ride->id,
        ])->throwNotificationsVia('push')->build();

        /*PushNotification::sendToUserConditionally($driver, [
            'content' => [
                'title' => 'Your offer has been accepted',
                'message' => 'Your offer has been accepted by your passenger.',
                'action' => 'offer_accepted_passenger',
            ],
            'data' => [
                'trip_id' => $ride->id,
            ],
        ]);*/
    }
}
