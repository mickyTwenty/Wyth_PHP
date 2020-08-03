<?php

namespace App\Listeners;

use App\Classes\FireStoreHandler;
use App\Classes\PHPFireStore\FireStoreTimestamp;
use App\Events\OfferMadeByDriver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PostDriverOfferToFireStore
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
     * @param  OfferMadeByDriver  $event
     * @return void
     */
    public function handle(OfferMadeByDriver $event)
    {
        $ride       = $event->ride;
        $driver     = $event->driver;
        $passengers = $event->passengers;

        $driverId = $driver->id;

        foreach ($passengers as $passengerId) {
            $groupName  = generateGroupName($driverId, $passengerId, '');
            $parentNode = 'groups/' . $ride->id . '/offers_' . $groupName;

            $existingOffer = $ride->offers()->hasAnyOfferByDriverTo($driverId, $passengerId)->first();

            if ( $existingOffer ) {
                FireStoreHandler::addDocument($parentNode, null, [
                    'trip_id'      => strval($ride->id),
                    'driver_id'    => intval($driverId),
                    'passenger_id' => intval($passengerId),
                    'first_name'   => $driver->first_name,
                    'last_name'    => $driver->last_name,
                    'price'        => $existingOffer->proposed_amount,
                    'bags'         => $existingOffer->bags_quantity,
                    'sender'       => 'driver',
                    'sender_id'    => intval($driverId),
                    'timestamp'    => new FireStoreTimestamp()
                ]);
            }
        }
    }
}
