<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class RideSearches
{
    use SerializesModels;

    public $request;
    public $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Request $request, User $user)
    {
        $this->request = $request;
        $this->user = $user;
    }
}
