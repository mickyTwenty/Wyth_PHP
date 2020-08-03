<?php

namespace App\Models;

use App\Events\RideSearches;
use App\Events\TripCreatedByDriver;
use App\Events\TripCreatedByPassenger;
use App\Events\TripDeleted;
use App\Exceptions\InvalidRouteGiven;
use App\Exceptions\UndefinedTripDriver;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Log;

class Trip extends Model
{
    const DEFAULT_PAYOUT = 'standard';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'trip_name',
        'origin_latitude',
        'origin_longitude',
        'origin_title',
        'destination_latitude',
        'destination_longitude',
        'destination_title',
        // 'expected_start_time',
        'expected_distance',
        'expected_distance_format',
        'expected_duration',
        'is_roundtrip',
        'is_enabled_booknow',
        'booknow_price',
        'min_estimates',
        'max_estimates',
        'estimates_format',
        'initiated_by',
        'initiated_type',
        'payout_type',
        'is_request',
        'canceled_at',
        'earned_by_driver',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_roundtrip'       => 'boolean',
        'is_enabled_booknow' => 'boolean',
        'is_request'         => 'boolean',
        'canceled_at'        => 'datetime',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $events = [
        'deleting' => TripDeleted::class,
    ];

    protected $dates   = ['canceled_at'];
    private $initiator = [];
    private $tripDriver;

    /*public function getExpectedStartDateAttribute()
    {
        return Carbon::parse($this->attributes['expected_start_time'])->format(constants('api.global.formats.date'));
    }*/

    public function getGoingRideOfTrip()
    {
        return $this->rides->first();
    }

    public function getReturningRideOfTrip()
    {
        return $this->rides->last();
    }

    public function searchTripsByRequest(Request $request, $me)
    {
        event(new RideSearches($request, $me));

        // Cummulative value of gender in request
        $request->merge([
            'cummulative_genders' => User::getBitwiseGenderValueByUserIds(array_merge(
                array_filter(explode(constants('api.separator'), $request->get('invited_members'))),
                [$me->id]
            )),
        ]);

        $rides = collect(self::searchTrips($request, $me));

        # Do processing for round-trip
        if ($request->get('is_roundtrip') == 1 && count($rides)) {
            // $goingRides = $rides->keyBy('trip_id');
            $goingRides = $rides;

            // Search in reverse direction
            $coordinates = $request->only([
                'origin_latitude',
                'origin_longitude',
                'destination_latitude',
                'destination_longitude',
                'date_returning',
            ]);
            $request->merge([
                'origin_latitude'       => $coordinates['destination_latitude'],
                'origin_longitude'      => $coordinates['destination_longitude'],
                'destination_latitude'  => $coordinates['origin_latitude'],
                'destination_longitude' => $coordinates['origin_longitude'],
                'expected_start_date'   => $coordinates['date_returning'],
            ]);

            $returningRides = collect(self::searchTrips($request, $me))->keyBy('trip_id');

            // Debugging purpose
            Log::debug('searching for returning', [$goingRides->keyBy('trip_id')->keys()->toArray(), $returningRides->keys()->toArray()]);

            // $entireTripData is to display ride data so that frontend developers can apply filter on their end.
            $rides = $entireTripData = [];

            // Here we including rides with same trip only
            foreach ($returningRides as $tripId => $ride) {

                // Validating round-trip for same trip_id (parent)
                $matchForSameTrip = $goingRides->filter(function($item, $key) use($tripId) {
                    return $item->trip_id == $tripId;
                });

                foreach ($matchForSameTrip as $goingRideId => $goingRideForSameTrip) {

                    // Round-trip matched
                    // Now ensure that ride is going towards desired direction,
                    // i.e {A-to-B}, not {B-to-A}
                    if ($ride->id > $goingRides->get($goingRideId)->id) {
                        $rides[] = $goingRides->get($goingRideId);
                        $entireTripData[$tripId] = [$goingRides->get($goingRideId), $ride];

                        $goingRides->forget($goingRideId);
                        $returningRides->forget($tripId);
                    }

                }
            }

            // Now attempt to show inter-connect rides as a feature
            foreach ($goingRides as $goingRide) {
                foreach ($returningRides as $returningTripId => $returningRide) {

                    Log::debug('round-trip detection', [$returningRide->id, $goingRide->id, $returningRide->time_range, $goingRide->time_range, $returningRide->start_time, $goingRide->start_time, (
                        $returningRide->time_range > $goingRide->time_range &&
                        $returningRide->start_time == $goingRide->start_time
                    ),
                        (
                            $returningRide->start_time > $goingRide->start_time
                        )]);

                    if (
                        // Check for returning trip time should be greater than going ride with same date
                        (
                            $returningRide->time_range > $goingRide->time_range &&
                            $returningRide->start_time == $goingRide->start_time
                        )
                        ||
                        // Else if returning trip date is greater then make a group
                        (
                            $returningRide->start_time > $goingRide->start_time
                        )
                    ) {
                        // Need to add group_id
                        $random = mt_rand(999999, 9999999);

                        $currentGoingRide     = clone $goingRide;
                        $currentReturningRide = clone $returningRide;

                        $currentGoingRide->group_id = $currentReturningRide->group_id = $random;
                        // Log::debug('Debug', [$currentGoingRide->id, $currentReturningRide->id, $random]);
                        $rides[] = $currentGoingRide;
                        $rides[] = $currentReturningRide;
                        $returningRides->forget($returningTripId);
                        break;
                    }
                }
            }

            $rides = collect($rides);
        }

        // dd($rides);

        $allPreferences  = RidePreference::getPreferences();
        $trips           = self::with(['driver'])->whereIn('id', $rides->pluck('trip_id'))->get()->keyBy('id');

        // All matched ride's preferences
        $ridePreferences = TripRideMeta::whereIn('trip_ride_id', $rides->pluck('id'))
            ->select(DB::raw('*, SUBSTR(`key`, 12) as identifier'))
            ->whereRaw(DB::raw("LEFT(`key`, 10) = 'preference'"))
            ->get()
            ->groupBy('trip_ride_id');


        $results = [];
        foreach ($rides as $index => $ride) {

            $rideToAdd              = $trips[$ride->trip_id];
            $rideToAdd->search_ride = $ride;

            $rideMetaPreference = [];

            if ( $ridePreferences->get($ride->id) ) {
                $rideMetaPreference = $ridePreferences->get($ride->id)->pluck('identifier');
            }

            $rideToAdd->preferences = generatePreferencesResponse($allPreferences, $rideMetaPreference);

            if ( isset($entireTripData, $entireTripData[$ride->trip_id][1]) ) {
                $rideToAdd->ride = [
                    // Going ride
                    self::rideDateToSearchFilter( $entireTripData[$ride->trip_id][0] ),

                    // Returning ride
                    self::rideDateToSearchFilter( $entireTripData[$ride->trip_id][1] ),
                ];
            } else {
                $rideToAdd->ride = [ self::rideDateToSearchFilter($ride) ];
            }

            $results[] = clone $rideToAdd;
            // yield $rideToAdd;
        }

        return $results;
    }

    public function searchTrips($payload, User $passenger)
    {
        $origin = [
            'latitude'  => $payload->get('origin_latitude'),
            'longitude' => $payload->get('origin_longitude'),
        ];
        $destination = [
            'latitude'  => $payload->get('destination_latitude'),
            'longitude' => $payload->get('destination_longitude'),
        ];
        $timeRange         = intval($payload->get('time_range'));
        $invitedMembers    = array_filter(explode(constants('api.separator'), $payload->get('invited_members')));
        $preferences       = [];

        // NOTE: Adding preference option in database will affect search result, so make sure to assign new preference to all rides by default.
        if ($payload->has('preferences')) {
            try {
                $preferences = filterPreferencesToSearchFor(json_decode($payload->get('preferences')));
            } catch (Exception $e) {
                throw new InvalidArgumentException('Invalid payload for preferenceses provided.');
            }
        }

        try {
            $expectedStartDate = Carbon::createFromTimestamp(substr($payload->get('expected_start_date'), 0, -3))->format('Y-m-d 00:00:00');
        } catch (Exception $e) {
            throw new InvalidArgumentException('Invalid start date given, please use unix format.');
        }

        if (
            !$origin['latitude'] ||
            !$origin['longitude'] ||
            !$destination['latitude'] ||
            !$destination['longitude']
        ) {
            throw new InvalidArgumentException('Invalid co-ordinates given.');
        }

        $bindings = [
            $origin['longitude'],
            $origin['latitude'],
            $destination['longitude'],
            $destination['latitude'],
            $expectedStartDate,
        ];

        # SQL Joining/Binding/Clauses/Selections
        $joins  = [];
        $wheres = ['1=1'];

        $timeRangeQuery = '1=1';
        if ($timeRange !== 0) {
            $timeRangeQuery = 'tr.time_range & ' . $timeRange;
        }

        # Filter rides by driver's gender when search made by passenger
        if (in_array($payload->get('desired_gender'), [1, 2])) {
            $bindings[] = (intval($payload->get('desired_gender')) === 1) ? 'Male' : 'Female';
            $wheres[]   = "user_id IN ( SELECT user_id FROM user_meta WHERE `key` = 'gender' AND `grouping` = 'profile' AND `value` = ? )";
        }

        # This filteration is for driver's desired gender preferences
        $bindings[] = $payload->get('cummulative_genders'); // modified by searchTripsByRequest method
        $wheres[]   = "tr.desired_gender & ?";

        # Hide self create ride when created as a driver
        if ($passenger) {
            $bindings[] = $passenger->id;
            $wheres[] = 'trips.user_id <> ?';
        }

        # Filter by perferences
        if (is_array($preferences) && count($preferences)) {

            $wherePreferences = [];
            $index            = 1;
            foreach ($preferences as $key => $options) {

                $index++;
                $joins[] = "INNER JOIN trip_ride_meta AS trm{$index} ON tr.id = trm{$index}.trip_ride_id";

                if (is_array($options) && count($options) > 0) {

                    $bindings[] = 'preference_' . $key;

                    # Sorry for being dirty :(
                    $wherePreferences[] = "(trm{$index}.key = ? AND trm{$index}.value IN (" . "'" . implode("', '", array_map('removeQuotes', $options)) . "'" . "))";

                }
            }

            $wheres = array_merge($wheres, $wherePreferences);
        }

        # Filter by driver's ratings
        if ($payload->has('rating') && ($rating = intval($payload->get('rating'))) > 0) {
            $bindings[] = $rating;
            $wheres[]   = "user_id IN ( SELECT user_id FROM user_meta WHERE `key` = 'rating' AND `grouping` = 'driver' AND `value` >= ? )";
        }

        # Available Seats
        $bindings[] = count($invitedMembers) >= 1 ? (count($invitedMembers) + 1) : 1;

        // LOW | TODO: Same user_id search should not be happen.

        # Query conversions
        $joinQuery  = implode(' ', $joins);
        $whereQuery = implode(' AND ', $wheres);

        // LOW | TODO: Order by friends yet to be added.
        $query = "
            SELECT tr.*, tr.start_time

            # Joins
            FROM trip_ride_polygon AS trp
            INNER JOIN trip_ride_routes AS trr ON trr.id = trp.trip_ride_route_id
            INNER JOIN trip_rides AS tr ON tr.id = trr.trip_ride_id
            INNER JOIN trips ON trips.id = tr.trip_id
            {$joinQuery}

            WHERE

            # Match co-ordinates first
            ST_CONTAINS(
              trp.point_polygon,
              ST_GEOMFROMTEXT(CONCAT('POINT(', ?, ' ', ?, ')'))
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
                  ST_GEOMFROMTEXT(CONCAT('POINT(', ?, ' ', ?, ')'))
                ) LIMIT 1
            )

            # Trip start date
            AND tr.start_time >= ?

            # Time-Range
            AND {$timeRangeQuery}

            # Preferences
            AND {$whereQuery}

            # Seats availibility
            AND tr.seats_available >= ?

            # Trip type identification, wether it is request/public-trip
            AND trips.user_id IS NOT NULL
            AND trips.is_request = 0

            # Ignore canceled trips by driver
            AND trips.canceled_at IS NULL

            GROUP BY trp.trip_ride_route_id
        ";

        $results = DB::select(DB::raw($query), $bindings);

        return $results;
    }

    public function searchTripsDriverByRequest(Request $request, $me)
    {
        // Cummulative value of gender in request
        $request->merge([
            'cummulative_genders' => transformGenderStringToInteger($me->getMetaDefault('gender', 3)),
        ]);

        $rides = collect(self::searchTripDriver($request, $me));

        # Do processing for round-trip
        if ($request->get('is_roundtrip') == 1 && count($rides)) {
            $goingRides = $rides;

            // Search in reverse direction
            $coordinates = $request->only([
                'origin_latitude',
                'origin_longitude',
                'destination_latitude',
                'destination_longitude',
                'date_returning',
            ]);
            $request->merge([
                'origin_latitude'       => $coordinates['destination_latitude'],
                'origin_longitude'      => $coordinates['destination_longitude'],
                'destination_latitude'  => $coordinates['origin_latitude'],
                'destination_longitude' => $coordinates['origin_longitude'],
                'expected_start_date'   => $coordinates['date_returning'],
            ]);

            $returningRides = collect(self::searchTripDriver($request, $me))->keyBy('trip_id');

            // Debugging purpose
            Log::debug('searching for returning', [$goingRides->keyBy('trip_id')->keys()->toArray(), $returningRides->keys()->toArray()]);

            // $entireTripData is to display ride data so that frontend developers can apply filter on their end.
            $rides = $entireTripData = [];

            // Here we including rides with same trip only
            foreach ($returningRides as $tripId => $ride) {

                // Validating round-trip for same trip_id (parent)
                $matchForSameTrip = $goingRides->filter(function($item, $key) use($tripId) {
                    return $item->trip_id == $tripId;
                });

                foreach ($matchForSameTrip as $goingRideId => $goingRideForSameTrip) {

                    // Round-trip matched
                    // Now ensure that ride is going towards desired direction,
                    // i.e {A-to-B}, not {B-to-A}
                    if ($ride->id > $goingRides->get($goingRideId)->id) {
                        $rides[] = $goingRides->get($goingRideId);
                        $entireTripData[$tripId] = [$goingRides->get($goingRideId), $ride];

                        $goingRides->forget($goingRideId);
                        $returningRides->forget($tripId);
                    }

                }
            }

            $rides = collect($rides);
        }

        // dd($rides);

        $allPreferences = RidePreference::getPreferences();
        $trips          = self::with('driver')->whereIn('id', $rides->pluck('trip_id'))->get()->keyBy('id');

         // All matched ride's preferences
        $ridePreferences = TripRideMeta::whereIn('trip_ride_id', $rides->pluck('id'))
            ->select(DB::raw('*, SUBSTR(`key`, 12) as identifier'))
            ->whereRaw(DB::raw("LEFT(`key`, 10) = 'preference'"))
            ->get()
            ->groupBy('trip_ride_id');

        $results = [];
        foreach ($rides as $index => $ride) {

            $rideToAdd              = $trips[$ride->trip_id];
            $rideToAdd->search_ride = $ride;

            $rideMetaPreference = [];

            if ( $ridePreferences->get($ride->id) ) {
                $rideMetaPreference = $ridePreferences->get($ride->id)->pluck('identifier');
            }

            $rideToAdd->preferences = generatePreferencesResponse($allPreferences, $rideMetaPreference);

            if ( isset($entireTripData, $entireTripData[$ride->trip_id][1]) ) {
                $rideToAdd->ride = [
                    // Going ride
                    self::rideDateToSearchFilter( $entireTripData[$ride->trip_id][0], 'driver' ),

                    // Returning ride
                    self::rideDateToSearchFilter( $entireTripData[$ride->trip_id][1], 'driver' ),
                ];
            } else {
                $rideToAdd->ride = [ self::rideDateToSearchFilter($ride, 'driver') ];
            }

            $results[] = clone $rideToAdd;
        }

        return $results;
    }

    public function searchTripDriver($payload, User $driver)
    {
        $origin = [
            'latitude'  => $payload->get('origin_latitude'),
            'longitude' => $payload->get('origin_longitude'),
        ];
        $destination = [
            'latitude'  => $payload->get('destination_latitude'),
            'longitude' => $payload->get('destination_longitude'),
        ];
        $timeRange         = intval($payload->get('time_range'));
        $preferences       = [];

        // NOTE: Adding preference option in database will affect search result, so make sure to assign new preference to all rides by default.
        if ($payload->has('preferences')) {
            try {
                $preferences = filterPreferencesToSearchFor(json_decode($payload->get('preferences')));
            } catch (Exception $e) {
                throw new InvalidArgumentException('Invalid payload for preferenceses provided.');
            }
        }

        try {
            $expectedStartDate = Carbon::createFromTimestamp(substr($payload->get('expected_start_date'), 0, -3))->format('Y-m-d 00:00:00');
        } catch (Exception $e) {
            throw new InvalidArgumentException('Invalid start date given, please use unix format.');
        }

        if (
            !$origin['latitude'] ||
            !$origin['longitude'] ||
            !$destination['latitude'] ||
            !$destination['longitude']
        ) {
            throw new InvalidArgumentException('Invalid co-ordinates given.');
        }

        $bindings = [
            $origin['longitude'],
            $origin['latitude'],
            $destination['longitude'],
            $destination['latitude'],
            $expectedStartDate,
        ];

        # SQL Joining/Binding/Clauses/Selections
        $joins  = [];
        $wheres = ['1=1'];

        $timeRangeQuery = '1=1';
        if ($timeRange !== 0) {
            $timeRangeQuery = 'tr.time_range & ' . $timeRange;
        }

        # Filter rides by passengers's gender when search made by driver
        if (in_array($payload->get('desired_gender'), [1, 2])) {
            $bindings[] = (intval($payload->get('desired_gender')) === 1) ? 'Male' : 'Female';
            $wheres[]   = "initiated_by IN ( SELECT user_id FROM user_meta WHERE `key` = 'gender' AND `grouping` = 'profile' AND `value` = ? )";
        }

        # This filteration is for passengers's desired gender preferences
        $bindings[] = $payload->get('cummulative_genders'); // modified by searchTripsDriverByRequest method
        $wheres[]   = "tr.desired_gender & ?";

        # Hide self create ride when created as a passenger
        if ($driver) {
            $bindings[] = $driver->id;
            $wheres[] = 'trips.initiated_by <> ?';
        }

        # Filter by perferences
        if (is_array($preferences) && count($preferences)) {

            $wherePreferences = [];
            $index            = 1;
            foreach ($preferences as $key => $options) {

                $index++;
                $joins[] = "INNER JOIN trip_ride_meta AS trm{$index} ON tr.id = trm{$index}.trip_ride_id";

                if (is_array($options) && count($options) > 0) {

                    $bindings[] = 'preference_' . $key;

                    # Sorry for being dirty :(
                    $wherePreferences[] = "(trm{$index}.key = ? AND trm{$index}.value IN (" . "'" . implode("', '", array_map('removeQuotes', $options)) . "'" . "))";

                }
            }

            $wheres = array_merge($wheres, $wherePreferences);
        }

        # Filter by passenger ratings
        if ($payload->has('rating') && ($rating = intval($payload->get('rating'))) > 0) {
            $bindings[] = $rating;
            $wheres[]   = "initiated_by IN ( SELECT user_id FROM user_meta WHERE `key` = 'rating' AND `grouping` = 'profile' AND `value` = ? )";
        }

        # Filter by roundtrips
        if ($payload->has('is_roundtrip')) {
            $bindings[] = intval($payload->get('is_roundtrip'));
            $wheres[] = "trips.is_roundtrip = ?";
        }

        # Query conversions
        $joinQuery  = implode(' ', $joins);
        $whereQuery = implode(' AND ', $wheres);

        // LOW | TODO: Order by friends yet to be added.
        $query = "
            SELECT tr.*, tr.start_time

            # Joins
            FROM trip_ride_polygon AS trp
            INNER JOIN trip_ride_routes AS trr ON trr.id = trp.trip_ride_route_id
            INNER JOIN trip_rides AS tr ON tr.id = trr.trip_ride_id
            INNER JOIN trips ON trips.id = tr.trip_id
            {$joinQuery}

            WHERE

            # Match co-ordinates first
            ST_CONTAINS(
              trp.point_polygon,
              ST_GEOMFROMTEXT(CONCAT('POINT(', ?, ' ', ?, ')'))
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
                  ST_GEOMFROMTEXT(CONCAT('POINT(', ?, ' ', ?, ')'))
                ) LIMIT 1
            )

            # Trip start date
            AND tr.start_time >= ?

            # Time-Range
            AND {$timeRangeQuery}

            # Preferences
            AND {$whereQuery}

            # Trip type identification, wether it is request/public-trip
            AND trips.initiated_by IS NOT NULL
            AND trips.is_request = 1

            # Ignore canceled trips by passenger
            AND trips.canceled_at IS NULL

            # GROUP BY trp.trip_ride_route_id
            GROUP BY tr.trip_id
        ";

        $results = DB::select(DB::raw($query), $bindings);

        return $results;
    }

    public function createPassengerTripByRequest(Request $request, $me)
    {
        $trip = self::createPassengerTrip($request, $me);

        return $trip;
    }

    public function createPassengerTrip($payload, $me)
    {
        $payload = collect($payload);

        try {

            DB::beginTransaction();

            $originData      = $this->resolveCoordinate($payload->get('origin_latitude'), $payload->get('origin_longitude'), $payload->get('origin_title'));
            $destinationData = $this->resolveCoordinate($payload->get('destination_latitude'), $payload->get('destination_longitude'), $payload->get('destination_title'));
            $invitedMembers  = array_filter(explode(constants('api.separator'), $payload->get('invited_members')));
            $totalPassengers = array_merge([$me->id], $invitedMembers);

            # Date validation
            try {
                $expectedStartDate = Carbon::createFromTimestamp(substr($payload->get('expected_start_date'), 0, -3))->format('Y-m-d 00:00:00');

                if ($payload->has('date_returning')) {
                    $expectedReturnDate = Carbon::createFromTimestamp(substr($payload->get('date_returning'), 0, -3))->format('Y-m-d 00:00:00');
                }
            } catch (Exception $e) {
                throw new InvalidArgumentException('Invalid start date given, please use unix format.');
            }

            if ($payload->has('driver_id') && intval($payload->get('is_roundtrip')) === 1 && intval($payload->get('seats_total_returning')) === 0) {
                throw new InvalidArgumentException('Seats of returning trip is required from driver.');
            }

            // if ($expectedStartDate < rideExpectedStartTime()) {
            //     throw new InvalidArgumentException('You cannot create a ride with backdate time.');
            // }

            # Route validation
            try {
                $steppedRoutes = polylineDecode($payload->get('stepped_route'));
            } catch (Exception $e) {
                $steppedRoutes = [];
            }

            if (empty($steppedRoutes)) {
                throw new InvalidArgumentException('Stepped route is not defined or empty.');
            }

            $params = [
                'trip_name'                => $this->resolveTripName($originData, $destinationData, $payload->get('trip_name')),
                'origin_latitude'          => $originData['latitude'],
                'origin_longitude'         => $originData['longitude'],
                'origin_title'             => $originData['title'],
                'destination_latitude'     => $destinationData['latitude'],
                'destination_longitude'    => $destinationData['longitude'],
                'destination_title'        => $destinationData['title'],
                'expected_distance'        => $payload->get('expected_distance'),
                'expected_distance_format' => $payload->get('expected_distance_format'),
                'expected_duration'        => $payload->get('expected_duration'),
                // 'expected_start_time'      => $expectedStartDate,
                'is_roundtrip'             => $payload->get('is_roundtrip'),
                'is_enabled_booknow'       => $payload->get('is_enabled_booknow'),
                'booknow_price'            => (intval($payload->get('is_enabled_booknow')) == 1) ? $payload->get('booknow_price', 0.00) : 0.00,
                'min_estimates'            => max($payload->get('min_estimates'), 0.50),
                'max_estimates'            => max($payload->get('max_estimates'), 0.50),
                // 'estimates_format'         => $payload->get('estimates_format'),
                'initiated_by'             => $this->getInitiator('initiated_by'),
                'initiated_type'           => $this->getInitiator('initiated_type') ?: '',
            ];

            if ($payload->get('driver_id')) {
                # Seats/Members invitation
                if (intval($payload->get('seats_total')) < (count($totalPassengers))) {
                    throw new InvalidArgumentException('You have invited more than the seats available.');
                }

                self::setTripDriver($payload->get('driver_id'));
                $trip = $this->tripDriver->trips()->create($params);
            } else {
                $params['is_request'] = 1;
                $trip                 = self::create($params);
            }

            $ride = $trip->rides()->create([
                'time_range'            => $payload->get('time_range'),
                'desired_gender'        => $payload->get('desired_gender'),
                'ride_status'           => (array_key_exists('is_request', $params) && $params['is_request'] === 1) ? TripRide::RIDE_STATUS_PENDING : $this->getDefaultRideStatus(),
                'origin_latitude'       => $originData['latitude'],
                'origin_longitude'      => $originData['longitude'],
                'origin_title'          => $originData['title'],
                'origin_city'           => getCityFromLatLng($originData['latitude'], $originData['longitude']),
                'destination_latitude'  => $destinationData['latitude'],
                'destination_longitude' => $destinationData['longitude'],
                'destination_title'     => $destinationData['title'],
                'destination_city'      => getCityFromLatLng($destinationData['latitude'], $destinationData['longitude']),
                'start_time'            => $expectedStartDate,
                'seats_total'           => $payload->get('seats_total', count($totalPassengers)),
                'seats_available'       => $payload->get('seats_total', count($totalPassengers)),
            ]);

            if ($trip->isRoundTrip()) {
                $roundTrip = $trip->rides()->create([
                    'time_range'            => $payload->get('time_range_returning'),
                    'desired_gender'        => $payload->get('desired_gender'),
                    'ride_status'           => (array_key_exists('is_request', $params) && $params['is_request'] === 1) ? TripRide::RIDE_STATUS_PENDING : $this->getDefaultRideStatus(),
                    'origin_latitude'       => $destinationData['latitude'],
                    'origin_longitude'      => $destinationData['longitude'],
                    'origin_title'          => $destinationData['title'],
                    'origin_city'           => getCityFromLatLng($destinationData['latitude'], $destinationData['longitude']),
                    'destination_latitude'  => $originData['latitude'],
                    'destination_longitude' => $originData['longitude'],
                    'destination_title'     => $originData['title'],
                    'destination_city'      => getCityFromLatLng($originData['latitude'], $originData['longitude']),
                    'start_time'            => $expectedReturnDate,
                    'seats_total'           => $payload->get('seats_total_returning', count($totalPassengers)),
                    'seats_available'       => $payload->get('seats_total_returning', count($totalPassengers)),
                ]);
            }

            // Save preferences
            $trip->updatePreferences($payload->get('preferences'));

            // Save routes
            $trip->saveRoute($steppedRoutes);

            if ($this->tripDriver) {
                // Add in offer list if driver exists
                $trip->createEmptyOffer($totalPassengers);
            } else {
                // Add passenger to requested table so that can be extracted details later
                $trip->addRequestedPassengers($totalPassengers);
            }

            // Add leader only
            $trip->attachPassengers([$me->id], [
                'is_confirmed' => 1,
            ]);

            DB::commit();

            event(new TripCreatedByPassenger($trip));

            return $trip;

        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function createPublicTripByRequest(Request $request, $user)
    {
        $trip = self::setTripDriver($user)->createPublicTrip($request);

        return $trip;
    }

    public function createPublicTrip($payload)
    {
        $payload = collect($payload);

        if (!$this->tripDriver instanceof User) {
            throw new UndefinedTripDriver('Internal error: Undefined trip driver.');
        }

        try {

            DB::beginTransaction();

            $originData      = $this->resolveCoordinate($payload->get('origin_latitude'), $payload->get('origin_longitude'), $payload->get('origin_title'));
            $destinationData = $this->resolveCoordinate($payload->get('destination_latitude'), $payload->get('destination_longitude'), $payload->get('destination_title'));
            $invitedMembers  = array_filter(explode(constants('api.separator'), $payload->get('invited_members')));

            # Date validation
            try {
                $expectedStartDate = Carbon::createFromTimestamp(substr($payload->get('expected_start_date'), 0, -3))->format('Y-m-d 00:00:00');

                if ($payload->has('date_returning')) {
                    $expectedReturnDate = Carbon::createFromTimestamp(substr($payload->get('date_returning'), 0, -3))->format('Y-m-d 00:00:00');
                }

            } catch (Exception $e) {
                throw new InvalidArgumentException('Invalid start date given, please use unix format.');
            }

            // Validate if ride exist with same date of driver?
            $this->validateRideDates($this->tripDriver, $expectedStartDate, ($payload->has('date_returning') ? $expectedReturnDate : null));

            // if ($expectedStartDate < rideExpectedStartTime()) {
            //     throw new InvalidArgumentException('You cannot create a ride with backdate time.');
            // }

            # Route validation
            try {
                $steppedRoutes = polylineDecode($payload->get('stepped_route'));
            } catch (Exception $e) {
                $steppedRoutes = [];
            }

            if (empty($steppedRoutes)) {
                throw new InvalidArgumentException('Stepped route is not defined or empty.');
            }

            # Seats/Members invitation
            if (intval($payload->get('seats_total')) < count($invitedMembers)) {
                throw new InvalidArgumentException('You have invited more than the seats available.');
            }

            $bookNowPrice = (intval($payload->get('is_enabled_booknow')) == 1) ?
                (intval($payload->get('is_roundtrip')) == 1 ? ($payload->get('booknow_price', 0.00) / 2) : $payload->get('booknow_price', 0.00))
                : 0.00;

            $trip = $this->tripDriver->trips()->create([
                'trip_name'                => $this->resolveTripName($originData, $destinationData, $payload->get('trip_name')),
                'origin_latitude'          => $originData['latitude'],
                'origin_longitude'         => $originData['longitude'],
                'origin_title'             => $originData['title'],
                'destination_latitude'     => $destinationData['latitude'],
                'destination_longitude'    => $destinationData['longitude'],
                'destination_title'        => $destinationData['title'],
                'expected_distance'        => $payload->get('expected_distance'),
                'expected_distance_format' => $payload->get('expected_distance_format'),
                'expected_duration'        => $payload->get('expected_duration'),
                // 'expected_start_time'   => $expectedStartDate,
                'is_roundtrip'             => $payload->get('is_roundtrip'),
                'is_enabled_booknow'       => $payload->get('is_enabled_booknow'),
                'booknow_price'            => $bookNowPrice,
                'min_estimates'            => max($payload->get('min_estimates'), 0.50),
                'max_estimates'            => max($payload->get('max_estimates'), 0.50),
                // 'estimates_format'      => prefixCurrency(max($payload->get('estimates'), 0.50)),
                'initiated_by'             => $this->getInitiator('initiated_by'),
                'initiated_type'           => $this->getInitiator('initiated_type') ?: '',
                'payout_type'              => $payload->get('payout_type', self::DEFAULT_PAYOUT),
            ]);

            $ride = $trip->rides()->create([
                'time_range'            => $payload->get('time_range'),
                'desired_gender'        => $payload->get('desired_gender'),
                'ride_status'           => $this->getDefaultRideStatus(),
                'origin_latitude'       => $originData['latitude'],
                'origin_longitude'      => $originData['longitude'],
                'origin_title'          => $originData['title'],
                'origin_city'           => getCityFromLatLng($originData['latitude'], $originData['longitude']),
                'destination_latitude'  => $destinationData['latitude'],
                'destination_longitude' => $destinationData['longitude'],
                'destination_title'     => $destinationData['title'],
                'destination_city'      => getCityFromLatLng($destinationData['latitude'], $destinationData['longitude']),
                'start_time'            => $expectedStartDate,
                'seats_total'           => $payload->get('seats_total'),
                'seats_available'       => $payload->get('seats_total'),
            ]);

            if ($trip->isRoundTrip()) {
                // Data for returning trip
                $roundTrip = $trip->rides()->create([
                    'time_range'            => $payload->get('time_range_returning'),
                    'desired_gender'        => $payload->get('desired_gender'),
                    'ride_status'           => $this->getDefaultRideStatus(),
                    'origin_latitude'       => $destinationData['latitude'],
                    'origin_longitude'      => $destinationData['longitude'],
                    'origin_title'          => $destinationData['title'],
                    'origin_city'           => getCityFromLatLng($destinationData['latitude'], $destinationData['longitude']),
                    'destination_latitude'  => $originData['latitude'],
                    'destination_longitude' => $originData['longitude'],
                    'destination_title'     => $originData['title'],
                    'destination_city'      => getCityFromLatLng($originData['latitude'], $originData['longitude']),
                    'start_time'            => $expectedReturnDate,
                    'seats_total'           => $payload->get('seats_total_returning'),
                    'seats_available'       => $payload->get('seats_total_returning'),
                ]);
            }

            // Save preferences
            $trip->updatePreferences($payload->get('preferences'));

            // Save routes
            $trip->saveRoute($steppedRoutes);

            // Add in offer list
            $trip->createEmptyOffer($invitedMembers);

            // Add members
            $trip->attachPassengers($invitedMembers);

            DB::commit();

            event(new TripCreatedByDriver($trip));

            return $trip;

        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function resetTripData()
    {
        $passenger = User::find($this->initiated_by);
        $goingTrip = $this->getGoingRideOfTrip();

        $passengers = [];

        // Get passenger list from going trip
        $passengers = array_merge($passengers, $goingTrip->members->pluck('user_id')->toArray());
        $passengers = array_merge($passengers, $goingTrip->offers()->where('has_accepted', 0)->get()->pluck('user_id')->toArray());

        $request = request();
        $request->merge([
            'trip_name'                => $this->trip_name,
            'origin_latitude'          => $this->origin_latitude,
            'origin_longitude'         => $this->origin_longitude,
            'origin_title'             => $this->origin_title,
            'destination_latitude'     => $this->destination_latitude,
            'destination_longitude'    => $this->destination_longitude,
            'destination_title'        => $this->destination_title,
            'expected_distance'        => $this->expected_distance,
            'expected_distance_format' => $this->expected_distance_format,
            'expected_duration'        => $this->expected_duration,
            'expected_start_date'      => strval(Carbon::parse($goingTrip->start_time)->timestamp) . '000',
            'time_range'               => $goingTrip->time_range,
            'seats_total'              => $goingTrip->seats_total,
            'desired_gender'           => $goingTrip->desired_gender,
            'is_roundtrip'             => $this->isRoundTrip(),
            'is_enabled_booknow'       => $this->isBookNowAvailable(),
            'booknow_price'            => $this->booknow_price,
            'min_estimates'            => $this->min_estimates,
            'max_estimates'            => $this->max_estimates,
            'stepped_route'            => $goingTrip->route->stepped_route,
            'preferences'              => reversePreferencesToJSON($goingTrip),
            'invited_members'          => ($passengers && is_array($passengers)) ? implode(constants('api.separator'), array_unique($passengers)) : '',
        ]);

        if ($this->isRoundTrip()) {
            $returnTrip = $this->getReturningRideOfTrip();

            $request->merge([
                'date_returning'        => strval(Carbon::parse($returnTrip->start_time)->timestamp) . '000',
                'time_range_returning'  => $returnTrip->time_range,
                'seats_total_returning' => $returnTrip->seats_total,
            ]);
        }

        (new self)->setInitiatorTrip($passenger->id, TripMember::TYPE_PASSENGER)
                ->createPassengerTripByRequest($request, $passenger);
    }

    /**
     * List of ride status when driver can cancel trip on.
     *
     * @return array
     */
    public static function statusesOfDriverCanCancelTrip()
    {
        return [
            TripRide::RIDE_STATUS_ACTIVE,
            TripRide::RIDE_STATUS_FILLED,
            TripRide::RIDE_STATUS_CONFIRMED,
        ];
    }

    public function cancelTripByDriver($countCancel = true)
    {
        $members = [];

        # Remove passengers from trip and offers
        foreach ($this->rides as $ride) {

            # Get the list of members to send notification to, event based need to fetch first.
            $members = array_merge($members, $ride->members->pluck('user_id')->toArray());

            $ride->offers()->delete(); // Delete all offers relating to this ride.

            foreach ($ride->members as $tripMember) {

                TripMember::refundAndRemoveElement($ride, $tripMember, false);
            }

            $ride->updateRideStatus(TripRide::RIDE_STATUS_CANCELED, true);
        }

        // Mark trip as canceled.
        $this->markAsCanceled();

        if ($this->initiated_type == 'passenger') {
            try {
                $this->resetTripData();
            } catch (\Exception $e) {
                info('Unable to replicate trip data');
            }
        }

        if ($countCancel) {
            $driver             = $this->driver;
            $currentCancelRides = intval($driver->getMetaMulti(UserMeta::GROUPING_DRIVER)->get('canceled_trips', 0));

            $driver->setMeta(['canceled_trips' => $currentCancelRides + 1], UserMeta::GROUPING_DRIVER);
            $driver->save();
        }

        event(new \App\Events\TripCanceledByDriver($this, $members));
    }

    public function cancelTripByAdmin()
    {
        $members = [];

        # Remove passengers from trip and offers
        foreach ($this->rides as $ride) {

            # Get the list of members to send notification to, event based need to fetch first.
            $members = array_merge($members, $ride->members->pluck('user_id')->toArray());

            $ride->offers()->delete(); // Delete all offers relating to this ride.

            foreach ($ride->members as $tripMember) {

                TripMember::refundAndRemoveElement($ride, $tripMember, false);
            }

            $ride->updateRideStatus(TripRide::RIDE_STATUS_CANCELED, true);
        }

        // Mark trip as canceled.
        $this->markAsCanceled();

        event(new \App\Events\TripCanceledByDriver($this, $members));
    }

    public function affectDriverRating()
    {
        $configs = Setting::extracts([
            'setting.ride_cancellation_count',
            'setting.ride_cancellation_penalty',
        ]);

        if (0 === $configs->get('setting.ride_cancellation_count', 0)) {
            // Disabled
            return 1;
        }

        $driver = $this->driver;

        // Very unusual but handled.
        if (!$driver) {
            return 2;
        }

        $currentCancelRides = intval($driver->getMetaMulti(UserMeta::GROUPING_DRIVER)->get('canceled_trips', 0));

        if ($currentCancelRides < $configs->get('setting.ride_cancellation_count')) {
            return 3;
        }

        $ratings = TripRating::where([
            'ratee_id'   => $driver->id,
            'ratee_type' => TripMember::TYPE_DRIVER,
        ])->get()->pluck('rating')->toArray();

        if (0 === count($ratings)) {
            return 5;
        }

        $deductPercentage    = $configs->get('setting.ride_cancellation_penalty', 0);
        $penalizeRatingValue = (calculateAverageByArray($ratings) * (100 - $deductPercentage) / 100);
        $newRating           = ($penalizeRatingValue * (count($ratings)+1)) - array_sum($ratings);

        $tripRide   = $this->getGoingRideOfTrip();
        $tripRating = $tripRide->ratings()->create([
            'rater_id'   => User::ADMIN_USER_ID,
            'rater_type' => 'system',
            'ratee_id'   => $driver->id,
            'ratee_type' => TripMember::TYPE_DRIVER,
            'rating'     => $newRating,
        ]);

        if ($tripRating) {
            $driver->setMeta([
                'canceled_trips' => max(0, ($currentCancelRides - $configs->get('setting.ride_cancellation_count')))
            ], UserMeta::GROUPING_DRIVER);
            $driver->save();

            event(new \App\Events\TripRated($tripRide, $tripRating, [
                'sendNotification' => false
            ]));

            return true;
        }

        return 4;
    }

    public static function affectPassengerRating(TripRide $tripRide, User $passenger)
    {
        $configs = Setting::extracts([
            'setting.ride_cancellation_count',
            'setting.ride_cancellation_penalty',
        ]);

        if (0 === $configs->get('setting.ride_cancellation_count', 0)) {
            // Admin disabled the functionality from backend
            return 1;
        }

        $currentCancelRides = intval($passenger->getMetaMulti(UserMeta::GROUPING_PROFILE)->get('canceled_trips', 0));

        if ($currentCancelRides < $configs->get('setting.ride_cancellation_count')) {
            // Threshold limit remaining
            return 2;
        }

        $ratings = TripRating::where([
            'ratee_id'   => $passenger->id,
            'ratee_type' => TripMember::TYPE_PASSENGER,
        ])->get()->pluck('rating')->toArray();

        if (0 === count($ratings)) {
            // Cannot penalize since user doesn't have enough rating yet
            return 5;
        }

        $deductPercentage    = $configs->get('setting.ride_cancellation_penalty', 0);
        $penalizeRatingValue = (calculateAverageByArray($ratings) * (100 - $deductPercentage) / 100);
        $newRating           = ($penalizeRatingValue * (count($ratings)+1)) - array_sum($ratings);

        $tripRating = $tripRide->ratings()->create([
            'rater_id'   => User::ADMIN_USER_ID,
            'rater_type' => 'system',
            'ratee_id'   => $passenger->id,
            'ratee_type' => TripMember::TYPE_PASSENGER,
            'rating'     => $newRating,
        ]);

        if ($tripRating) {
            $passenger->setMeta([
                'canceled_trips' => max(0, ($currentCancelRides - $configs->get('setting.ride_cancellation_count')))
            ], UserMeta::GROUPING_PROFILE);
            $passenger->save();

            event(new \App\Events\TripRated($tripRide, $tripRating, [
                'sendNotification' => false
            ]));

            return true;
        }

        return 4;
    }

    public function markAsCanceled()
    {
        $this->canceled_at = Carbon::now();
        $this->save();
    }

    public function getPassengerRecord($user = null)
    {
        $rideIds = $this->rides->pluck('id');
        $tripMembers = TripMember::whereIn('trip_ride_id', $rideIds);

        if ($user) {
            $tripMembers->whereUserId( User::extractUserId($user) );
        }

        return $tripMembers->get();
    }

    /**
     * Update all rides preferences of particular trip
     *
     * @param  array $payload
     * @return void
     */
    public function updatePreferences($payload)
    {
        try {
            $preferences = extractSelectedPreferences(json_decode($payload));
        } catch (Exception $e) {
            $preferences = null;
        }

        foreach ($this->rides as $ride) {
            $ride->updateRidePreferences($preferences);
        }
    }

    public function saveRoute($routeArray)
    {
        if (count($routeArray) > 0) {

            if (!is_array($routeArray) || count($routeArray) == 0) {
                throw new InvalidRouteGiven('Unable to create trip because route is not valid.', 'invalid_route');
            }

            $route = TripRideRoute::optimizeRoute($routeArray);

            // At index 0, save routes exactly same and if round-trip enabled then reverse the route.
            foreach ($this->rides as $index => $ride) {
                $ride->route()->create([
                    'stepped_route' => ($index == 0) ? polylineEncode($route) : polylineEncode(array_reverse($route)),
                ]);
            }
        }
    }

    public function createEmptyOffer($userIds)
    {
        if (is_array($userIds) && count($userIds) > 0) {
            foreach ($this->rides as $ride) {
                $ride->createEmptyOffer($userIds, $this->user_id, $this);

                break; // create offer only for first ride!
            }
        }
    }

    public function attachPassengers($userIds, $extendedObject = array())
    {
        if (is_array($userIds) && count($userIds) > 0) {
            $memberObject = new TripMember([
                'invited_by' => $this->initiated_by,
            ] + $extendedObject);

            foreach ($this->rides as $rideIndex => $ride) {
                $ride->addPassengers($userIds, $memberObject);

                // Save passenger's geo location.
                $geoLocation = [
                    'pickup_latitude'   => $ride->origin_latitude,
                    'pickup_longitude'  => $ride->origin_longitude,
                    'pickup_title'      => $ride->origin_title,
                    'dropoff_latitude'  => $ride->destination_latitude,
                    'dropoff_longitude' => $ride->destination_longitude,
                    'dropoff_title'     => $ride->destination_title,
                ];

                if ( $rideIndex > 0 ) {
                    $geoLocation = [
                        'dropoff_latitude'  => $ride->origin_latitude,
                        'dropoff_longitude' => $ride->origin_longitude,
                        'dropoff_title'     => $ride->origin_title,
                        'pickup_latitude'   => $ride->destination_latitude,
                        'pickup_longitude'  => $ride->destination_longitude,
                        'pickup_title'      => $ride->destination_title,
                    ];
                }

                foreach ($userIds as $userId) {
                    $ride->setMeta('geo.passenger_'.$userId, $geoLocation);
                    $ride->save();
                }
            }
        }
    }

    public function addRequestedPassengers($userIds)
    {
        if (is_array($userIds) && count($userIds) > 0) {
            $ride = $this->rides->first();

            foreach ($userIds as $userId) {
                $ride->requestedMembers()->create([
                    'user_id'      => $userId,
                    'is_leader'    => ($this->getInitiator('initiated_by') == $userId) ? 1 : 0,
                    'has_accepted' => 0,
                ]);
            }
        }
    }

    public function resolveCoordinate($lat, $lng, $title = null)
    {
        if (empty($title)) {

            // Resolved via api or do what-ever you want.
            $title = '';
        }

        $resolvedData = [
            'latitude'  => $lat,
            'longitude' => $lng,
            'title'     => $title,
        ];

        return collect($resolvedData);
    }

    public function resolveTripName($origin, $destination, $tripName = null)
    {
        return empty($tripName) ? trim($origin['title'], '.') . ' to ' . trim($destination['title'], '.') : $tripName;
    }

    public function setInitiatorTrip($userId, $userType)
    {
        $this->initiator = [
            'initiated_by'   => $userId,
            'initiated_type' => $userType,
        ];

        return $this;
    }

    public function getInitiator($key)
    {
        return false !== array_key_exists($key, $this->initiator) ? $this->initiator[$key] : null;
    }

    public function getDefaultRideStatus()
    {
        return TripRide::RIDE_STATUS_ACTIVE;
    }

    public function setTripDriver($user)
    {
        if (!$user instanceof User) {
            $user = User::find($user);
        }

        $this->tripDriver = $user;

        return $this;
    }

    public function validateRideDates($driver, $startDate, $returnDate=null)
    {
        $existingTrips = TripRide::where(function($query) use ($startDate, $returnDate) {
                $query->whereDate('start_time', '=', $startDate);

                if ($returnDate) {
                    $query->orWhereDate('start_time', '=', $returnDate);
                }
            })
            ->notCanceled()
            ->addSelect(DB::raw('*, CONCAT(DATE(`start_time`), \' 00:00:00\') as date_start_time'))
            ->whereIn('ride_status', [
                TripRide::RIDE_STATUS_ACTIVE,
                TripRide::RIDE_STATUS_FILLED,
                TripRide::RIDE_STATUS_CONFIRMED,
                TripRide::RIDE_STATUS_STARTED,
            ])
            ->whereHas('trip', function($query) use ($driver) {
                $query->driverId( User::extractUserId($driver) );
            })
            ->get();

        //if ($existingTrips->count()) {
        //    $firstTrip = $existingTrips->first();

        //    if ($firstTrip->date_start_time == $startDate) {
        //        throw new InvalidArgumentException('Trip with same date already exist, please change your departure date.');
        //    } else if ($returnDate && $firstTrip->date_start_time == $returnDate) {
        //        throw new InvalidArgumentException('Trip with same date already exist, please change your return trip date.');
        //    }
        //}
    }

    public function markAsPublic($save = true)
    {
        $this->is_request = 0;

        if ($save) {
            $this->save();
        }
    }

    public function isBookNowAvailable()
    {
        return (bool) (intval($this->attributes['is_enabled_booknow']) === 1);
    }

    public function isRoundTrip()
    {
        return (bool) (intval($this->attributes['is_roundtrip']) === 1);
    }

    public function isRequest()
    {
        return (bool) (intval($this->attributes['is_request']) === 1);
    }

    public function isCanceled()
    {
        return (bool) ($this->attributes['canceled_at'] !== null);
    }

    /*public function isUpcoming()
    {
        return (bool) ($this->attributes['expected_start_time'] >= rideExpectedStartTime());
    }*/

    public function isDriver($user)
    {
        $user = User::extractUserId($user);

        return intval($this->user_id) === intval($user);
    }

    public function getEstimatesAttribute()
    {
        return prefixCurrency($this->attributes['min_estimates']) . ' - ' . prefixCurrency($this->attributes['max_estimates']);
    }

    public function getStatusTextFormattedAttribute()
    {
        return $this->attributes['is_request'] == '1' ?
            '<span class="label label-success">Private</span>' :
            '<span class="label label-success">Public</span>';
    }

    public function getRoundTripStatusAttribute()
    {
        return $this->attributes['is_roundtrip'] == '1' ?
            '<span class="label label-success">Yes</span>' :
            '<span class="label label-danger">No</span>';
    }

    public function calculateEarning()
    {
        $rideIds = $this->rides()->pluck('id');
        return TripMember::readyToFly()->whereIn('trip_ride_id', $rideIds)->sum('fare');
    }

    public function getEarningObject($column='earning')
    {
        return $this->rides->sum(function($ride) use($column) {
            try {
                return $ride->earning->{$column};
            } catch (\Exception $e) {
                return 0;
            }
        });
    }

    /**
     * Does this trip has a driver associated?
     *
     * @return boolean
     */
    public function hasDriver()
    {
        return (bool) !empty($this->user_id);
    }

    protected function rideDateToSearchFilter($ride, $searchBy='passenger')
    {
        return [
            'id'              => $ride->id,
            'time_range'      => $ride->time_range,
            'ride_status'     => $ride->ride_status,
            'seats_available' => $ride->seats_available,
            'date'            => $ride->start_time,
        ] + ($searchBy === 'driver' ? ['seats_required' => $ride->seats_total] : []);
    }

    /*
     * @Scopes
     */
    /*public function scopeSearchRides($query)
    {
        $query = $query
            ->join('trip_rides', 'trip.id', '=', 'trip_rides.trip_id')
            ->where('expected_start_time', '>=', rideExpectedStartTime());

        return $query;
    }*/

    /*public function scopeUpcoming($query)
    {
        return $query->where('expected_start_time', '>=', rideExpectedStartTime());
    }*/

    public function scopeDriverId($query, $driverId)
    {
        return $query->where('user_id', $driverId);
    }

    /*public function scopeExpectedStartDate($query, $date)
    {
        return $query->whereDate('expected_start_time', $date);
    }

    public function scopeExpectedStartDateGreaterThan($query, $date)
    {
        return $query->whereDate('expected_start_time', '>=', $date);
    }*/

    public function scopeCanceled($query)
    {
        return $query->whereNotNull('canceled_at');
    }

    public function scopeNotCanceled($query)
    {
        return $query->whereNull('canceled_at');
    }

    /*
     * @Relationships
     */
    public function rides()
    {
        return $this->hasMany(TripRide::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function passenger()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }
}
