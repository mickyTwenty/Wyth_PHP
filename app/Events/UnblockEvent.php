<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Queue\SerializesModels;

class UnblockEvent
{
    use SerializesModels;

    public $unblocker;
    public $unblockee;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $unblocker, User $unblockee)
    {
        $this->unblocker = $unblocker;
        $this->unblockee = $unblockee;
    }
}
