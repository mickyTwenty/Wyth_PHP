<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;


class NotifySubscribedUser implements ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $trip;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($trip)
    {
        $this->trip  = $trip;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
