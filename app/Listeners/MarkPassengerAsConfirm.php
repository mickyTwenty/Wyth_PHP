<?php

namespace App\Listeners;

use App\Events\OfferAcceptedByPassenger;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class MarkPassengerAsConfirm
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
     * @param  OfferAcceptedByPassenger  $event
     * @return void
     */
    public function handle(OfferAcceptedByPassenger $event)
    {
        $ride       = $event->ride;
        $passenger  = $event->passenger;
        $driver     = $event->driver;

        $record = $ride->members()->memberId( User::extractUserId($passenger) )->first();

        if ( $record ) {
            if ( !$record->isConfirmed() ) {
                $record->is_confirmed = 1;
                $record->save();
            }
        } else {
            \Log::info('Unable to find record with passenger on a ride', [User::extractUserId($passenger), $ride]);
        }
    }
}
