<?php

namespace App\Events;

use App\Models\TripRideRoute;
use Illuminate\Queue\SerializesModels;

class TripRideRouteCreated
{
    use SerializesModels;

    public $tripRideRoute;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TripRideRoute $tripRideRoute)
    {
        $this->tripRideRoute = $tripRideRoute;
    }
}
