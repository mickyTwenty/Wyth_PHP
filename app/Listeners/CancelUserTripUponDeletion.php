<?php

namespace App\Listeners;

use App\Events\AccountDeleted;
use App\Models\Trip;
use App\Models\TripRide;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CancelUserTripUponDeletion
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
     * @param  AccountDeleted  $event
     * @return void
     */
    public function handle(AccountDeleted $event)
    {
        $user = $event->user;

        if ($user->isDriver()) {
            $upcomingRides = TripRide::with(['trip.driver'])
                ->notEnded()
                ->upcoming()
                ->whereHas('trip', function ($query) use ($user) {
                    return $query->driverId($user->id)->notCanceled();
                })
                ->get();

            // Cancel trip as driver
            if ($upcomingRides->count()) {
                $driverTrips = Trip::whereIn($upcomingRides->pluck('trip.id'))->get();

                foreach ($driverTrips as $trip) {
                    $trip->cancelTripByDriver(false);
                }
            }
        }

        // HIGH | TODO: Cancel trip as passenger
    }
}
