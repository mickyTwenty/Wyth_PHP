<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Queue\SerializesModels;

class TripDeleted
{
    use SerializesModels;

    public $trip;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Trip $trip)
    {
        $this->trip = $trip;
    }
}
