<?php

namespace App\Events\Api;

use App\Models\User;
use Illuminate\Queue\SerializesModels;

class JWTUserLogin
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
        $this->user = $user;
    }
}
