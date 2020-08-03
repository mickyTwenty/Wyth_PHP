<?php

namespace App\Events;

use App\Models\TripRide;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

class PassengerTripPayment
{
    use SerializesModels;

    public $ride;
    public $passenger;
    public $driver;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TripRide $ride, User $passenger, $driver)
    {
        $this->ride       = $ride;
        $this->passenger  = $passenger;
        $this->driver     = $driver;
    }
}
