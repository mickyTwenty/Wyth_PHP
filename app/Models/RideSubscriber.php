<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class RideSubscriber extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'origin_title',
        'origin_latitude',
        'origin_longitude',
        'destination_title',
        'destination_latitude',
        'destination_longitude',
        'is_roundtrip',
    ];

    public static function searchNearByRequests($request)
    {
        $radius = M_PI / 180;

        $query = "
        SELECT
              id,
              (
              ACOS(
                SIN(? * (PI()/180)) *
                SIN(origin_latitude * (PI()/180)) +
                COS(? * (PI()/180)) *
                COS(origin_latitude * (PI()/180)) *
                COS((? - origin_longitude) * (PI()/180))

              ) / (PI()/180) * 60 * 1.852
            ) AS origin_distance,
              (
              ACOS(
                SIN(? * (PI()/180)) *
                SIN(destination_latitude * (PI()/180)) +
                COS(? * (PI()/180)) *
                COS(destination_latitude * (PI()/180)) *
                COS((? - destination_longitude) * (PI()/180))

              ) / (PI()/180) * 60 * 1.852
            ) AS destination_distance
            FROM ride_subscribers
            WHERE is_processed = 0
            HAVING origin_distance < ?
            ORDER BY origin_distance + destination_distance
            #LIMIT 0, 30
        ";

        $bindings = [
            $request->get('origin_latitude'),
            $request->get('origin_latitude'),
            $request->get('origin_longitude'),
            $request->get('destination_latitude'),
            $request->get('destination_latitude'),
            $request->get('destination_longitude'),
            constants('global.ride.point_buffer') / 1000 // Convert it to kilometer radius
        ];

        $results = DB::select(DB::raw($query), $bindings);

        return collect($results);
    }

    public function extractUserOfRouteSubscribers(Trip $trip, TripRide $ride)
    {
        $genderJoinForPassenger = $genderWhereForDriver = 'AND 1=1';

        $bindings = [
            $trip->id,
        ];

        // This filter is being handle on client side.
        if (false) {
            $driverGender = ['Male'];
            $genderJoinForPassenger = "
            INNER JOIN users ON users.id = trips.user_id
            INNER JOIN user_meta AS um ON um.user_id = users.id AND um.key = 'gender' AND um.value IN ('" . implode("', '", $driverGender) . "')
            ";
        }

        if (in_array($ride->desired_gender, [1, 2])) {
            $driverGender = $ride->desired_gender == 1 ? ['Male'] : ['Female'];
            $genderWhereForDriver = "
            AND rs.user_id IN (SELECT um.user_id FROM user_meta AS um WHERE um.key = 'gender' AND um.value IN ('" . implode("', '", $driverGender) . "'))
            ";
        }

        $query = "
            SELECT rs.id, trips.user_id AS driver_id, rs.user_id AS passenger_id

            # Joins
            FROM ride_subscribers AS rs, trips
            INNER JOIN trip_rides AS tr ON tr.trip_id = trips.id
            INNER JOIN trip_ride_routes AS trr ON trr.trip_ride_id = tr.id
            INNER JOIN trip_ride_polygon AS trp ON trp.trip_ride_route_id = trr.id

            # Gender of driver, which passenger wants
            $genderJoinForPassenger

            WHERE

            # Match co-ordinates first
            ST_CONTAINS(
              trp.point_polygon,
              ST_GEOMFROMTEXT(CONCAT('POINT(', rs.origin_longitude, ' ', rs.origin_latitude, ')'))
            )
            AND
            (
                SELECT trp2.id FROM trip_ride_polygon AS trp2
                INNER JOIN trip_ride_routes AS trr2 ON trr2.id = trp2.trip_ride_route_id
                WHERE
                trp2.id > trp.id AND
                trr2.trip_ride_id = trr.trip_ride_id AND
                ST_CONTAINS(
                  trp2.point_polygon,
                  ST_GEOMFROMTEXT(CONCAT('POINT(', rs.destination_longitude, ' ', rs.destination_latitude, ')'))
                ) LIMIT 1
            )

            AND trips.id = ?

            AND rs.is_processed = 0

            # Passenger round-trip criteria search.
            AND (
                (rs.is_roundtrip = 1 AND trips.is_roundtrip = 1) OR (rs.is_roundtrip = 0)
            )

            # Gender of passenger which driver wants.
            $genderWhereForDriver

            GROUP BY rs.id DESC
        ";

        $results = DB::select(DB::raw($query), $bindings);

        return $results;
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('is_processed', 0);
    }

    /*
     * @Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
