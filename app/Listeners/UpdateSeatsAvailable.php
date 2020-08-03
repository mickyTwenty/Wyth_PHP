<?php

namespace App\Listeners;

use App\Models\TripMember;
use App\Models\TripRide;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateSeatsAvailable implements ShouldQueue
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
     * @param  TripMembersAdded|TripMembersUpdated  $event
     * @return void
     */
    public function handle($event)
    {
        $tripRide = $event->tripRide;

        $members   = $tripRide->members()->count();
        $available = $tripRide->seats_total - $members;

        $tripRide->seats_available = $available;
        $tripRide->save();

        // Revert status back to active form filled if seats available found more than zero.
        // Because this will be called when passenger added or removed. On removal case ride need to get back to active state.
        // LOW | TODO: This needs to be wrapped in trip->rides because all ride's status should be same.
        if (intval($available) > 0) {
            $tripRide = TripRide::find($tripRide->id);
            $tripRide->updateRideStatus(TripRide::RIDE_STATUS_ACTIVE, ($tripRide->ride_status != TripRide::RIDE_STATUS_ACTIVE));
        }
    }
}
