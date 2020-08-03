<?php

namespace App\Events;

use App\Models\TripRide;
use Illuminate\Queue\SerializesModels;

class TripMembersUpdated
{
    use SerializesModels;

    public $tripRide;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TripRide $tripRide)
    {
        $this->tripRide     = $tripRide;
    }
}
