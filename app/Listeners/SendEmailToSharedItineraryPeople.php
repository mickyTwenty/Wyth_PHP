<?php

namespace App\Listeners;

use App\Events\TripStarted;
use App\Mail\ShareItenraryTripStarted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Mail;

class SendEmailToSharedItineraryPeople implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  TripStarted  $event
     * @return void
     */
    public function handle(TripStarted $event)
    {
        $tripRide = $event->tripRide;

        foreach ($tripRide->shareItenerary as $tripRideShared) {
            Mail::to($tripRideShared->email)->send(new ShareItenraryTripStarted($tripRideShared));
        }
    }
}
