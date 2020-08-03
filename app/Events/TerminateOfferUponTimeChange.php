<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class TerminateOfferUponTimeChange
{
    use SerializesModels;

    public $intimatePassengersOfRide;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(array $intimatePassengersOfRide)
    {
        $this->intimatePassengersOfRide = $intimatePassengersOfRide;
    }
}
