<?php

namespace App\Events;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

class NewNotificationAdded
{
    use SerializesModels;

    public $notification;
    public $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Notification $notification, User $user)
    {
        $this->notification = $notification;
        $this->user         = $user;
    }
}
