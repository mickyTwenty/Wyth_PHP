<?php

namespace App\Events;

use App\Models\TripMember;
use App\Models\TripRide;
use App\Models\TripRideOffer;
use Illuminate\Queue\SerializesModels;

class PassengerAcceptedOffer
{
    use SerializesModels;

    public $ride;
    public $passenger;
    public $offer;
    public $amountToCharge;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TripRide $ride, TripMember $passenger, TripRideOffer $offer, $amountToCharge)
    {
        $this->ride           = $ride;
        $this->passenger      = $passenger;
        $this->offer          = $offer;
        $this->amountToCharge = $amountToCharge;
    }
}
