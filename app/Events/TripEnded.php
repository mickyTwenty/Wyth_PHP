<?php

namespace App\Events;

use App\Models\TripRide;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

class TripEnded
{
    use SerializesModels;

    public $tripRide;
    public $driver;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TripRide $tripRide, User $driver)
    {
        $this->tripRide = $tripRide;
        $this->driver   = $driver;
    }
}
