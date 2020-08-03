<?php

namespace App\Listeners;

use App\Events\TripRideRouteCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use DB;
use Exception;

class CreateRidePolygons implements ShouldQueue
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
     * @param  TripRideRouteCreated  $event
     * @return void
     */
    public function handle(TripRideRouteCreated $event)
    {
        $tripRideRoute   = $event->tripRideRoute;

        $radiusBuffer    = constants('global.ride.point_buffer');
        $pointCollection = polylineDecode($tripRideRoute->stepped_route);

        if ( is_array($pointCollection) ) {
            foreach ($pointCollection as $point) {
                DB::table('trip_ride_polygon')->insert([
                    'trip_ride_route_id' => $tripRideRoute->id,
                    'point_polygon' => DB::raw('ST_GEOMFROMTEXT("'.createPointBuffer( $point['latitude'], $point['longitude'], $radiusBuffer ).'")'),
                ]);
            }
        } else {
            throw new Exception('Route is not array, please check database.');

        }
    }
}
