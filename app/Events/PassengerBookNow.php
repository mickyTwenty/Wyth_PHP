<?php

namespace App\Events;

use App\Models\TripRide;
use Illuminate\Queue\SerializesModels;

class PassengerBookNow
{
    use SerializesModels;

    public $ride;
    public $passengerIds;
    public $amountToCharge;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TripRide $ride, $passengerIds, $amountToCharge)
    {
        $this->ride           = $ride;
        $this->passengerIds   = $passengerIds;
        $this->amountToCharge = $amountToCharge;
    }
}
