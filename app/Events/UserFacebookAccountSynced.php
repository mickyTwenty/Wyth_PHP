<?php

namespace App\Events;

use App\Models\User;
use App\Models\UserFacebook;
use Illuminate\Queue\SerializesModels;

class UserFacebookAccountSynced
{
    use SerializesModels;

    public $user;
    public $user_facebook;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, UserFacebook $user_facebook)
    {
        $this->user = $user;
        $this->user_facebook = $user_facebook;
    }
}
