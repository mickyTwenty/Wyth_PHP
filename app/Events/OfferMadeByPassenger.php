<?php

namespace App\Events;

use App\Models\TripRide;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class OfferMadeByPassenger
{
    use SerializesModels;

    public $ride;
    public $leader;
    public $driver;
    public $passengers;
    public $request;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TripRide $ride, User $leader, User $driver, array $passengers, Request $request)
    {
        $this->ride       = $ride;
        $this->leader     = $leader;
        $this->driver     = $driver;
        $this->passengers = $passengers;
        $this->request    = $request;
    }
}
