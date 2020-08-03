<?php

namespace App\Events;

use App\Models\TripRide;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

class OfferMadeByDriver
{
    use SerializesModels;

    public $ride;
    public $driver;
    public $passengers;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TripRide $ride, User $driver, array $passengers)
    {
        $this->ride       = $ride;
        $this->driver     = $driver;
        $this->passengers = $passengers;
    }
}
