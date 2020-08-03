<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Queue\SerializesModels;

class CreateUserFromBackend
{
    use SerializesModels;

    public $user, $attributes;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, $attributes)
    {
        $this->user       = $user;
        $this->attributes = $attributes;
    }
}
