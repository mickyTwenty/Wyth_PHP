<?php

namespace App\Events;

use App\Models\TripRide;
use Illuminate\Queue\SerializesModels;

class TripMembersAdded
{
    use SerializesModels;

    public $tripRide;
    public $userIds;
    public $payload;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TripRide $tripRide, array $userIds, $payload)
    {
        $this->tripRide = $tripRide;
        $this->userIds  = $userIds;
        $this->payload  = $payload;
    }
}
