<?php

namespace App\Listeners;

use App\Events\TripRated;
use App\Models\TripMember;
use App\Models\TripRating;
use App\Models\User;
use App\Models\UserMeta;

//use Illuminate\Queue\InteractsWithQueue;
//use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateUserAverageRating
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
     * @param  TripRated $event
     * @return void
     */
    public function handle(TripRated $event)
    {
        $tripRide   = $event->tripRide;
        $tripRating = $event->tripRating;

        if ($tripRating->ratee_type == TripMember::TYPE_DRIVER) {
            $driver = $tripRide->trip->driver;

            $avgRating = TripRating::where([
                'ratee_id'   => $driver->id,
                'ratee_type' => TripMember::TYPE_DRIVER,
            ])->avg('rating');

            $driver = User::find($driver->id);
            if ($driver) {
                $driver->setMeta(['rating' => number_format($avgRating, 2)], UserMeta::GROUPING_DRIVER);
                $driver->update();
            } else {
                info('User not found while saving rating avg!', $driver->id);
            }
        } else {
            $passenger = $tripRating->ratee;

            $avgRating = TripRating::where([
                'ratee_id'   => $passenger->id,
                'ratee_type' => TripMember::TYPE_PASSENGER,
            ])->avg('rating');

            $passenger = User::find($passenger->id);
            if ($passenger) {
                $passenger->setMeta(['rating' => number_format($avgRating, 2)], UserMeta::GROUPING_PROFILE);
                $passenger->update();
            } else {
                info('User not found while saving rating avg!', $passenger->id);
            }
        }
    }
}
