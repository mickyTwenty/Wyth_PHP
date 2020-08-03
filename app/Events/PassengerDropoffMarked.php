<?php

namespace App\Events;

use App\Models\TripRide;
use Illuminate\Queue\SerializesModels;

class PassengerDropoffMarked
{
    use SerializesModels;

    public $tripRide;
    public $members;
    public $coordinates;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TripRide $tripRide, $members, $coordinates)
    {
        $this->tripRide    = $tripRide;
        $this->members     = $members;
        $this->coordinates = $coordinates;
    }
}
