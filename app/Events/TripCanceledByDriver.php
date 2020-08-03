<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Queue\SerializesModels;

class TripCanceledByDriver
{
    use SerializesModels;

    public $trip;
    public $members;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Trip $trip, array $members)
    {
        $this->trip = $trip;
        $this->members = $members;
    }
}
