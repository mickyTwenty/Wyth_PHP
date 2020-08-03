<?php

namespace App\Listeners;

use App\Events\PassengerDropoffMarked;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SetPassengersPendingRatingFlag
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
     * @param  PassengerDropoffMarked  $event
     * @return void
     */
    public function handle(PassengerDropoffMarked $event)
    {
        $ride    = $event->tripRide;
        $members = $event->members;

        if (count($members)) {
            foreach ($members as $member) {
                $passenger = $member->user;

                $passenger->setMeta(['pending_rating' => true]);
                $passenger->save();
            }
        }
    }
}
