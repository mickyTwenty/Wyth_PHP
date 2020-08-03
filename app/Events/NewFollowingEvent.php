<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Queue\SerializesModels;

class NewFollowingEvent
{
    use SerializesModels;

    public $follower;
    public $following;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $follower, User $following)
    {
        $this->follower = $follower;
        $this->following = $following;
    }
}
