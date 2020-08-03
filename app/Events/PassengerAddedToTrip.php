<?php

namespace App\Events;

use App\Models\TripRide;
use Illuminate\Queue\SerializesModels;

class PassengerAddedToTrip
{
    use SerializesModels;

    public $ride;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TripRide $ride)
    {
        $this->ride = $ride;
    }
}
