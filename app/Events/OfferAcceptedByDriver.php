<?php

namespace App\Events;

use App\Models\TripRide;
use App\Models\TripRideOffer;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

class OfferAcceptedByDriver
{
    use SerializesModels;

    public $ride;
    public $driver;
    public $passenger;
    public $offer;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TripRide $ride, User $driver, $passenger, TripRideOffer $offer)
    {
        $this->ride      = $ride;
        $this->driver    = $driver;
        $this->passenger = $passenger;
        $this->offer     = $offer;
    }
}
