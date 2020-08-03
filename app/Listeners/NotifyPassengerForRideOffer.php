<?php

namespace App\Listeners;

use App\Events\OfferMadeByDriver;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyPassengerForRideOffer
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
     * @param  OfferMadeByDriver  $event
     * @return void
     */
    public function handle(OfferMadeByDriver $event)
    {
        $ride      = $event->ride;
        $driver    = $event->driver;
        $passenger = $event->passenger;

        // Send push notification
    }
}
