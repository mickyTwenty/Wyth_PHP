<?php

namespace App\Events;

use App\Models\TripRating;
use App\Models\TripRide;
use Illuminate\Queue\SerializesModels;

class TripRated
{
    use SerializesModels;

    public $tripRide;
    public $tripRating;
    public $attributes;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TripRide $tripRide, TripRating $tripRating, $attributes=array())
    {
        $this->tripRide   = $tripRide;
        $this->tripRating = $tripRating;
        $this->attributes = $attributes;
    }
}
