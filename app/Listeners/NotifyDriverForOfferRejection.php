<?php

namespace App\Listeners;

use App\Classes\PushNotification;
use App\Events\OfferRejectedByPassenger;
use App\Models\TripMember;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyDriverForOfferRejection implements ShouldQueue
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
     * @param  OfferRejectedByPassenger  $event
     * @return void
     */
    public function handle(OfferRejectedByPassenger $event)
    {
        $ride      = $event->ride;
        $passenger = $event->passenger;
        $driver    = $event->driver;

        User::find($driver)->createNotification(TripMember::TYPE_DRIVER, 'Your offer has been rejected', [
            'message' => 'Your offer has been rejected by passenger (' . strval($passenger->full_name) . ')',
            'type' => 'offer_rejected_passenger',
        ])->customPayload([
            'click_action' => 'offer_rejected_passenger',
            'trip_id' => $ride->id,
        ])->throwNotificationsVia('push')->build();
    }
}
