<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Queue\SerializesModels;

class NewUnfollowingEvent
{
    use SerializesModels;

    public $unfollower;
    public $unfollowing;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $unfollower, User $unfollowing)
    {
        $this->unfollower = $unfollower;
        $this->unfollowing = $unfollowing;
    }
}
