<?php

namespace App\Events;

use App\Models\TripRide;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

class PassengerRemovedFromTrip
{
    use SerializesModels;

    public $ride;
    public $driver;
    public $passengerId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TripRide $ride, User $driver, $passengerId)
    {
        $this->ride        = $ride;
        $this->driver      = $driver;
        $this->passengerId = $passengerId;
    }
}
