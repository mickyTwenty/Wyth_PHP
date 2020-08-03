<?php

namespace App\Events;

use App\Models\TripRide;
use Illuminate\Queue\SerializesModels;

class TripStarted
{
    use SerializesModels;

    public $tripRide;
    public $members;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TripRide $tripRide, $members)
    {
        $this->tripRide = $tripRide;
        $this->members  = $members;
    }
}
