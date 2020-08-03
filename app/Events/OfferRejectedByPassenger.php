<?php

namespace App\Events;

use App\Models\TripRide;
use App\Models\TripRideOffer;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

class OfferRejectedByPassenger
{
    use SerializesModels;

    public $ride;
    public $passenger;
    public $driver;
    public $offer;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TripRide $ride, User $passenger, $driver, TripRideOffer $offer)
    {
        $this->ride       = $ride;
        $this->passenger  = $passenger;
        $this->driver     = $driver;
        $this->offer      = $offer;
    }
}
