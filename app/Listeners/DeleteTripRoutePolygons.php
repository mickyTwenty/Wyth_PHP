<?php

namespace App\Listeners;

use Exception;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use DB;

class DeleteTripRoutePolygons
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
        $trip = $event->trip;

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
                  trips AS t
                  INNER JOIN trip_rides AS tr
                    ON tr.trip_id = t.id
                  INNER JOIN trip_ride_routes AS trr
                    ON trr.trip_ride_id = tr.id
                  INNER JOIN trip_ride_polygon AS trp
                    ON trp.trip_ride_route_id = trr.id
                WHERE t.id = ?
                GROUP BY trr.id) t2
                ON t2.trip_ride_route_id = trp2.trip_ride_route_id;
            ";

            $result = DB::statement($query, [
                $trip->id
            ]);

        } catch (Exception $e) {
            logger('Unable to delete polygons of request trip: ' . $e->getMessage());
        }
    }
}
