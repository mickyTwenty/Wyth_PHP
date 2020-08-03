<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RemovePassengerOffersUponKick
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
     * @param  $event
     * @return void
     */
    public function handle($event)
    {
        $ride        = $event->ride;
        $driver      = $event->driver;
        $passengerId = $event->passengerId;

        // $ride->load('trip');

        $ride->offers()->hasAnyOfferByPassengerTo($passengerId, $driver->id)->delete();
    }
}
