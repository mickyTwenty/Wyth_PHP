<?php

namespace App\Listeners;

use App\Events\RideSearches;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SaveRideSearches
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  RideSearches  $event
     * @return void
     */
    public function handle(RideSearches $event)
    {
        $request = $event->request;
        $user = $event->user;

        $user->saveSearch( $request );
    }
}
