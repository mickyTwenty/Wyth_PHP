<?php

namespace App\Listeners;

use Exception;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use DB;

class DeleteRideRoutePolygons
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
     * @param  $event TripDeleted|TripCanceledByDriver
     * @return void
     */
    public function handle($event)
    {
        $ride = $event->ride;

        try {
            $query = "
            DELETE
              trp2.*
            FROM
              trip_ride_polygon AS trp2
              JOIN
                (SELECT
                  trr.id AS trip_ride_route_id
                FROM
                  trip_rides AS tr
                  INNER JOIN trip_ride_routes AS trr
                    ON trr.trip_ride_id = tr.id
                  INNER JOIN trip_ride_polygon AS trp
                    ON trp.trip_ride_route_id = trr.id
                WHERE tr.id = ?
                GROUP BY trr.id) t2
                ON t2.trip_ride_route_id = trp2.trip_ride_route_id;
            ";

            $result = DB::statement($query, [
                $ride->id
            ]);

        } catch (Exception $e) {
            logger('Unable to delete polygons of request ride: ' . $e->getMessage());
        }
    }
}
