<?php

namespace App\Listeners;

use App\Classes\FireStoreHandler;
use App\Classes\PHPFireStore\FireStoreTimestamp;
use App\Events\OfferMadeByPassenger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PostPassengerOfferToFireStore
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
     * @param  OfferMadeByPassenger  $event
     * @return void
     */
    public function handle(OfferMadeByPassenger $event)
    {
        $ride       = $event->ride;
        $leader     = $event->leader;
        $driver     = $event->driver;
        $passengers = $event->passengers;

        $driverId = $driver->id;

        foreach ($passengers as $passengerId) {
            $groupName  = generateGroupName($driverId, $passengerId, '');
            $parentNode = 'groups/' . $ride->id . '/offers_' . $groupName;

            $existingOffer = $ride->offers()->hasAnyOfferByPassengerTo($passengerId, $driverId)->first();

            if ( $existingOffer ) {
                FireStoreHandler::addDocument($parentNode, null, [
                    'trip_id'      => strval($ride->id),
                    'driver_id'    => intval($driverId),
                    'passenger_id' => intval($passengerId),
                    'first_name'   => $driver->first_name,
                    'last_name'    => $driver->last_name,
                    'price'        => $existingOffer->proposed_amount,
                    'bags'         => $existingOffer->bags_quantity,
                    'sender'       => 'passenger',
                    'sender_id'    => intval($passengerId),
                    'timestamp'    => new FireStoreTimestamp()
                ]);
            }
        }
    }
}
