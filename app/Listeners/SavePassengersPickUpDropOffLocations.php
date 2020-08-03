<?php

namespace App\Listeners;

use App\Events\OfferMadeByPassenger;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SavePassengersPickUpDropOffLocations
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
        $request    = $event->request;

        $isRoundTrip = intval($request->get('is_roundtrip', 0));

        if (!$request->has('pickup_latitude')) {
            // Dont overwrite because not received any payload
            return;
        }

        // Save passenger pickup and dropoff locations
        foreach ($passengers as $passengerId) {
            // Let it iterate through all rides
            foreach ($ride->trip->rides as $rideIndex => $roundTripRide) {

                // If passenger does not wants to book roundtrip then dont add them to other-way ride
                if ( !$isRoundTrip && $roundTripRide->id !== $ride->id ) {
                    continue;
                }

                $payload = [
                    'pickup_latitude'   => $request->get('pickup_latitude', ''),
                    'pickup_longitude'  => $request->get('pickup_longitude', ''),
                    'pickup_title'      => $request->get('pickup_title', ''),
                    'dropoff_latitude'  => $request->get('dropoff_latitude', ''),
                    'dropoff_longitude' => $request->get('dropoff_longitude', ''),
                    'dropoff_title'     => $request->get('dropoff_title', ''),
                ];

                if ($rideIndex > 0) {
                    // Inverse the payload
                    $payload = [
                        'pickup_latitude'   => $request->get('dropoff_latitude', ''),
                        'pickup_longitude'  => $request->get('dropoff_longitude', ''),
                        'pickup_title'      => $request->get('dropoff_title', ''),
                        'dropoff_latitude'  => $request->get('pickup_latitude', ''),
                        'dropoff_longitude' => $request->get('pickup_longitude', ''),
                        'dropoff_title'     => $request->get('pickup_title', ''),
                    ];
                }

                $roundTripRide->setMeta('geo.passenger_'.$passengerId, $payload);
                $roundTripRide->save();
            }
        }
    }
}
