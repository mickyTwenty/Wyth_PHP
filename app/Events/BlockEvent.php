<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Queue\SerializesModels;

class BlockEvent
{
    use SerializesModels;

    public $blocker;
    public $blockee;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $blocker, User $blockee)
    {
        $this->blocker = $blocker;
        $this->blockee = $blockee;
    }
}
