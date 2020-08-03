<?php

namespace App\Events;

use App\Models\TripRide;
use Illuminate\Queue\SerializesModels;

class RideCanceledByDriver
{
    use SerializesModels;

    public $ride;
    public $members;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TripRide $ride, array $members)
    {
        $this->ride = $ride;
        $this->members = $members;
    }
}
