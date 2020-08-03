<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Queue\SerializesModels;

class AccountDeleted
{
    use SerializesModels;

    public $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        // $this->user = User::whereId($user->id)->withTrashed()->first();
        $this->user = User::whereId($user->id)->first();
    }
}
