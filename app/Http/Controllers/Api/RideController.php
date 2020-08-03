<?php
namespace App\Http\Controllers\Api;

use App\Events\OfferAcceptedByDriver;
use App\Events\OfferAcceptedByPassenger;
use App\Events\OfferMadeByDriver;
use App\Events\OfferMadeByPassenger;
use App\Events\OfferRejectedByPassenger;
use App\Events\PassengerBookNow;
use App\Events\PassengerDropoffMarked;
use App\Events\PassengerPickupMarked;
use App\Events\PassengerRemovedFromTrip;
use App\Events\PassengerTripPayment;
use App\Events\TripCanceledByDriver;
use App\Events\TripEnded;
use App\Events\TripMembersUpdated;
use App\Events\TripPickupTimeUpdated;
use App\Events\TripRated;
use App\Events\TripStarted;
use App\Exceptions\RideSeatsExhausted;
use App\Exceptions\UserCanNotJoinRide;
use App\Helpers\RESTAPIHelper;
use App\Helpers\StripeHelper;
use App\Http\Requests\Api\DriverAcceptOffer;
use App\Http\Requests\Api\PostPassengerRideRequest;
use App\Http\Requests\Api\PostPublicRideRequest;
use App\Http\Requests\Api\SearchRideRequest;
use App\Models\Coupon;
use App\Models\PassengerCard;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\TripRating;
use App\Models\TripRide;
use App\Models\TripRideOffer;
use App\Models\TripRideShared;
use App\Models\User;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Mail;
use Ramsey\Uuid\Uuid;
use Validator;

class RideController extends ApiBaseController
{

    public function __construct()
    {
        //
    }

    public function pendingTripRating(Request $request)
    {
        $me = $this->getUserInstance();
        $unratedTripIdsByPassenger = $unRatedTripsByDriver = [];

        // First get the list of past trips for passengers
        $passengerPastTrips = TripRide::whereIn('ride_status', [TripRide::RIDE_STATUS_ENDED, TripRide::RIDE_STATUS_ONE_TRIP_COMPLETED])
            // ->past()
            ->with(['trip.driver'])
            ->whereHas('member', function ($query) use ($me) {
                return $query->memberId($me->id)->confirmed();
            })->groupBy('trip_id')
            ->get()
            ->keyBy('id')
            ;

        if ( $passengerPastTrips->count() ) {
            // Now fetch the list of rated trips
            $ratedTrips = TripRating::select('trip_ride_id')->where([
                    'rater_id'   => $me->id,
                    'rater_type' => TripMember::TYPE_PASSENGER,
                ])
                ->whereIn('trip_ride_id', $passengerPastTrips->pluck('id'))
                ->get();

            // Exclude rated trip from past trips
            $unratedTripIdsByPassenger = $passengerPastTrips->pluck('id')->diff($ratedTrips->pluck('trip_ride_id'));
        }

        // Driver stuff starts here
        $driverPastTrips = TripRide::query()
            ->ended()
            // ->past()
            ->with(['trip', 'members'])
            ->whereHas('trip', function ($query) use ($me) {
                return $query->driverId($me->id);
            })->groupBy('trip_id')
            ->get()
            ->keyBy('id')
            ;

        if ( $driverPastTrips->count() ) {
            // Now fetch the list of past trip members whose rating is pending
            $unRatedTripsByDriver = TripMember::with(['ride', 'user'])
                ->select(['trip_members.trip_ride_id', 'trip_members.user_id'])
                ->whereIn('trip_members.trip_ride_id', $driverPastTrips->pluck('id'))
                ->leftJoin('trip_ratings AS tr', function($join) {
                    $join
                        ->on('trip_members.trip_ride_id', '=', 'tr.trip_ride_id')
                        ->on('trip_members.user_id', '=', 'tr.ratee_id')
                        ->on('rater_id', '=', DB::raw('?'))
                        ->on('rater_type', '=', DB::raw('?'));
                })
                ->whereNull('tr.trip_ride_id')
                ->setBindings(array_merge([$me->id, TripMember::TYPE_DRIVER], $driverPastTrips->pluck('id')->toArray()))
                ->get()
                ;
        }

        $response = [
            'driver'    => [],
            'passenger' => [],
        ];

        foreach ($unratedTripIdsByPassenger as $rideId) {
            $ride = $passengerPastTrips->get($rideId);
            $response['passenger'][] = [
                'trip' => [
                    'trip_name' => $ride->trip->trip_name,
                    'origin_title' => $ride->origin_title,
                    'destination_title' => $ride->destination_title,
                    'start_time' => Carbon::parse($ride->start_time)->format(constants('api.global.formats.datetime')),
                    'date' => $ride->expected_start_date,
                    'trip_id' => $rideId,
                ],
                'user' => $ride->trip->driver ? User::extractUserBasicDetails($ride->trip->driver) : new \stdClass,
            ];
        }

        foreach ($unRatedTripsByDriver as $tripMember) {
            $ride = $driverPastTrips->get($tripMember->trip_ride_id);
            // dd($ride);
            $response['driver'][] = [
                'trip' => [
                    'trip_name' => $ride->trip->trip_name,
                    'origin_title' => $ride->origin_title,
                    'destination_title' => $ride->destination_title,
                    'start_time' => Carbon::parse($ride->start_time)->format(constants('api.global.formats.datetime')),
                    'date' => $ride->expected_start_date,
                    'trip_id' => $tripMember->trip_ride_id,
                ],
                'user' => User::extractUserBasicDetails($tripMember->user),
            ];
        }

        $me->setMeta([
            'pending_rating' => (count($response['driver']) || count($response['passenger'])) ? true : false,
        ]);
        $me->save();

        return RESTAPIHelper::response( $response );
    }

    public function ridePopularity(Request $request)
    {
        $routes = $request->get('route');

        if (!is_array($routes)) {
            return RESTAPIHelper::response('Invalid payload received.', false, 'validation_error');
        }

        $response = [];
        foreach ($routes as $key => $route) {
            $latitudes = $longitudes = [];

            try {
                $points = polylineDecode($route);
            } catch (Exception $e) {
                $points = [];
            }

            if (!is_array($points) || empty($points)) {
                $response[$key] = 0;
                continue;
            }

            foreach ($points as $point) {
                $latitudes[]  = $point['latitude'];
                $longitudes[] = $point['longitude'];
            }

            $polygon = createMultipolyWithBuffersFromPointArray($latitudes, $longitudes, constants('global.ride.point_buffer'), 6);
            // var_dump($polygon);exit;

            DB::statement("SET @g2 = ST_GEOMFROMTEXT('{$polygon}');");

            $results = DB::select(DB::raw("
                SELECT COUNT(1) as matchesCount
                FROM ride_searches AS rs
                WHERE
                # ST_CONTAINS(
                #   @g2
                #   ,
                #   ST_GEOMFROMTEXT(CONCAT('POINT(', rs.origin_longitude, ' ', rs.origin_latitude, ')'))
                # )
                # AND
                ST_CONTAINS(
                  @g2
                  ,
                  ST_GEOMFROMTEXT(CONCAT('POINT(', rs.destination_longitude, ' ', rs.destination_latitude, ')'))
                )
                ;
            "));

            $response['index_' . $key] = $results[0]->matchesCount;
        }

        return RESTAPIHelper::response(['popularity' => $response]);
    }

    public function calculateRideEstimates(Request $request)
    {
        # code...
    }

    public function passengerCreateRide(PostPassengerRideRequest $request, Trip $trip)
    {
        $me = $this->getUserInstance();

        try {
            $parentTrip = $trip
                ->setInitiatorTrip($me->id, TripMember::TYPE_PASSENGER)
                ->createPassengerTripByRequest($request, $me);

            return RESTAPIHelper::response($parentTrip->toArray(), true, 'Trip created successfully.');

        } catch (InvalidArgumentException $e) {
            return RESTAPIHelper::response($e->getMessage(), false, 'validation_error');
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createPublicRide(PostPublicRideRequest $request, Trip $trip)
    {
        $me = $this->getUserInstance();

        if (!$me->hasBankAccount()) {
            return RESTAPIHelper::response('Please add your bank account details in order to continue.', false, 'add_bank_account');
        }

        try {

            $parentTrip = $trip->setInitiatorTrip($me->id, TripMember::TYPE_DRIVER)->createPublicTripByRequest($request, $me);

            return RESTAPIHelper::response($parentTrip->toArray(), true, 'Trip created successfully.');

        } catch (InvalidArgumentException $e) {
            return RESTAPIHelper::response($e->getMessage(), false, 'validation_error');
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function passengerSearchRide(SearchRideRequest $request, Trip $trip)
    {
        $me = $this->getUserInstance();

        try {

            $trips = $trip->searchTripsByRequest($request, $me);
            // dd($trips);

            $result = [];
            foreach ($trips as $trip) {
                $result[] = [
                    'driver'                   => [
                        'user_id'         => $trip->driver->id,
                        'first_name'      => $trip->driver->first_name,
                        'last_name'       => $trip->driver->last_name,
                        'profile_picture' => $trip->driver->profile_picture_auto,
                        'gender'          => $trip->driver->getMetaDefault('gender', ''),
                        'rating'          => $trip->driver->getMetaDefault('rating', 0.0),
                    ],
                    'trip_name'                => $trip->trip_name,
                    'origin_title'             => $trip->search_ride->origin_title,
                    'destination_title'        => $trip->search_ride->destination_title,
                    'trip_id'                  => $trip->search_ride->id,
                    // 'time_range'            => $trip->search_ride->time_range,
                    'start_time'               => $trip->search_ride->start_time,
                    'seats_available'          => $trip->search_ride->seats_available,
                    'seats_total'              => $trip->search_ride->seats_total,
                    'expected_distance'        => $trip->expected_distance,
                    'expected_distance_format' => $trip->expected_distance_format,
                    'min_estimates'            => $trip->min_estimates,
                    'max_estimates'            => $trip->max_estimates,
                    'rides'                    => $trip->ride,
                    'preferences'              => $trip->preferences,
                ] + (isset($trip->search_ride->group_id) ? ['group_id' => $trip->search_ride->group_id] : []);
            }

            return RESTAPIHelper::response(
                $result,
                true,
                count($result) ? 'Listing all possible results.' : 'No drivers found based on your trip criteria. Would you like to be notified when a driver posts a trip that matches your criteria?'
            );

        } catch (Exception $e) {
            throw $e;
        }
    }

    public function passengerSubscribeForRide(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origin_title'          => 'required',
            'origin_latitude'       => 'required',
            'origin_longitude'      => 'required',
            'destination_title'     => 'required',
            'destination_latitude'  => 'required',
            'destination_longitude' => 'required',
            'is_roundtrip'          => 'required',
        ]);

        if ($validator->fails()) {
            return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
        }

        $me = $this->getUserInstance();

        /*
         * @2018-10-18 Start
         * Check if any request exist
         * */
        if($me->hasProcessRequest())
        {
            /*
            * Check if the origin lat and long is in the 50 miles is already exists in the system
            * */
            $checkRequestExist = $me->checkRequestExist($request->get('origin_latitude'), $request->get('origin_longitude'));

            if($checkRequestExist)
            {
                return RESTAPIHelper::response(new \stdClass(), true, "Subscribed for requested ride..");
            }
        }
        /*
         * @2018-10-18 End
         * */

        $me->ridesubscriber()->create([
            'origin_title'          => $request->get('origin_title'),
            'origin_latitude'       => $request->get('origin_latitude'),
            'origin_longitude'      => $request->get('origin_longitude'),
            'destination_title'     => $request->get('destination_title'),
            'destination_latitude'  => $request->get('destination_latitude'),
            'destination_longitude' => $request->get('destination_longitude'),
            'is_roundtrip'          => $request->get('is_roundtrip'),
        ]);

        return RESTAPIHelper::response(new \stdClass, true, 'Subscribed for requested ride.');
    }

    public function passengerShareItinerary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_id'    => 'required',
            'invitee'    => 'required',
            // 'first_name' => 'required|max:100',
            // 'last_name'  => 'required|max:100',
            // 'email'      => 'required|email',
        ]);

        if ($validator->fails()) {
            return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
        }

        try {
            $inviteeData = json_decode($request->get('invitee'), true);

            # Validate invitee format
            foreach ($inviteeData as $friend) {
                $validator = Validator::make($friend, [
                    'first_name' => 'required|max:100',
                    'last_name'  => 'required|max:100',
                    'email'      => 'required|email',
                ], [], [
                    'first_name' => 'first_name in invitee',
                    'last_name'  => 'last_name in invitee',
                    'email'      => 'email in invitee',
                ]);

                if ($validator->fails()) {
                    return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
                }
            }
        } catch (\Exception $e) {
            return RESTAPIHelper::response('Invalid invitee data.', false, 'validation_error');
        }

        $ride = TripRide::with('trip')->find($request->get('trip_id'));
        $me   = $this->getUserInstance();

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        if (!$ride->trip->hasDriver()) {
            return RESTAPIHelper::response('Unable to share this trip because it does not has any driver associated.', false, 'unable_to_send');
        }

        $member = $ride
            ->members()
            ->memberId($me->id)
            ->readyToFly()
            ->first();

        if (!$member) {
            return RESTAPIHelper::response('You cannot share this trip at this moment. Please confirm first.', false, 'unable_to_send');
        }

        foreach ($inviteeData as $friend) {
            // Generate random uuid
            $uid = Uuid::uuid4();

            $tripRideShared = $ride->shareItenerary()->create([
                'id'           => $uid,
                'user_id'      => $me->id,
            ] + array_filter(collect($friend)->only([
                'first_name',
                'last_name',
                'email',
                'mobile',
            ])->toArray()));

            // Custom generated ID doesn't assign back to model so set here to do further processing
            $tripRideShared->id = $uid;

            // Send beautiful email :)
            // $tripRideShared->notify(new \App\Notifications\Api\ShareItinerary($tripRideShared));
            Mail::to($tripRideShared->email)->send(new \App\Mail\ShareItinerary($tripRideShared));
        }

        return RESTAPIHelper::response(new \stdClass, true, 'Trip shared successfully.');
    }

    public function passengerPastTrips(Request $request)
    {
        $latitude  = $request->get('latitude', null);
        $longitude = $request->get('longitude', null);
        $date      = $request->get('date', null);
        $me        = $this->getUserInstance();
        $perPage   = $request->get('limit', constants('api.config.defaultPaginationLimit'));

        // Because of status of trips on ride's level, it became mess to handle ride with status
        // To ensure the integrity here we exclude ids of upcoming trips from past.
        $upcoming = TripRide::notEnded()
            ->notCanceled()
            ->upcoming()
            ->whereHas('member', function ($query) use ($me) {
                return $query->memberId($me->id)->confirmed();
            })
            ->whereHas('trip', function ($query) use ($me) {
                return $query->notCanceled();
            })
            ->get();

        $rides = TripRide::with(['trip.driver', 'trip.passenger', 'member', 'ratings', 'members.user'])
            ->where(function ($query) use ($me) {
                return $query->where(function ($query) {
                    return $query->ended();
                })->orWhere('ride_status', TripRide::RIDE_STATUS_CANCELED)
                ->orWhereHas('member', function ($query) use ($me) {
                    return $query->memberId($me->id)->confirmed()->onlyTrashed();
                });
            })
            ->whereHas('member', function ($query) use ($me) {
                return $query->memberId($me->id)->confirmed()->withTrashed();
            })
            // ->whereNotIn('id', $upcoming->pluck('id')) // Here is the game changer.
            // ->groupBy('trip_id')
            ;

        if (!empty($latitude) && !empty($longitude)) {
            $rides = $rides->destination($latitude, $longitude);
        }

        if ($date) {
            $rides = $rides->endedDate(Carbon::parse($date)->toDateString());
        }

        $rides = $rides->paginate($perPage);

        $records = [];
        if ($rides) {
            foreach ($rides as $rideKey => $ride) {
                // $records[$rideKey]['id']                    = $ride->id;

                // Ride id as trip_ip
                $records[$rideKey]['trip_id']               = $ride->id;
                $records[$rideKey]['trip_name']             = $ride->trip->trip_name;
                $records[$rideKey]['is_roundtrip']          = $ride->trip->is_roundtrip;
                $records[$rideKey]['time_range']            = $ride->time_range;
                $records[$rideKey]['origin_latitude']       = $ride->origin_latitude;
                $records[$rideKey]['origin_longitude']      = $ride->origin_longitude;
                $records[$rideKey]['origin_title']          = $ride->origin_title;
                $records[$rideKey]['destination_latitude']  = $ride->destination_latitude;
                $records[$rideKey]['destination_longitude'] = $ride->destination_longitude;
                $records[$rideKey]['destination_title']     = $ride->destination_title;
                $records[$rideKey]['seats_available']       = $ride->seats_available;
                $records[$rideKey]['seats_total']           = $ride->seats_total;
                $records[$rideKey]['started_at']            = $ride->started_at;
                $records[$rideKey]['ended_at']              = $ride->ended_at;
                $records[$rideKey]['date']                  = $ride->expected_start_date;
                $records[$rideKey]['ride_status']           = $ride->ride_status_text;

                $records[$rideKey]['rating']          = 0;
                $records[$rideKey]['individual_cost'] = 0;

                if ($ride->ratings) {
                    foreach ($ride->ratings as $rating) {
                        if ($rating->isRater($me->id)) {
                            $records[$rideKey]['rating'] = floatval($rating->rating);
                        }
                    }
                }

                if ($ride->member) {
                    $records[$rideKey]['individual_cost'] = $ride->member->fare;
                } else {
                    $records[$rideKey]['ride_status'] = 'cancelled';
                }

                $records[$rideKey]['total_distance'] = $ride->trip->expected_distance;
                $records[$rideKey]['driver']         = User::extractUserBasicDetails($ride->trip->driver);
                $records[$rideKey]['passenger']      = $ride->trip->initiated_type == 'passenger' ? User::extractUserBasicDetails($ride->trip->passenger) : new \stdClass;
                $records[$rideKey]['passengers']     = [];

                if ($ride->members) {
                    foreach ($ride->members as $memberKey => $member) {
                        $records[$rideKey]['passengers'][$memberKey] = array_merge(
                            User::extractUserBasicDetails($member->user),
                            ['is_confirmed' => $member->is_confirmed]
                        );
                    }
                }
            }
        }

        if ($records) {
            return RESTAPIHelper::setPagination($rides)->response($records);
        }

        return RESTAPIHelper::response([], true, 'No trip found!');
    }

    public function passengerUpcomingTrips(Request $request)
    {
        $latitude  = $request->get('latitude', null);
        $longitude = $request->get('longitude', null);
        $date      = $request->get('date', null);
        $me        = $this->getUserInstance();
        $perPage   = $request->get('limit', constants('api.config.defaultPaginationLimit'));

        $rides = TripRide::notEnded()
            ->notCanceled()
            ->upcoming()
            ->with(['trip.driver', 'trip.passenger', 'member', 'members.user'])
            ->whereHas('member', function ($query) use ($me) {
                return $query->memberId($me->id)->confirmed();
            })
            ->whereHas('trip', function ($query) use ($me) {
                return $query->notCanceled();
            })
            ;

        if (!empty($latitude) && !empty($longitude)) {
            $rides = $rides->destination($latitude, $longitude);
        }

        if ($date) {
            $rides = $rides->expectedStartDateGreaterThan(Carbon::parse($date)->toDateString())->orderBy("start_time");
        }

        $rides = $rides->paginate($perPage);

        $records = [];
        if ($rides) {
            foreach ($rides as $rideKey => $ride) {
                // $records[$rideKey]['id']                        = $ride->id;

                // Ride id as trip_ip
                $records[$rideKey]['trip_id']               = $ride->id;
                $records[$rideKey]['trip_name']             = $ride->trip->trip_name;
                $records[$rideKey]['is_roundtrip']          = $ride->trip->is_roundtrip;
                $records[$rideKey]['time_range']            = $ride->time_range;
                $records[$rideKey]['origin_latitude']       = $ride->origin_latitude;
                $records[$rideKey]['origin_longitude']      = $ride->origin_longitude;
                $records[$rideKey]['origin_title']          = $ride->origin_title;
                $records[$rideKey]['destination_latitude']  = $ride->destination_latitude;
                $records[$rideKey]['destination_longitude'] = $ride->destination_longitude;
                $records[$rideKey]['destination_title']     = $ride->destination_title;
                $records[$rideKey]['seats_available']       = $ride->seats_available;
                $records[$rideKey]['seats_total']           = $ride->seats_total;
                $records[$rideKey]['date']                  = $ride->expected_start_date;
                $records[$rideKey]['ride_status']           = $ride->ride_status_text;
                $records[$rideKey]['is_request']            = $ride->trip->is_request;
                $records[$rideKey]['rating']                = $ride->trip->hasDriver() ? $ride->trip->driver->getMetaDefault('rating', 0.0) : 0.0;
                $records[$rideKey]['driver']                = $ride->trip->hasDriver() ? User::extractUserBasicDetails($ride->trip->driver) : new \stdClass;
                $records[$rideKey]['passenger']             = $ride->trip->initiated_type == 'passenger' ? User::extractUserBasicDetails($ride->trip->passenger) : new \stdClass;
                $records[$rideKey]['passengers']            = [];

                if ($ride->members) {
                    foreach ($ride->members as $memberKey => $member) {
                        $records[$rideKey]['passengers'][$memberKey] = array_merge(
                            User::extractUserBasicDetails($member->user),
                            ['is_confirmed' => $member->is_confirmed]
                        );
                    }
                }
            }
        }

        if ($records) {
            return RESTAPIHelper::setPagination($rides)->response($records);
        }

        return RESTAPIHelper::response([], true, 'No trip found!');
    }

    public function passengerRideOffers(Request $request)
    {
        $me = $this->getUserInstance();

        $offers = TripRideOffer::where(
            function ($query) use ($me) {
                $query->fromPassenger($me->id)
                    ->orWhere(function ($query) use ($me) {
                        $query->toPassenger($me->id);
                    });
            })
            ->notAccepted()
            ->with(['ride.trip.driver', 'coupon', 'sender', 'receiver'])
            ->select('trip_ride_offers.*')
            ->leftJoin('trip_members', function($join) use ($me){
                $join
                    ->on('trip_members.trip_ride_id', '=', 'trip_ride_offers.trip_ride_id')
                    ->on(DB::raw('trip_members.user_id IN (SELECT following_id FROM favorites WHERE user_id = '.$me->id.') AND 1'), '=', DB::raw('1'));
            })
            ->whereHas('ride', function ($query) {
                return $query->notEnded()->upcoming()->notCanceled();
            })
            ->groupBy('trip_ride_offers.id')
            ->orderByRaw('count(trip_members.id) desc')
            ->orderByRaw("(SELECT `value` FROM user_meta WHERE user_id = {$me->id} AND `key` = 'rating' AND grouping = 'profile') DESC")
            ->get();

        $records = [];
        if ($offers) {
            foreach ($offers as $offerKey => $offer) {
                $records[$offerKey]['offer_id']              = $offer->id;
                $records[$offerKey]['group_id']              = $offer->group_id;
                $records[$offerKey]['trip_id']               = $offer->ride->id;
                $records[$offerKey]['trip_name']             = $offer->ride->trip->trip_name;
                $records[$offerKey]['min_estimates']         = $offer->ride->trip->min_estimates;
                $records[$offerKey]['max_estimates']         = $offer->ride->trip->max_estimates;
                $records[$offerKey]['time_range']            = $offer->ride->time_range;
                $records[$offerKey]['time_range_returning']  = $offer->ride->time_range_returning;
                $records[$offerKey]['origin_latitude']       = $offer->ride->origin_latitude;
                $records[$offerKey]['origin_longitude']      = $offer->ride->origin_longitude;
                $records[$offerKey]['origin_title']          = $offer->ride->origin_title;
                $records[$offerKey]['destination_latitude']  = $offer->ride->destination_latitude;
                $records[$offerKey]['destination_longitude'] = $offer->ride->destination_longitude;
                $records[$offerKey]['destination_title']     = $offer->ride->destination_title;

                $coupon = isset($offer->coupon) ? $offer->coupon->code : '';

                /*$records[$offerKey]['passenger'] = array_merge(
                User::extractUserBasicDetails($offer->ride->member->user), [
                'is_confirmed'    => $offer->ride->member->is_confirmed,
                'proposed_amount' => $offer->proposed_amount,
                'has_accepted'    => $offer->has_accepted,
                'bags_quantity'   => $offer->bags_quantity,
                'time_range'      => $offer->time_range,
                'promo_code'      => $coupon,
                ]
                );*/

                try {
                    if ($offer->isSender($me->id)) {
                        $records[$offerKey]['driver'] = User::extractUserBasicDetails($offer->receiver);
                    } else {
                        $records[$offerKey]['driver'] = User::extractUserBasicDetails($offer->sender);
                    }
                } catch (Exception $e) {
                    $records[$offerKey]['driver'] = new \stdClass;
                }
            }
        }

        if ($records) {
            return RESTAPIHelper::response($records);
        }

        return RESTAPIHelper::response([], true, 'No offer found!');
    }

    public function passengerOfferDetail(Request $request, $rideId = null)
    {
        $driverId = $request->get('driver_id');

        $ride = TripRide::with(['trip'])->find($rideId);

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        // Receiver should have role of driver
        $driver = User::find($driverId);

        if (!$driver || !$driver->isDriver()) {
            return RESTAPIHelper::response('Invalid driver detected.', false, 'unable_to_process');
        }

        $me = $this->getUserInstance();

        try {
            return RESTAPIHelper::response($ride->detailForDriverPassengerOffer($ride, $me->id, $driverId));
        } catch (InvalidArgumentException $e) {
            return RESTAPIHelper::response($e->getMessage(), false, 'unable_to_process');
        }
    }

    public function getCouponDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            'promo_code' => 'required',
        ]);

        if ($validator->fails()) {
            return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
        }

        if (!$coupon = Coupon::validateCoupon($request->get('promo_code'))) {
            return RESTAPIHelper::response('Invalid promo code.', false, 'invalid_promo');
        }

        $afterDiscount = Coupon::getNetAmountToCharge($coupon, $request->get('amount'));

        return RESTAPIHelper::response([
            'before_discount' => floatval($request->get('amount')),
            'after_discount'  => floatval($afterDiscount),
        ]);
    }

    public function passengerMakeOffer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_id' => 'required',
            'price'   => 'required|numeric|min:0.5',
        ]);

        if ($validator->fails()) {
            return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
        }

        $rideId         = $request->get('trip_id');
        $driverId       = $request->get('driver_id');
        $invitedMembers = array_filter(explode(constants('api.separator'), $request->get('invited_members')));
        $isRoundTrip    = intval($request->get('is_roundtrip', 0));

        $ride = TripRide::with(['trip', 'offers'])->find($rideId);

        if (!$ride || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        if ($ride->isCanceled()) {
            return RESTAPIHelper::response('The request trip has been cancelled.', false, 'not_found');
        }

        // When passenger is making an offer make sure we have every required details
        // seats_available, user_id etc

        // Removed because, passenger can make counter offer on a trip requested where driver is not assigned.
        // Can we start operation on this ride?
        // if ( !$ride->isValidRide() ) {
        //     return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'unable_to_process');
        // }

        if ( !$ride->isActiveRide() ) {
            return RESTAPIHelper::response('This trip has been expired or cancelled.', false, 'unable_to_process');
        }

        // Receiver should have role of driver
        $driver = User::find($driverId);

        if (!$driver || !$driver->isDriver()) {
            return RESTAPIHelper::response('You are sending offer to an unauthorized user.', false, 'unable_to_process');
        }

        // List of passengers
        $passengers = (array) $invitedMembers;

        $me = $this->getUserInstance();

        try {
            $me->canJoinRide();
        } catch (UserCanNotJoinRide $e) {
            return RESTAPIHelper::response($e->getMessage(), false, $e->getResolvedErrorCode());
        }

        // Add current passenger to list
        $passengers[] = $me->id;
        $passengers   = array_unique($passengers);

        // Assign group_id only if passenger is making an offer with invites
        $groupId = count($invitedMembers) ? TripMember::generateUniqueGroupId($ride->trip->id, $passengers) : '';

        foreach ($passengers as $passengerId) {
            $existingOffer = $ride->offers()->hasAnyOfferByPassengerTo($passengerId, $driverId)->first();

            $bagsQuantity = ($me->id == $passengerId) ? intval($request->get('bags')) : 0;

            if ($existingOffer) {

                // Already accept offer?
                if (intval($existingOffer->has_accepted) === 1) {
                    if (($key = array_search($passengerId, $passengers)) !== false) {
                        unset($passengers[$key]);
                    }
                    continue;
                }

                $existingOffer->proposed_amount = $request->get('price');

                if ($request->has('bags')) {
                    $existingOffer->bags_quantity   = $bagsQuantity;
                }

                if ($request->has('time_range')) {
                    $existingOffer->time_range = intval($request->get('time_range'));
                }

                if ($request->has('time_range_returning')) {
                    $existingOffer->time_range_returning = intval($request->get('time_range_returning'));
                }

                if (!empty($groupId)) {
                    $existingOffer->group_id = $groupId;
                }

                if ($request->has('is_roundtrip')) {
                    $existingOffer->is_roundtrip = $isRoundTrip;
                }

                $existingOffer->save();
            } else {
                $ride->offers()->create([
                    'from_user_id'         => $passengerId,
                    'from_user_type'       => TripMember::TYPE_PASSENGER,
                    'to_user_id'           => $driverId,
                    'to_user_type'         => TripMember::TYPE_DRIVER,
                    'proposed_amount'      => $request->get('price'),
                    'bags_quantity'        => $bagsQuantity,
                    'time_range'           => intval($request->get('time_range')),
                    'time_range_returning' => intval($request->get('time_range_returning')),
                    'group_id'             => $groupId,
                    'is_roundtrip'         => $isRoundTrip,
                ]);
            }
        }

        // No invitation found, passenger making single offer for themself only.
        if (count($passengers) == 0) {
            // This was added just to stop making further offers when accepted, but this function
            // also includes invitations so i'm just making sure to throw error to single offer user.
            return RESTAPIHelper::response('This offer has been accepted already.', false, 'unable_to_process');
        }

        event(new OfferMadeByPassenger($ride, $me, $driver, $passengers, $request));

        return RESTAPIHelper::response(new \stdClass, true, 'Offer sent successfully.');
    }

    public function passengerAcceptOffer(Request $request)
    {
        $rideId     = $request->get('trip_id');
        $driverId   = $request->get('driver_id');
        $fareCharge = $request->get('price');

        $ride = TripRide::with('trip')->find($rideId);

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        if ( !$ride->isActiveRide() ) {
            return RESTAPIHelper::response('This trip has been expired or cancel.', false, 'unable_to_process');
        }

        $me     = $this->getUserInstance();
        $driver = User::find($driverId);

        // Receiver should have role of driver
        if (!$driver || !$driver->isDriver()) {
            return RESTAPIHelper::response('You are sending offer to an unauthorized user.', false, 'unable_to_process');
        }

        // Can join own's ride?
        if ( $driver->isSelf($me) ) {
            return RESTAPIHelper::response('You cannot be driver and passenger both on same ride.', false, 'unable_to_process');
        }

        $existingOffer = $ride->offers()->hasAnyOfferByPassengerTo($me->id, $driverId)->first();

        // Already accept offer?
        if ($existingOffer && intval($existingOffer->has_accepted) === 1) {
            return RESTAPIHelper::response('You have already accepted this offer.', false, 'unable_to_process');
        }

        $coupon = null;
        if ($request->has('promo_code') && !$coupon = Coupon::validateCoupon($request->get('promo_code'))) {
            return RESTAPIHelper::response('Invalid promo code.', false, 'invalid_promo');
        }

        if ($existingOffer) {

            if ( !$ride->hasAvailableSeats() ) {
                // $existingOffer->delete();
                return RESTAPIHelper::response('This trip does not have any seats left.', false, 'unable_to_process');
            }

            // Check for is time still same with value in offer table?
            if ($existingOffer->time_range != 0 && (!hasBitValue($ride->time_range, $existingOffer->time_range))) {
                return RESTAPIHelper::response('Unfortunately, your offer has been expired.', false, 'unable_to_process');
            }

            // If is_roundtrip flag is 1 which means, above ride is going and need to verify returning ride.
            if ($existingOffer->is_roundtrip == 1) {
                $returningRide = $existingOffer->ride->getReturningRideOfTrip();

                if ($existingOffer->time_range_returning != 0 && (!hasBitValue($returningRide->time_range, $existingOffer->time_range_returning))) {
                    return RESTAPIHelper::response('Unfortunately, your offer has been expired.', false, 'unable_to_process');
                }
            }

            try {
                $me->canJoinRide();
            } catch (UserCanNotJoinRide $e) {
                return RESTAPIHelper::response($e->getMessage(), false, $e->getResolvedErrorCode());
            }

            $existingOffer->proposed_amount = $request->get('price');
            $existingOffer->has_accepted    = 1;

            if ($request->has('bags')) {
                $existingOffer->bags_quantity   = intval($request->get('bags'));
            }

            if ($request->has('time_range')) {
                $existingOffer->time_range = intval($request->get('time_range'));
            }

            if ($request->has('is_roundtrip')) {
                $existingOffer->is_roundtrip = intval($request->get('is_roundtrip', 0));
            }

            try {
                $ride->makeConfirmRideByPassenger($existingOffer, $me, $driver, $coupon);
            } catch (RideSeatsExhausted $e) {
                return RESTAPIHelper::response('This trip has reached maximum available seats.', false, 'unable_to_process');
            } catch (InvalidArgumentException $e) {
                return RESTAPIHelper::response($e->getMessage(), false, 'unable_to_process');
            }

            $existingOffer->save();

        } else {
            // Offer should exist
            return RESTAPIHelper::response('No offer found for this data to accept, please create an offer first.', false, 'not_allowed');
        }

        event(new OfferAcceptedByPassenger($ride, $me, $driverId, $existingOffer));

        return RESTAPIHelper::response(new \stdClass, true, 'Offer accepted successfully.');
    }

    public function passengerRejectOffer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_id'   => 'required',
            'driver_id' => 'required',
            'price'     => 'required',
        ]);

        if ($validator->fails()) {
            return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
        }

        $rideId     = $request->get('trip_id');
        $driverId   = $request->get('driver_id');
        $fareCharge = $request->get('price');

        $ride = TripRide::with('trip')->find($rideId);

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        $me     = $this->getUserInstance();
        $driver = User::find($driverId);

        // Receiver should have role of driver
        if (!$driver || !$driver->isDriver()) {
            return RESTAPIHelper::response('This driver is not authorized to received ignore update.', false, 'unable_to_process');
        }

        $existingOffer = $ride->offers()->hasAnyOfferByPassengerTo($me->id, $driverId)->first();

        if(!$existingOffer) {
            return RESTAPIHelper::response('No offers found for this driver.', false, 'unable_to_process');
        }

        // Already accept offer?
        if (intval($existingOffer->has_accepted) === 1) {
            return RESTAPIHelper::response('You have already accepted this offer.', false, 'unable_to_process');
        }

        event(new OfferRejectedByPassenger($ride, $me, $driverId, $existingOffer));

        return RESTAPIHelper::response(new \stdClass, true, 'Offer rejected successfully.');
    }

    public function passengerTripPayment(Request $request)
    {
        $rideId = $request->get('trip_id');
        $ride   = TripRide::with('trip.rides')->find($rideId);

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        $me        = $this->getUserInstance();
        $passenger = $ride->members()->confirmed()->memberId($me->id)->first();

        if (!$passenger) {
            return RESTAPIHelper::response('You are not a member of this trip.', false, 'unable_to_process');
        }

        if (!$passenger->isReadyToFly()) {

            if (!$card = $passenger->user->creditCard) {
                return RESTAPIHelper::response('Please add your credit card.', false, 'unable_to_process');
            }

            $payment = false;
            $trip    = $ride->trip;
            $rideIds = $trip->rides->pluck('id');

            $tripIds = TripMember::whereIn('trip_ride_id', $rideIds)
                ->where('user_id', $me->id)
                ->where('payment_status', 0)
                ->confirmed()
                ->get();

            if ( !$tripIds->count() ) {
                return RESTAPIHelper::response('You have already made payment or trip not found.', false, 'unable_to_process');
            }

            $amountToCharge = $tripIds->sum('fare');

            $configs = Setting::extracts([
                'setting.application.transaction_fee',
		'setting.application.transaction_fee_local',
		'setting.application.local_max_distance',
            ]);
            $transactionFee = floatval($configs->get('setting.application.transaction_fee', 0.00));

	    if (floatval($trip->expected_distance) < floatval($configs->get('setting.application.local_max_distance', 0.00))) {
		$transactionFee = floatval($configs->get('setting.application.transaction_fee_local', 0.00));
	    }

	    // No Charge - Shutting down stripe
	    //if ($amountToCharge != 0){
            //    if ($trip->is_roundtrip) {
            //        $payment = StripeHelper::payViaStripe($card->stripe_customer_id, ($amountToCharge + $transactionFee), $trip->id);

            //        if ($payment) {
            //            foreach ($trip->rides as $tripRide) {
            //                $tripRide->members()->memberId($passenger->user_id)->update(['payment_status' => 1]);
            //            }
            //        }
            //    } else {
            //        $payment = StripeHelper::payViaStripe($card->stripe_customer_id, ($amountToCharge + $transactionFee), $trip->id);

            //        if ($payment) {
            //            $passenger->update(['payment_status' => 1]);
            //        }
            //    }
	    //}
	    //else
	    //{
		$passenger->update(['payment_status' => 1]);
	    //}

            if ($payment) {
                $card->transactions()->create([
                    'user_id'          => $passenger->user_id,
                    'stripe_charge_id' => $payment->id,
                    'trip_ride_id'     => $ride->id,
                    'amount'           => $amountToCharge,
                    'transaction_fee'  => $transactionFee,
                    'payload'          => $payment,
                ]);

                event(new PassengerTripPayment($ride, $me, $trip->driver->id));
            } else {
                return RESTAPIHelper::response('Payment processing failed! please verify your card.', false, 'unable_to_process');
            }

        } else {
            return RESTAPIHelper::response('You have already paid for this trip.', false, 'not_allowed');
        }

        return RESTAPIHelper::response(new \stdClass, true, 'Payment transferred successfully.');
    }

    // LOW | TODO: Question: What to do when inviter payment confirms but invitees payment failed, what to do with invitee and inviter?
    public function passengerBookNow(Request $request)
    {
        $rideId         = $request->get('trip_id');
        $driverId       = $request->get('driver_id');
        $invitedMembers = array_filter(explode(constants('api.separator'), $request->get('invited_members')));
        $isRoundTrip    = intval($request->get('is_roundtrip', 0));

        $ride = TripRide::with('trip.rides')->find($rideId);

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        // Can we start operation on this ride?
        //if (!$ride->isValidRide() || !$ride->trip->isBookNowAvailable()) {
        //    return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'unable_to_process');
        //}

        if ( !$ride->isActiveRide() ) {
            return RESTAPIHelper::response('This trip has been expired or cancel.', false, 'unable_to_process');
        }

        // Receiver should have role of driver
        $driver = User::find($driverId);

        if (!$driver || !$driver->isSelf($ride->getDriver())) {
            return RESTAPIHelper::response('You are sending offer to an unauthorized user.', false, 'unable_to_process');
        }

        $me = $this->getUserInstance();

        // Is already a passenger then void any request.
        if ($ride->hasPassenger($me->id)) {
            return RESTAPIHelper::response('You are already part of this trip.', false, 'unable_to_process');
        }

        $coupon = null;
        //if ($request->has('promo_code') && !$coupon = Coupon::validateCoupon($request->get('promo_code'))) {
        //    return RESTAPIHelper::response('Invalid promo code.', false, 'invalid_promo');
        //}

        // List of passengers
        $passengers = (array) $invitedMembers;
        $passengers = array_unique($passengers);

        $allPassengers = array_merge($passengers, [$me->id]);

        try {
            # User level validation
            $me->canJoinRide();
        } catch (UserCanNotJoinRide $e) {
            return RESTAPIHelper::response($e->getMessage(), false, $e->getResolvedErrorCode());
        }

        // Assign group_id only if passenger is making an offer with invites
        $groupId = count($invitedMembers) ? TripMember::generateUniqueGroupId($ride->trip->id, $allPassengers) : '';

        DB::beginTransaction();

        // NOTE: Different amount can be charge from all-passengers because of roundtrip association.
        $amountToCharge = 0;

        $bookNowPrice = $ride->trip->booknow_price;

        // Let it iterate through all rides
        $tripRides = $ride->trip->rides;
        foreach ($tripRides as $rideIndex =>  $roundTripRide) {

            // If passenger does not wants to book roundtrip then dont add them to other-way ride
            if (!$isRoundTrip && $roundTripRide->id !== $ride->id) {
                continue;
            }

            $amountToCharge += $bookNowPrice;

            try {
                // Adding friends if any
                $roundTripRide->addPassengers($allPassengers, new TripMember([
                    'fare'         => $bookNowPrice,
                    'is_confirmed' => 1,
                    'group_id'     => $groupId,
                    'invited_by'   => $me->id,
                ]));

                // Save passenger pickup and dropoff locations
                foreach ($allPassengers as $passengerId) {
                    $geoPayload = [
                        'pickup_latitude'   => $request->get('pickup_latitude', ''),
                        'pickup_longitude'  => $request->get('pickup_longitude', ''),
                        'pickup_title'      => $request->get('pickup_title', ''),
                        'dropoff_latitude'  => $request->get('dropoff_latitude', ''),
                        'dropoff_longitude' => $request->get('dropoff_longitude', ''),
                        'dropoff_title'     => $request->get('dropoff_title', ''),
                    ];

                    if ($rideIndex > 0) {
                        // Inverse the geoPayload
                        $geoPayload = [
                            'pickup_latitude'   => $request->get('dropoff_latitude', ''),
                            'pickup_longitude'  => $request->get('dropoff_longitude', ''),
                            'pickup_title'      => $request->get('dropoff_title', ''),
                            'dropoff_latitude'  => $request->get('pickup_latitude', ''),
                            'dropoff_longitude' => $request->get('pickup_longitude', ''),
                            'dropoff_title'     => $request->get('pickup_title', ''),
                        ];
                    }

                    $roundTripRide->setMeta('geo.passenger_' . $passengerId, $geoPayload);
                    $roundTripRide->save();

                    // Remove any offer for this ride of passenger
                    $roundTripRide->offers()->hasAnyOfferByPassengerTo($passengerId, $driverId)->delete();
                }

            } catch (RideSeatsExhausted $e) {
                DB::rollback();

                return RESTAPIHelper::response('This trip has reached maximum available seats.', false, 'unable_to_process');
            } catch (UserCanNotJoinRide $e) {
                DB::rollback();

                return RESTAPIHelper::response($e->getMessage(), false, 'unable_to_process');
            }

            // Update self passenger record
            $passengerRecord                = $roundTripRide->getPassengerRecord($me->id);
            $passengerRecord->invited_by    = null;
            $passengerRecord->coupon_id     = $coupon ? $coupon->id : null;
            $passengerRecord->bags_quantity = $request->get('bags', 0);
            $passengerRecord->save();
        }

        $ride->changeTimeRangeOfRide(collect([
            'is_roundtrip' => $isRoundTrip,
            'time_range'   => $request->get('time_range', 7), // Maximum time_range if not provided.
            'group_id'     => $groupId,
        ]), $me);

        DB::commit();

        event(new PassengerBookNow($ride, $allPassengers, $amountToCharge));

        return RESTAPIHelper::response(new \stdClass, true, 'Ride booked successfully.');
    }

    public function passengerRideDetail(Request $request, $tripId = null, $message = '')
    {
        $ride = TripRide::with(['trip.driver', 'members'])->find($tripId);

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        $me              = $this->getUserInstance();
        $isMember        = $ride->hasPassenger($me->id);
        $sharedItinerary = $me->tripshared()->whereIn('trip_ride_id', [$ride->id])->get()->pluckMultiple(['first_name', 'last_name', 'email', 'mobile']);

        $response = [
            'trip_id'                  => $ride->id,
            'time_range'               => $ride->time_range,
            'trip_name'                => $ride->trip->trip_name,
            'origin_latitude'          => $ride->origin_latitude,
            'origin_longitude'         => $ride->origin_longitude,
            'origin_title'             => $ride->origin_title,
            'destination_latitude'     => $ride->destination_latitude,
            'destination_longitude'    => $ride->destination_longitude,
            'destination_title'        => $ride->destination_title,
            'seats_available'          => $ride->seats_available,
            'seats_total'              => $ride->seats_total,
            'date'                     => $ride->expected_start_date,
            // 'ride_status'              => $ride->ride_status_text,
            'is_request'               => $ride->trip->is_request,
            'is_roundtrip'             => $ride->trip->is_roundtrip,
            'min_estimates'            => $ride->trip->min_estimates,
            'max_estimates'            => $ride->trip->max_estimates,
            'expected_distance'        => $ride->trip->expected_distance,
            'expected_distance_format' => $ride->trip->expected_distance_format,
            'expected_start_time'      => $ride->start_time,
            'expected_duration'        => $ride->trip->expected_duration,
            'payout_type'              => $ride->trip->payout_type,
            'route_polyline'           => $ride->route->stepped_route,
            'has_initiated_offer'      => (bool) ($ride->offers()->fromPassenger($me->id)->count() > 0),
            'has_made_offer'           => (bool) ($ride->offers()->hasAnyOfferByPassenger($me->id)->count() > 0),
            'is_member'                => $isMember,
            'is_enabled_booknow'       => $ride->trip->is_enabled_booknow,
            'booked_as_roundtrip'      => ($ride->trip->is_roundtrip && $isMember && (count($ride->trip->getPassengerRecord($me)) == 2)) ? true : false,
            'needs_payment'            => false,
            'itinerary_shared'         => $sharedItinerary,
        ];

        if ( $request->get('fetch_return', false) == 'true' ) {
            $returningRide = $ride->getReturningRideOfTrip();
            $response['return_trip'] = ($ride->trip->is_roundtrip && $ride->id !== $returningRide->id) ? $returningRide->toArray() + ['date' => $returningRide->expected_start_date] : new \stdClass;
        }

        try {
            $response['driver'] = array_merge(User::extractUserBasicDetails($ride->trip->driver), [
                'driving_license_no' => $ride->trip->driver->getMetaDefault('driving_license_no', ''),
                'vehicle_id_number'  => $ride->trip->driver->getMetaDefault('vehicle_id_number', ''),
                'vehicle_type'       => $ride->trip->driver->getMetaDefault('vehicle_type', ''),
            ]);
        } catch (Exception $e) {
            $response['driver'] = new \stdClass;
        }

        $response['ride_status'] = $ride->ride_status_text;

        // Switching ride has been removed because we're now handling separate ride independently
        /*// When returning ride is active, return updated information
        if ( $ride->isTimeToSwitchTheRide() ) {
            // Time to set the pointer for the ride to coming trip
            $ride = $ride->getReturningRideOfTrip();
            info(__FUNCTION__ . ' @ Ride switched from ' . $tripId . ' to ' . $ride->id);
        }*/

        $members          = $ride->members->keyBy('user_id');
        $requestedMembers = $ride->requestedMembers->keyBy('user_id');

        $mixedPassengers = array_unique(array_merge($members->keys()->toArray(), $requestedMembers->keys()->toArray()));

        $response['passengers'] = [];
        foreach ($mixedPassengers as $userId) {

            $user              = $members->has($userId) ? $members->get($userId) : $requestedMembers->get($userId);
            $passengerMetaData = $members->has($userId) ? [
                'is_confirmed'     => ($user->is_confirmed && $user->payment_status == 1),
                'has_payment_made' => $user->payment_status == 1,
                'is_dropped'       => !empty($user->dropped_at),
            ] : [
                'is_confirmed'     => false,
                'has_payment_made' => false,
                'is_dropped'       => false,
            ];

            if ( false === $response['needs_payment'] && $me->isSelf($userId) && $response['is_member'] && $members->has($userId) ) {
                $response['needs_payment'] = ($user->is_confirmed && $user->payment_status == 0 && $user->fare > 0.00);
            }

            $response['passengers'][] = array_merge(User::extractUserBasicDetails($user->user), $passengerMetaData);
        }

        return RESTAPIHelper::response($response, true, $message);
    }

    public function driverSearchRide(SearchRideRequest $request, Trip $trip)
    {
        $me = $this->getUserInstance();

        try {

            $trips = $trip->searchTripsDriverByRequest($request, $me);

            $result = [];
            foreach ($trips as $trip) {
                $result[] = [
                    'passenger'                => [
                        'user_id'         => $trip->passenger->id,
                        'first_name'      => $trip->passenger->first_name,
                        'last_name'       => $trip->passenger->last_name,
                        'profile_picture' => $trip->passenger->profile_picture_auto,
                        'gender'          => $trip->passenger->getMetaDefault('gender', ''),
                        'rating'          => $trip->passenger->getMetaDefault('rating', 0.0),
                        'trips_canceled'  => $trip->passenger->getMetaDefault('canceled_trips', 0),
                    ],
                    'trip_name'                => $trip->trip_name,
                    'origin_title'             => $trip->search_ride->origin_title,
                    'destination_title'        => $trip->search_ride->destination_title,
                    'trip_id'                  => $trip->search_ride->id,
                    // 'time_range'            => $trip->search_ride->time_range,
                    'expected_distance'        => $trip->expected_distance,
                    'expected_distance_format' => $trip->expected_distance_format,
                    'min_estimates'            => $trip->min_estimates,
                    'max_estimates'            => $trip->max_estimates,
                    'rides'                    => $trip->ride,
                    'preferences'              => $trip->preferences,
                    'is_request'               => $trip->is_request,
                ];
            }

            return RESTAPIHelper::response(
                $result,
                true,
                count($result) ? 'Listing all possible results.' : 'There are no matches with the selected search criteria. You can modify the search criteria or create a ride.'
            );

        } catch (Exception $e) {
            throw $e;
        }
    }

    public function driverListSubscribedRoutes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origin_latitude'       => 'required',
            'origin_longitude'      => 'required',
            'destination_latitude'  => 'required',
            'destination_longitude' => 'required',
        ]);

        if ($validator->fails()) {
            return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
        }

        $geographicalRequestIds = \App\Models\RideSubscriber::searchNearByRequests($request);
        $result = \App\Models\RideSubscriber::with('user')->whereIn('id', $geographicalRequestIds->pluck('id'))->latest()->get();

        $response = [];
        foreach ($result as $record) {
            $response[] = [
                'id' => $record->id,
                'origin_title' => $record->origin_title,
                'origin_latitude' => $record->origin_latitude,
                'origin_longitude' => $record->origin_longitude,
                'destination_title' => $record->destination_title,
                'destination_latitude' => $record->destination_latitude,
                'destination_longitude' => $record->destination_longitude,
                'is_roundtrip' => (bool) $record->is_roundtrip,
                'passenger' => User::extractUserBasicDetails($record->user),
                'gender' => $record->user->getMetaDefault('gender'),
            ];
        }

        return RESTAPIHelper::response(
            $response,
            true,
            count($response) ? 'Listing all subscribed routes.' : 'No routes subscribed by any passenger.'
        );
    }

    public function driverShareItinerary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_id'    => 'required',
            'invitee'    => 'required',
            // 'first_name' => 'required|max:100',
            // 'last_name'  => 'required|max:100',
            // 'email'      => 'required|email',
        ]);

        if ($validator->fails()) {
            return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
        }

        try {
            $inviteeData = json_decode($request->get('invitee'), true);

            # Validate invitee format
            foreach ($inviteeData as $friend) {
                $validator = Validator::make($friend, [
                    'first_name' => 'required|max:100',
                    'last_name'  => 'required|max:100',
                    'email'      => 'required|email',
                ], [], [
                    'first_name' => 'first_name in invitee',
                    'last_name'  => 'last_name in invitee',
                    'email'      => 'email in invitee',
                ]);

                if ($validator->fails()) {
                    return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
                }
            }
        } catch (\Exception $e) {
            return RESTAPIHelper::response('Invalid invitee data.', false, 'validation_error');
        }

        $ride = TripRide::with('trip')->find($request->get('trip_id'));
        $me   = $this->getUserInstance();

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        if (!$ride->isDriver($me)) {
            return RESTAPIHelper::response('You do not have permission to perform this action.', false, 'not_allowed');
        }

        foreach ($inviteeData as $friend) {
            // Generate random uuid
            $uid = Uuid::uuid4();

            $tripRideShared = $ride->shareItenerary()->create([
                'id'           => $uid,
                'user_id'      => $me->id,
            ] + array_filter(collect($friend)->only([
                'first_name',
                'last_name',
                'email',
                'mobile',
            ])->toArray()));

            // Custom generated ID doesn't assign back to model so set here to do further processing
            $tripRideShared->id = $uid;

            // Send beautiful email :)
            // $tripRideShared->notify(new \App\Notifications\Api\ShareItinerary($tripRideShared));
            Mail::to($tripRideShared->email)->send(new \App\Mail\ShareItinerary($tripRideShared));
        }

        return RESTAPIHelper::response(new \stdClass, true, 'Trip shared successfully.');
    }

    public function driverPastTrips(Request $request)
    {
        $latitude  = $request->get('latitude', null);
        $longitude = $request->get('longitude', null);
        $date      = $request->get('date', null);
        $me        = $this->getUserInstance();
        $perPage   = $request->get('limit', constants('api.config.defaultPaginationLimit'));

        $rides = TripRide::with(['trip.driver', 'members.user'])
            // ->notCanceled()
            ->where(function ($query) {
                return $query->where(function ($query) {
                    return $query->ended();
                })->orWhere('ride_status', TripRide::RIDE_STATUS_CANCELED);
            })
            ->whereHas('trip', function ($query) use ($me) {
                return $query->driverId($me->id);
            });

        if (!empty($latitude) && !empty($longitude)) {
            $rides = $rides->destination($latitude, $longitude);
        }

        if ($date) {
            $rides = $rides->endedDate(Carbon::parse($date)->toDateString());
        }

        $rides = $rides->paginate($perPage);

        $records = [];
        if ($rides) {
            foreach ($rides as $rideKey => $ride) {
                // $records[$rideKey]['id']                    = $ride->id;

                // Ride id as trip_ip
                $records[$rideKey]['trip_id']               = $ride->id;
                $records[$rideKey]['trip_name']             = $ride->trip->trip_name;
                $records[$rideKey]['is_roundtrip']          = $ride->trip->is_roundtrip;
                $records[$rideKey]['time_range']            = $ride->time_range;
                $records[$rideKey]['origin_latitude']       = $ride->origin_latitude;
                $records[$rideKey]['origin_longitude']      = $ride->origin_longitude;
                $records[$rideKey]['origin_title']          = $ride->origin_title;
                $records[$rideKey]['destination_latitude']  = $ride->destination_latitude;
                $records[$rideKey]['destination_longitude'] = $ride->destination_longitude;
                $records[$rideKey]['destination_title']     = $ride->destination_title;
                $records[$rideKey]['seats_available']       = $ride->seats_available;
                $records[$rideKey]['seats_total']           = $ride->seats_total;
                $records[$rideKey]['started_at']            = $ride->started_at;
                $records[$rideKey]['ended_at']              = $ride->ended_at;
                $records[$rideKey]['date']                  = $ride->expected_start_date;
                $records[$rideKey]['ride_status']           = $ride->ride_status_text;
                $records[$rideKey]['total_distance']        = $ride->trip->expected_distance;
                $records[$rideKey]['passengers']            = [];

                if ($ride->members) {
                    foreach ($ride->members as $memberKey => $member) {
                        $records[$rideKey]['passengers'][$memberKey] = array_merge(
                            User::extractUserBasicDetails($member->user),
                            ['is_confirmed' => $member->is_confirmed]
                        );
                    }
                }
            }
        }

        if ($records) {
            return RESTAPIHelper::setPagination($rides)->response($records);
        }

        return RESTAPIHelper::response([], true, 'No trip found!');
    }

    // MEDIUM | TODO: Ongoing ride won't show up when date change to next day of starttime, same goes for passenger maybe
    public function driverUpcomingTrips(Request $request)
    {
        $latitude  = $request->get('latitude', null);
        $longitude = $request->get('longitude', null);
        $date      = $request->get('date', null);
        $me        = $this->getUserInstance();
        $perPage   = $request->get('limit', constants('api.config.defaultPaginationLimit'));

        $rides = TripRide::with(['trip.driver', 'member', 'members'])
            ->notCanceled()
            ->notEnded()
            ->upcoming()
            ->whereHas('trip', function ($query) use ($me) {
                return $query->driverId($me->id)->notCanceled();
            });

        if (!empty($latitude) && !empty($longitude)) {
            $rides = $rides->destination($latitude, $longitude);
        }

        if ($date) {
            $rides = $rides->expectedStartDateGreaterThan(Carbon::parse($date)->toDateString())->orderBy("start_time");
        }

        $rides = $rides->paginate($perPage);

        $records = [];
        if ($rides) {
            foreach ($rides as $rideKey => $ride) {
                // $records[$rideKey]['id']                        = $ride->id;

                // Ride id as trip_ip
                $records[$rideKey]['trip_id']               = $ride->id;
                $records[$rideKey]['trip_name']             = $ride->trip->trip_name;
                $records[$rideKey]['is_roundtrip']          = $ride->trip->is_roundtrip;
                $records[$rideKey]['time_range']            = $ride->time_range;
                $records[$rideKey]['origin_latitude']       = $ride->origin_latitude;
                $records[$rideKey]['origin_longitude']      = $ride->origin_longitude;
                $records[$rideKey]['origin_title']          = $ride->origin_title;
                $records[$rideKey]['destination_latitude']  = $ride->destination_latitude;
                $records[$rideKey]['destination_longitude'] = $ride->destination_longitude;
                $records[$rideKey]['destination_title']     = $ride->destination_title;
                $records[$rideKey]['expected_distance']     = $ride->trip->expected_distance;
                $records[$rideKey]['seats_available']       = $ride->seats_available;
                $records[$rideKey]['seats_total']           = $ride->seats_total;
                $records[$rideKey]['date']                  = $ride->expected_start_date;
                $records[$rideKey]['start_time']            = $ride->start_time;
                $records[$rideKey]['ride_status']           = $ride->ride_status_text;
                $records[$rideKey]['passengers']            = [];

                if ($ride->members) {
                    foreach ($ride->members as $memberKey => $member) {
                        $records[$rideKey]['passengers'][$memberKey] = array_merge(
                            User::extractUserBasicDetails($member->user),
                            ['is_confirmed' => $member->is_confirmed]
                        );
                    }
                }
            }
        }

        if ($records) {
            return RESTAPIHelper::setPagination($rides)->response($records);
        }

        return RESTAPIHelper::response([], true, 'No trip found!');
    }

    public function driverRideOffers(Request $request)
    {
        $me = $this->getUserInstance();

        $offers = TripRideOffer::where(
            function ($query) use ($me) {
                $query->fromDriver($me->id)
                    ->orWhere(
                        function ($query) use ($me) {
                            $query->toDriver($me->id);
                        });
            })
            ->notAccepted()
            ->with(['ride.trip.driver', 'ride.members', 'sender', 'receiver'])
            ->select('trip_ride_offers.*')
            ->leftJoin('trip_members', function($join) use ($me){
                $join
                    ->on('trip_members.trip_ride_id', '=', 'trip_ride_offers.trip_ride_id')
                    ->on(DB::raw('trip_members.user_id IN (SELECT following_id FROM favorites WHERE user_id = '.$me->id.') AND 1'), '=', DB::raw('1'));
            })
            ->whereHas('ride', function ($query) {
                return $query->notEnded()->upcoming()->notCanceled();
            })
            ->groupBy('trip_ride_offers.id')
            ->orderByRaw('count(trip_members.id) desc')
            ->orderByRaw("(SELECT `value` FROM user_meta WHERE user_id = {$me->id} AND `key` = 'rating' AND grouping = 'driver') DESC")
            ->get();

        $records = [];
        if ($offers) {
            foreach ($offers as $offerKey => $offer) {
                $records[$offerKey]['offer_id']              = $offer->id;
                $records[$offerKey]['group_id']              = $offer->group_id;
                $records[$offerKey]['trip_id']               = $offer->ride->id;
                $records[$offerKey]['trip_name']             = $offer->ride->trip->trip_name;
                $records[$offerKey]['min_estimates']         = $offer->ride->trip->min_estimates;
                $records[$offerKey]['max_estimates']         = $offer->ride->trip->max_estimates;
                $records[$offerKey]['time_range']            = $offer->ride->time_range;
                $records[$offerKey]['origin_latitude']       = $offer->ride->origin_latitude;
                $records[$offerKey]['origin_longitude']      = $offer->ride->origin_longitude;
                $records[$offerKey]['origin_title']          = $offer->ride->origin_title;
                $records[$offerKey]['destination_latitude']  = $offer->ride->destination_latitude;
                $records[$offerKey]['destination_longitude'] = $offer->ride->destination_longitude;
                $records[$offerKey]['destination_title']     = $offer->ride->destination_title;

                if ($offer->isSender($me->id)) {
                    $passenger = $offer->receiver;
                } else {
                    $passenger = $offer->sender;
                }

                // Set Passenger's Pickup/Dropoff
                $rideMeta = collect($offer->ride->getMeta()->get('geo.passenger_' . $passenger->id));

                $records[$offerKey]['origin_latitude']       = $rideMeta->get('pickup_latitude', '');
                $records[$offerKey]['origin_longitude']      = $rideMeta->get('pickup_longitude', '');
                $records[$offerKey]['origin_title']          = $rideMeta->get('pickup_title', '');
                $records[$offerKey]['destination_latitude']  = $rideMeta->get('dropoff_latitude', '');
                $records[$offerKey]['destination_longitude'] = $rideMeta->get('dropoff_longitude', '');
                $records[$offerKey]['destination_title']     = $rideMeta->get('dropoff_title', '');

                $coupon = isset($offer->coupon) ? $offer->coupon->code : '';

                $records[$offerKey]['passenger'] = array_merge(
                    User::extractUserBasicDetails($passenger), [
                        'time_range'      => $offer->time_range,
                        'proposed_amount' => $offer->proposed_amount,
                        'has_accepted'    => $offer->has_accepted,
                        'bags_quantity'   => $offer->bags_quantity,
                    ]
                );

                // if ($offer->ride->members) {
                //     foreach ($offer->ride->members as $memberKey => $member) {
                //         if ($member->user_id == $passenger->id) {
                //             $coupon = isset($offer->coupon) ? $offer->coupon->code : '';

                //             $records[$offerKey]['passenger'] = array_merge(
                //                 User::extractUserBasicDetails($member->user), [
                //                     'is_confirmed'    => $member->is_confirmed,
                //                     'proposed_amount' => $offer->proposed_amount,
                //                     'has_accepted'    => $offer->has_accepted,
                //                     'bags_quantity'   => $offer->bags_quantity,
                //                 ]
                //             );
                //         }
                //     }
                // }
            }
        }

        if ($records) {
            return RESTAPIHelper::response($records);
        }

        return RESTAPIHelper::response([], true, 'No offer found!');
    }

    public function driverOfferDetail(Request $request, $rideId = null)
    {
        $passengerId = $request->get('passenger_id');

        $ride = TripRide::with(['trip'])->find($rideId);

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        $me = $this->getUserInstance();

        if (!$me || !$me->isDriver()) {
            return RESTAPIHelper::response('Invalid driver detected.', false, 'unable_to_process');
        }

        try {
            $rideResponse = $ride->detailForDriverPassengerOffer($ride, $passengerId, $me->id);

            // Set Passenger's Pickup/Dropoff
            $rideMeta = collect($ride->getMeta()->get('geo.passenger_' . $passengerId));

            $rideResponse['origin_latitude']       = $rideMeta->get('pickup_latitude', '');
            $rideResponse['origin_longitude']      = $rideMeta->get('pickup_longitude', '');
            $rideResponse['origin_title']          = $rideMeta->get('pickup_title', '');
            $rideResponse['destination_latitude']  = $rideMeta->get('dropoff_latitude', '');
            $rideResponse['destination_longitude'] = $rideMeta->get('dropoff_longitude', '');
            $rideResponse['destination_title']     = $rideMeta->get('dropoff_title', '');

            return RESTAPIHelper::response($rideResponse);

        } catch (InvalidArgumentException $e) {
            return RESTAPIHelper::response($e->getMessage(), false, 'unable_to_process');
        }
    }

    public function driverMakeOffer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_id' => 'required',
            'price'   => 'required|numeric|min:0.5',
        ]);

        if ($validator->fails()) {
            return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
        }

        $rideId              = $request->get('trip_id');
        $seatsTotal          = intval($request->get('seats_total', 0));
        $seatsTotalReturning = intval($request->get('seats_total_returning', 0));

        $ride = TripRide::with('trip')->find($rideId);

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        $me = $this->getUserInstance();

        if (!$me->hasBankAccount()) {
            return RESTAPIHelper::response('Please add your bank account details in order to continue.', false, 'add_bank_account');
        }

        // If trip has driver associated but current user is not the driver of this trip then throw error.
        // \Log::debug('$ride->trip->hasDriver()', [$ride->trip->hasDriver()]);
        // \Log::debug('$ride->isDriver($me)', [$ride->isDriver($me)]);
        if ($ride->trip->hasDriver() && !$ride->isDriver($me)) {
            return RESTAPIHelper::response('You do not have permission to perform this action.', false, 'not_allowed');
        }

        if ( !$ride->isActiveRide() ) {
            return RESTAPIHelper::response('This trip has been expired or cancelled.', false, 'unable_to_process');
        }

        $usersExtractedFromRequest = false;
        $groupId                   = '';

        // If $passengerId is missing it means, driver is initiating offer
        // and it should be send to all grouped-passengers if any.
        if ($request->has('passenger_id')) {
            $passengers = [intval($request->get('passenger_id'))];
        } else {
            $usersExtractedFromRequest = ($ride->trip->hasDriver() === false);
            $passengers                = $ride ? $ride->requestedMembers->pluck('user_id')->toArray() : [];

            // Assign group_id only if passenger made request with invitees (friends)
            $groupId = (count($passengers) > 1) ? TripMember::generateUniqueGroupId($ride->trip->id, $passengers) : '';
        }

        foreach ($passengers as $passengerId) {
            $existingOffer = $ride->offers()->hasAnyOfferByDriverTo($me->id, $passengerId)->first();

            if ($existingOffer) {

                // Already accept offer?
                if (intval($existingOffer->has_accepted) === 1) {
                    return RESTAPIHelper::response('This offer has been accepted already.', false, 'unable_to_process');
                }

                $existingOffer->proposed_amount = $request->get('price');

                if (!empty($groupId)) {
                    $existingOffer->group_id = $groupId;
                }

                if ($request->has('seats_total')) {
                    $existingOffer->seats_total = $seatsTotal;
                }

                if ($request->has('seats_total_returning')) {
                    $existingOffer->seats_total_returning = $seatsTotalReturning;
                }

                if ($request->has('time_range')) {
                    $existingOffer->time_range = $request->get('time_range');
                }

                // This is mainly for passenger's behalf, when this offer will accept by passenger then
                // further processing for adding passenger will be catered accordingly by is_roundtrip flag
                if (true === $usersExtractedFromRequest) {
                    $existingOffer->is_roundtrip = $ride->trip->is_roundtrip;
                }

                $existingOffer->save();
            } else {
                $ride->offers()->create([
                    'from_user_id'          => $me->id,
                    'from_user_type'        => TripMember::TYPE_DRIVER,
                    'to_user_id'            => $passengerId,
                    'to_user_type'          => TripMember::TYPE_PASSENGER,
                    'proposed_amount'       => $request->get('price'),
                    'bags_quantity'         => 0,
                    'time_range'            => $request->get('time_range', 0),
                    'is_roundtrip'          => (true === $usersExtractedFromRequest ? $ride->trip->is_roundtrip : 0),
                    'group_id'              => $groupId,
                    'seats_total'           => $seatsTotal,
                    'seats_total_returning' => $seatsTotalReturning,
                ]);
            }
        }

        event(new OfferMadeByDriver($ride, $me, $passengers));

        return RESTAPIHelper::response(new \stdClass, true, 'Offer sent successfully.');
    }

    public function driverAcceptOffer(DriverAcceptOffer $request)
    {
        $rideId      = intval($request->get('trip_id'));
        $passengerId = intval($request->get('passenger_id'));
        $fareCharge  = $request->get('price');

        $ride = TripRide::with('trip')->find($rideId);

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        $me = $this->getUserInstance();

        if (!$me->hasBankAccount()) {
            return RESTAPIHelper::response('Please add your bank account details in order to continue.', false, 'add_bank_account');
        }

        // If trip has driver associated but current user is not the driver of this trip then throw an error.
        if ($ride->trip->hasDriver() && !$ride->isDriver($me)) {
            return RESTAPIHelper::response('You do not have permission to perform this action.', false, 'not_allowed');
        }

        if ( !$ride->isActiveRide() ) {
            return RESTAPIHelper::response('This trip has been expired or cancelled.', false, 'unable_to_process');
        }

        $existingOffer = $ride->offers()->hasAnyOfferByDriverTo($me->id, $passengerId)->first();

        if ($existingOffer) {

            if ( !$ride->hasAvailableSeats() ) {
                // $existingOffer->delete();
                return RESTAPIHelper::response('This trip does not have any seats left.', false, 'unable_to_process');
            }

            // Already accept offer?
            if (intval($existingOffer->has_accepted) === 1) {
                return RESTAPIHelper::response('This offer has been accepted already.', false, 'unable_to_process');
            }

            $existingOffer->proposed_amount = $request->get('price');

            /*if ($request->has('seats_total')) {
                $existingOffer->seats_total = $seatsTotal;
            }*/

            $existingOffer->save();
        } else {
            // Offer should exist
            return RESTAPIHelper::response('No offer found for this data to accept, please create an offer first.', false, 'not_allowed');
        }

        event(new OfferAcceptedByDriver($ride, $me, $passengerId, $existingOffer));

        return RESTAPIHelper::response(new \stdClass, true, 'Offer has been accepted successfully. Please wait for the final approval from the passenger.');
    }

    public function driverRideDetail(Request $request, $tripId = null)
    {
        $ride = TripRide::with(['trip.driver', 'members'])->find($tripId);

        if (!$ride || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        $me = $this->getUserInstance();

        $sharedItinerary = $me->tripshared()->whereIn('trip_ride_id', [$ride->id])->get()->pluckMultiple(['first_name', 'last_name', 'email', 'mobile']);

        $response = [
            'trip_id'                  => $ride->id,
            'time_range'               => $ride->time_range,
            'trip_name'                => $ride->trip->trip_name,
            'origin_latitude'          => $ride->origin_latitude,
            'origin_longitude'         => $ride->origin_longitude,
            'origin_title'             => $ride->origin_title,
            'destination_latitude'     => $ride->destination_latitude,
            'destination_longitude'    => $ride->destination_longitude,
            'destination_title'        => $ride->destination_title,
            'seats_available'          => $ride->seats_available,
            'seats_total'              => $ride->seats_total,
            'date'                     => $ride->expected_start_date,
            'min_estimates'            => $ride->trip->min_estimates,
            'max_estimates'            => $ride->trip->max_estimates,
            'expected_distance'        => $ride->trip->expected_distance,
            'expected_distance_format' => $ride->trip->expected_distance_format,
            'expected_start_time'      => $ride->start_time,
            'expected_duration'        => $ride->trip->expected_duration,
            'payout_type'              => $ride->trip->payout_type,
            'route_polyline'           => $ride->route->stepped_route,
            'has_initiated_offer'      => (bool) ($ride->offers()->fromDriver($me->id)->count() > 0),
            'has_made_offer'           => (bool) ($ride->offers()->hasAnyOfferByDriver($me->id)->count() > 0),
            'is_driver'                => $ride->trip->isDriver($me->id),
            'is_enabled_booknow'       => $ride->trip->is_enabled_booknow,
            'is_request'               => $ride->trip->is_request,
            'is_roundtrip'             => $ride->trip->is_roundtrip,
            'itinerary_shared'         => $sharedItinerary,
        ];

        if ( $request->get('fetch_return', false) == 'true' ) {
            $returningRide = $ride->getReturningRideOfTrip();
            $response['return_trip'] = ($ride->trip->is_roundtrip && $ride->id !== $returningRide->id) ? $returningRide->toArray() + ['date' => $returningRide->expected_start_date] : new \stdClass;
        }

        try {
            $response['driver'] = User::extractUserBasicDetails($ride->trip->driver);
        } catch (Exception $e) {
            $response['driver'] = new \stdClass;
        }

        // Add passenger object if its a request from passenger.
        if ($ride->trip->isRequest()) {
            $response['passenger'] = User::extractUserBasicDetails($ride->trip->passenger);
        }

        $response['ride_status'] = $ride->ride_status_text;

        // When returning ride is active, return updated information
        if ( $ride->isTimeToSwitchTheRide() ) {
            // Time to set the pointer for the ride to coming trip
            $ride = $ride->getReturningRideOfTrip();
            info(__FUNCTION__ . ' @ Ride switched from ' . $tripId . ' to ' . $ride->id);
        }

        $members          = $ride->members->keyBy('user_id');
        $requestedMembers = $ride->requestedMembers->keyBy('user_id');

        $mixedPassengers = array_unique(array_merge($members->keys()->toArray(), $requestedMembers->keys()->toArray()));

        $response['passengers'] = [];
        foreach ($mixedPassengers as $userId) {

            $user              = $members->has($userId) ? $members->get($userId) : $requestedMembers->get($userId);
            $passengerMetaData = $members->has($userId) ? [
                'fare'             => doubleval($user->getEntireTripFareByMember()),
                'bags_quantity'    => $user->bags_quantity,
                'group_id'         => $user->group_id,
                'is_confirmed'     => $user->is_confirmed,
                'has_payment_made' => ($user->payment_status == 1),
            ] : [
                'fare'             => 0.00,
                'bags_quantity'    => 0,
                'group_id'         => '',
                'is_confirmed'     => false,
                'has_payment_made' => false,
            ];

            $response['passengers'][] = array_merge(User::extractUserBasicDetails($user->user), $passengerMetaData);
        }

        return RESTAPIHelper::response($response);
    }

    public function driverDeletePassenger(Request $request)
    {
        $rideId      = intval($request->get('trip_id'));
        $passengerId = intval($request->get('passenger_id'));

        $ride = TripRide::with('trip')->find($rideId);

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        $me = $this->getUserInstance();

        // If trip has driver associated but current user is not the driver of this trip then throw an error.
        if (!$ride->isDriver($me)) {
            return RESTAPIHelper::response('You do not have permission to perform this action.', false, 'not_allowed');
        }

        $members = $ride
            ->members()
            ->memberId($passengerId)
            ->reservedPassengers()
            ->get();

        if ($members->count()) {

            // Do we need to remove grouped-passengers
            // Here we overwriting $members variable so that all related passengers will be removed
            // And offer will be removed from event-listener
            if (!empty($members->first()->group_id)) {
                $members = $ride
                    ->members()
                    ->groupId($members->first()->group_id)
                    ->get();
            }

            foreach ($members as $member) {
                $member->delete();
                event(new PassengerRemovedFromTrip($ride, $me, $member->user_id));
            }

            event(new TripMembersUpdated($ride));

            return RESTAPIHelper::response(new \stdClass, true, 'Passenger removed successfully.');
        }

        return RESTAPIHelper::response('You do not have permission to perform this action.', false, 'not_allowed');
    }

    public function driverPreScheduleRideTime(Request $request)
    {
        $rideId = intval($request->get('trip_id'));
        $ride   = $requstedRide = TripRide::with('trip')->find($rideId);

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        if ( $ride->isTimeToSwitchTheRide() ) {
            // Time to set the pointer for the ride to coming trip
            $ride = $ride->getReturningRideOfTrip();
            info(__FUNCTION__ . ' @ Ride switched from ' . $request->get('trip_id') . ' to ' . $ride->id);
        }

        if ($ride->hasStarted()) {
            return RESTAPIHelper::response('Trip already started.', false, 'not_found');
        }

        if ($ride->hasEnded()) {
            return RESTAPIHelper::response('Trip already completed.', false, 'not_found');
        }

        $me = $this->getUserInstance();

        // If trip has driver associated but current user is not the driver of this trip then throw an error.
        if (!$ride->isDriver($me)) {
            return RESTAPIHelper::response('You do not have permission to perform this action.', false, 'not_allowed');
        }

        try {
            $pickup_time = $request->get('pickup_time');
            $pickup_time = Carbon::createFromTimestamp($pickup_time / 1000);

            // Append ride time with input time
            $pickup_time = Carbon::parse($ride->start_time)->startOfDay()->format('Y-m-d') . ' ' . $pickup_time->format('H:i:s');

            // if ($pickup_time < Carbon::now()) {
            //     return RESTAPIHelper::response('You must select a time that is greater than the current time.', false, 'unable_to_process');
            // }

        } catch (\Exception $e) {
            return RESTAPIHelper::response('Invalid pickup time.', false, 'unable_to_process');
        }

        $ride->update(['start_time' => $pickup_time]);

        return RESTAPIHelper::response(new \stdClass, true, 'Trip pickup time updated.');
    }

    public function driverScheduleRideTime(Request $request)
    {
        $rideId = intval($request->get('trip_id'));
        $ride   = $requstedRide = TripRide::with('trip')->find($rideId);

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        if ( $ride->isTimeToSwitchTheRide() ) {
            // Time to set the pointer for the ride to coming trip
            $ride = $ride->getReturningRideOfTrip();
            info(__FUNCTION__ . ' @ Ride switched from ' . $request->get('trip_id') . ' to ' . $ride->id);
        }

        if ($ride->hasStarted()) {
            return RESTAPIHelper::response('Trip already started.', false, 'not_found');
        }

        if ($ride->hasEnded()) {
            return RESTAPIHelper::response('Trip already completed.', false, 'not_found');
        }

        $me = $this->getUserInstance();

        // If trip has driver associated but current user is not the driver of this trip then throw an error.
        if (!$ride->isDriver($me)) {
            return RESTAPIHelper::response('You do not have permission to perform this action.', false, 'not_allowed');
        }

        try {
            $pickup_time = $request->get('pickup_time');
            $pickup_time = Carbon::createFromTimestamp($pickup_time / 1000);

            // Append ride time with input time
            $pickup_time = Carbon::parse($ride->start_time)->startOfDay()->format('Y-m-d') . ' ' . $pickup_time->format('H:i:s');

            // if ($pickup_time < Carbon::now()) {
            //     return RESTAPIHelper::response('You must select a time that is greater than the current time.', false, 'unable_to_process');
            // }

        } catch (\Exception $e) {
            return RESTAPIHelper::response('Invalid pickup time.', false, 'unable_to_process');
        }

        $members = $ride->members()->confirmed()->get();

        if ($members->count() != $ride->seats_total) {
            return RESTAPIHelper::response('Currently seats are available.', false, 'unable_to_process');
        }

        $ride->update(['start_time' => $pickup_time]);

        event(new TripPickupTimeUpdated($requstedRide));

        return RESTAPIHelper::response(new \stdClass, true, 'Trip pickup time updated.');
    }

    public function driverScheduleRideTimeForcefully(Request $request)
    {
        $rideId = intval($request->get('trip_id'));
        $ride   = $requstedRide = TripRide::with('trip')->find($rideId);

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        if ( $ride->isTimeToSwitchTheRide() ) {
            // Time to set the pointer for the ride to coming trip
            $ride = $ride->getReturningRideOfTrip();
            info(__FUNCTION__ . ' @ Ride switched from ' . $request->get('trip_id') . ' to ' . $ride->id);
        }

        if ($ride->hasStarted()) {
            return RESTAPIHelper::response('Trip already started.', false, 'not_found');
        }

        if ($ride->hasEnded()) {
            return RESTAPIHelper::response('Trip already completed.', false, 'not_found');
        }

        $me = $this->getUserInstance();

        // If trip has driver associated but current user is not the driver of this trip then throw an error.
        if (!$ride->isDriver($me)) {
            return RESTAPIHelper::response('You do not have permission to perform this action.', false, 'not_allowed');
        }

        try {
            $pickup_time = $request->get('pickup_time');
            $pickup_time = Carbon::createFromTimestamp($pickup_time / 1000);

            // Append ride time with input time
            $pickup_time = Carbon::parse($ride->start_time)->startOfDay()->format('Y-m-d') . ' ' . $pickup_time->format('H:i:s');

            // if ($pickup_time < Carbon::now()) {
            //     return RESTAPIHelper::response('You must select a time that is greater than the current time.', false, 'unable_to_process');
            // }

        } catch (\Exception $e) {
            return RESTAPIHelper::response('Invalid pickup time.', false, 'unable_to_process');
        }

        if (!in_array($ride->ride_status, [TripRide::RIDE_STATUS_ACTIVE, TripRide::RIDE_STATUS_FILLED])) {
            return RESTAPIHelper::response('Time cannot be decide at this stage.', false, 'not_allowed');
        }

        $currentMembers        = $ride->members()->count();
        $ride->seats_available = 0;
        $ride->seats_total     = $currentMembers;
        $ride->save();

        $members = $ride->members()->confirmed()->get();

        if ($members->count() != $ride->seats_total) {
            return RESTAPIHelper::response('There are reserved passengers in this ride, please remove them before you intend to start this ride.', false, 'unable_to_process');
        }

        $ride->update(['start_time' => $pickup_time]);

        event(new TripPickupTimeUpdated($requstedRide));

        return RESTAPIHelper::response(new \stdClass, true, 'Trip pickup time updated.');
    }

    public function driverStartTrip(Request $request)
    {
        $rideId = intval($request->get('trip_id'));
        $ride = $requstedRide = TripRide::with('trip.rides')->find($rideId);

        if ( $ride->isTimeToSwitchTheRide() ) {
            // Time to set the pointer for the ride to coming trip
            $ride = $ride->getReturningRideOfTrip();
            info(__FUNCTION__ . ' @ Ride switched from ' . $rideId . ' to ' . $ride->id);
        }

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        if($ride->trip->isRoundTrip() && $ride->id === $ride->trip->getReturningRideOfTrip()->id && !$ride->trip->getGoingRideOfTrip()->hasEnded())
        {
            return RESTAPIHelper::response('Return trip can not be start first', false, 'not_found');
        }

        if ($ride->hasStarted()) {
            return RESTAPIHelper::response('Trip already started.', false, 'not_found');
        }

        if ($ride->hasEnded()) {
            return RESTAPIHelper::response('Trip already completed.', false, 'not_found');
        }


        $me = $this->getUserInstance();

        // If trip has driver associated but current user is not the driver of this trip then throw an error.
        if (!$ride->isDriver($me)) {
            return RESTAPIHelper::response('You do not have permission to perform this action.', false, 'not_allowed');
        }

        $members = $ride->members()->confirmed()->get();
        if ($members->count() != $ride->seats_total) {
            return RESTAPIHelper::response('Currently seats are available.', false, 'unable_to_process');
        }

        event(new TripStarted($ride, $members));

        // Update ride status and start time for future handling
        $ride->update(['ride_status' => TripRide::RIDE_STATUS_STARTED, 'started_at' => Carbon::now()]);
        $requstedRide->update(['ride_status' => TripRide::RIDE_STATUS_STARTED, 'started_at' => Carbon::now()]);

        $ride->updateRideStatus(TripRide::RIDE_STATUS_STARTED);

        $response          = $ride->rideInProcessResponse($members);
        $response['trip_id'] = $rideId; // Return requested trip_id

        return RESTAPIHelper::response($response, true, 'Trip has been started.');
    }

    /**
     * This method will call when driver wants to continue ride either
     * round-trip ormistakenly closed app or went back during ride
     *
     * @param  Request $request
     */
    public function driverResumeTrip(Request $request)
    {
        $rideId = intval($request->get('trip_id'));
        $ride   = TripRide::with('trip.rides')->find($rideId);

        if ( $ride->isTimeToSwitchTheRide() ) {
            // Time to set the pointer for the ride to coming trip
            $ride = $ride->getReturningRideOfTrip();
            info(__FUNCTION__ . ' @ Ride switched from ' . $request->get('trip_id') . ' to ' . $ride->id);
        }

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        $me = $this->getUserInstance();

        // If trip has driver associated but current user is not the driver of this trip then throw an error.
        if (!$ride->isDriver($me)) {
            return RESTAPIHelper::response('You do not have permission to perform this action.', false, 'not_allowed');
        }

        if ($ride->hasEnded()) {
            return RESTAPIHelper::response('Trip already completed.', false, 'not_found');
        }

        $members = $ride->members()->confirmed()->get();

        if ($members->count() != $ride->seats_total) {
            return RESTAPIHelper::response('Currently seats are available.', false, 'unable_to_process');
        }

        $response = $ride->rideInProcessResponse($members);
        $response['trip_id'] = $rideId; // Return requested trip_id

        return RESTAPIHelper::response($response, true);
    }

    public function driverMarkPickup(Request $request)
    {
        $rideId = intval($request->get('trip_id'));
        $ride   = TripRide::with('trip')->find($rideId);

        if ( $ride->isTimeToSwitchTheRide() ) {
            // Time to set the pointer for the ride to coming trip
            $ride = $ride->getReturningRideOfTrip();
            info(__FUNCTION__ . ' @ Ride switched from ' . $request->get('trip_id') . ' to ' . $ride->id);
        }

        $passengerIds = array_filter(explode(constants('api.separator'), $request->get('passenger_ids')));

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        if ($ride->hasEnded()) {
            return RESTAPIHelper::response('Trip already completed.', false, 'not_found');
        }

        if (!count($passengerIds)) {
            return RESTAPIHelper::response('Invalid passengers ids.', false, 'not_found');
        }

        $me = $this->getUserInstance();

        // If trip has driver associated but current user is not the driver of this trip then throw an error.
        if (!$ride->isDriver($me)) {
            return RESTAPIHelper::response('You do not have permission to perform this action.', false, 'not_allowed');
        }

        $members = $ride->members()->whereIn('user_id', $passengerIds)->confirmed()->get();

        if (!$members->count()) {
            return RESTAPIHelper::response('Passenger not found in this ride.', false, 'not_found');
        }

        foreach ($members as $member) {
            $member->update(['picked_at' => Carbon::now()]);
        }

        event(new PassengerPickupMarked($ride, $members));

        $response = $ride->rideInProcessResponse($ride->members()->confirmed()->get());
        $response['trip_id'] = $rideId; // Return requested trip_id

        return RESTAPIHelper::response($response, true, 'Pickup has been marked.');
    }

    public function driverMarkDropoff(Request $request)
    {
        $rideId      = intval($request->get('trip_id'));
        $coordinates = trim($request->get('coordinates'));
        $ride        = TripRide::with('trip')->find($rideId);

        if ( $ride->isTimeToSwitchTheRide() ) {
            // Time to set the pointer for the ride to coming trip
            $ride = $ride->getReturningRideOfTrip();
            info(__FUNCTION__ . ' @ Ride switched from ' . $request->get('trip_id') . ' to ' . $ride->id);
        }

        $passengerIds = array_filter(explode(constants('api.separator'), $request->get('passenger_ids')));

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        if ($ride->hasEnded()) {
            return RESTAPIHelper::response('Trip already completed.', false, 'not_found');
        }

        if (!count($passengerIds)) {
            return RESTAPIHelper::response('Invalid passengers ids.', false, 'not_found');
        }

        $me = $this->getUserInstance();

        // If trip has driver associated but current user is not the driver of this trip then throw an error.
        if (!$ride->isDriver($me)) {
            return RESTAPIHelper::response('You do not have permission to perform this action.', false, 'not_allowed');
        }

        $members = $ride->members()->whereIn('user_id', $passengerIds)->confirmed()->get();

        if (!$members->count()) {
            return RESTAPIHelper::response('Passenger not found in this ride.', false, 'not_found');
        }

        foreach ($members as $member) {
            $member->update(['dropped_at' => Carbon::now()]);
        }

        event(new PassengerDropoffMarked($ride, $members, $coordinates));

        $response = $ride->rideInProcessResponse($ride->members()->confirmed()->get());
        $response['trip_id'] = $rideId; // Return requested trip_id

        return RESTAPIHelper::response($response, true, 'Dropoff has been marked.');
    }

    public function driverEndTrip(Request $request)
    {
        $rideId = intval($request->get('trip_id'));
        $ride   = $requstedRide = TripRide::with('trip.rides')->find($rideId);

        if ( $ride->isTimeToSwitchTheRide() ) {
            // Time to set the pointer for the ride to coming trip
            $ride = $ride->getReturningRideOfTrip();
            info(__FUNCTION__ . ' @ Ride switched from ' . $request->get('trip_id') . ' to ' . $ride->id);
        }

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        if (!$ride->hasStarted()) {
            return RESTAPIHelper::response('Trip not started yet.', false, 'not_found');
        }

        if ($ride->hasEnded()) {
            return RESTAPIHelper::response('Trip already completed.', false, 'not_found');
        }

        $me = $this->getUserInstance();

        // If trip has driver associated but current user is not the driver of this trip then throw an error.
        if (!$ride->isDriver($me)) {
            return RESTAPIHelper::response('You do not have permission to perform this action.', false, 'not_allowed');
        }

        $rideStatus = TripRide::RIDE_STATUS_ENDED;

        // Update ride's status auto-switched
        $ride->update(['ride_status' => $rideStatus, 'ended_at' => Carbon::now()]);
        $ride->updateRideStatus($rideStatus);

        $returnRide = $ride->getReturningRideOfTrip();

        // Make other (returning trip filled so that time can be selected)
        /*if ($ride->trip->isRoundTrip() && $returnRide->id != $ride->id) {
            $returnRide->updateRideStatus(TripRide::RIDE_STATUS_FILLED);
        }*/

        /*if ( $ride->trip->isRoundTrip() && false === $ride->isReturningRideOfTrip() ) {
            $rideStatus = TripRide::RIDE_STATUS_ONE_TRIP_COMPLETED;
        }

        // Update actual ride's status
        $requstedRide->update(['ride_status' => $rideStatus, 'ended_at' => Carbon::now()]);*/

        $driverEarning = 0;
        $tripRides = $ride->trip->rides;

        foreach ($tripRides as $roundTripRide) {
            $rideEarning = $roundTripRide->members()->readyToFly()->sum('fare');
            $driverEarning = $driverEarning + $rideEarning;
        }

        $ride->trip->update(['earned_by_driver' => $driverEarning]);

        event(new TripEnded($ride, $me));

        return RESTAPIHelper::response(new \stdClass, true, 'Trip has been completed.');
    }

    public function passengerRateTrip(Request $request, $rideId)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|between:1,5',
        ]);

        if ($validator->fails()) {
            return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
        }

        $ride = TripRide::with('trip')->find($rideId);
        $me   = $this->getUserInstance();

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        /*$myRating = $ride->ratings()->where([
            'rater_id'   => $me->id,
            'rater_type' => TripMember::TYPE_PASSENGER,
        ])->first();

        if ( $myRating && $ride->isTimeToSwitchTheRide() ) {
            // Time to set the pointer for the ride to coming trip
            $ride = $ride->getReturningRideOfTrip();
            info(__FUNCTION__ . ' @ Ride switched from ' . $rideId . ' to ' . $ride->id);
        }*/

        if (!$ride->hasEnded()) {
            return RESTAPIHelper::response('Trip must be completed before you rate.', false, 'not_found');
        }

        $member = $ride->members()->where('user_id', $me->id)->confirmed()->first();

        if (!$member) {
            return RESTAPIHelper::response('You do not have permission to perform this action.', false, 'not_allowed');
        }

        $myRating = $ride->ratings()->where([
            'rater_id'   => $me->id,
            'rater_type' => TripMember::TYPE_PASSENGER,
        ])->first();

        if ($myRating) {
            return RESTAPIHelper::response('You have already rated this trip.', false, 'not_allowed');
        }

        $tripRating = $ride->saveRatingByPassenger($me, $ride->trip->driver, $request);
        event(new TripRated($ride, $tripRating));

        return RESTAPIHelper::response(new \stdClass, true, 'Rating has been saved.');
    }

    /**
     * Rate multiple trips at a time by passenger
     * @param  Illuminate\Http\Request $request

     * @return json
     */
    public function passengerRateDrivers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rating_data' => 'required',
        ]);

        if ($validator->fails()) {
            return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
        }

        $me = $this->getUserInstance();

        try {
            $ratingData = json_decode($request->get('rating_data'));
        } catch (\Exception $e) {
            return RESTAPIHelper::response('Invalid rating data.', false, 'validation_error');
        }

        if ( !is_array($ratingData) ) {
            return RESTAPIHelper::response('Invalid rating data.', false, 'validation_error');
        }

        foreach ($ratingData as $data) {
            if (
                property_exists($data, 'user_id') &&
                property_exists($data, 'trip_id') &&
                property_exists($data, 'rating') &&
                $data->user_id &&
                $data->trip_id
            ) {
                $ride = TripRide::with('trip')->find($data->trip_id);

                if (!$ride || $ride->isCanceled() || !$ride->trip) {
                    return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
                }

                /*$myRating = $ride->ratings()->where([
                    'rater_id'   => $me->id,
                    'rater_type' => TripMember::TYPE_PASSENGER,
                ])->first();

                if ( $myRating && $ride->isTimeToSwitchTheRide() ) {
                    // Time to set the pointer for the ride to coming trip
                    $ride = $ride->getReturningRideOfTrip();
                    info(__FUNCTION__ . ' @ Ride switched from ' . $rideId . ' to ' . $ride->id);
                }*/

                if (null == $ride->started_at) {
                    return RESTAPIHelper::response('Trip is not started yet, please wait for the trip to start.', false, 'not_found');
                }

                $member = $ride->members()->where('user_id', $me->id)->confirmed()->first();

                if (!$member) {
                    return RESTAPIHelper::response('You do not have permission to perform this action.', false, 'not_allowed');
                }

                $myRating = $ride->ratings()->where([
                    'rater_id'   => $me->id,
                    'rater_type' => TripMember::TYPE_PASSENGER,
                ])->first();

                if (!$myRating) {
                    $tripRating = $ride->saveRatingByPassenger($me, $ride->trip->driver, collect([
                        'rating' => $data->rating,
                        'feedback' => $data->feedback,
                    ]));
                    event(new TripRated($ride, $tripRating));
                }

            } else {
                return RESTAPIHelper::response('Invalid rating data.', false, 'validation_error');
            }
        }

        return RESTAPIHelper::response(new \stdClass, true, 'Rating has been saved.');
    }

    /**
     * Rate one passenger at a time
     * @param  Illuminate\Http\Request $request
     * @param  $rideId
     * @return json
     */
    public function driverRateTrip(Request $request, $rideId)
    {
        $validator = Validator::make($request->all(), [
            'passenger_id' => 'required|exists:users,id',
            'rating'       => 'required|integer|between:1,5',
        ]);

        if ($validator->fails()) {
            return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
        }

        $ride      = TripRide::with('trip')->find($rideId);
        $passenger = User::find($request->get('passenger_id'));
        $me        = $this->getUserInstance();

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        /*$myRating = $ride->ratings()->where([
            'rater_id'   => $me->id,
            'rater_type' => TripMember::TYPE_DRIVER,
            'ratee_id'   => $passenger->id,
            'ratee_type' => TripMember::TYPE_PASSENGER,
        ])->first();

        if ( $myRating && $ride->isTimeToSwitchTheRide() ) {
            // Time to set the pointer for the ride to coming trip
            $ride = $ride->getReturningRideOfTrip();
            info(__FUNCTION__ . ' @ Ride switched from ' . $rideId . ' to ' . $ride->id);
        }*/

        if (!$ride->hasEnded()) {
            return RESTAPIHelper::response('Trip must be completed before you rate.', false, 'not_found');
        }

        // If trip has driver associated but current user is not the driver of this trip then throw an error.
        if (!$ride->isDriver($me)) {
            return RESTAPIHelper::response('You do not have permission to perform this action.', false, 'not_allowed');
        }

        $member = $ride->members()->where('user_id', $passenger->id)->confirmed()->first();

        if (!$member) {
            return RESTAPIHelper::response('Passenger not found.', false, 'not_found');
        }

        $myRating = $ride->ratings()->where([
            'rater_id'   => $me->id,
            'rater_type' => TripMember::TYPE_DRIVER,
            'ratee_id'   => $passenger->id,
            'ratee_type' => TripMember::TYPE_PASSENGER,
        ])->first();

        if ($myRating) {
            return RESTAPIHelper::response('You have already rated this trip.', false, 'not_allowed');
        }

        $tripRating = $ride->saveRatingByDriver($me, $passenger, $request);
        event(new TripRated($ride, $tripRating));

        return RESTAPIHelper::response(new \stdClass, true, 'Rating has been saved.');
    }

    /**
     * Rate multiple passengers at a time
     * @param  Illuminate\Http\Request $request

     * @return json
     */
    public function driverRateTripMembers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rating_data' => 'required',
        ]);

        if ($validator->fails()) {
            return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
        }

        $me = $this->getUserInstance();

        try {
            $ratingData = json_decode($request->get('rating_data'));
        } catch (\Exception $e) {
            return RESTAPIHelper::response('Invalid rating data.', false, 'validation_error');
        }

        if ( !is_array($ratingData) ) {
            return RESTAPIHelper::response('Invalid rating data.', false, 'validation_error');
        }

        foreach ($ratingData as $data) {
            if (
                property_exists($data, 'user_id') &&
                property_exists($data, 'trip_id') &&
                property_exists($data, 'rating') &&
                $data->user_id &&
                $data->trip_id
            ) {
                $ride = TripRide::with('trip')->find($data->trip_id);

                if (!$ride || $ride->isCanceled() || !$ride->trip) {
                    return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
                }

                if (!$ride->hasEnded()) {
                    return RESTAPIHelper::response('Trip must be completed before you rate.', false, 'not_found');
                }

                // If trip has driver associated but current user is not the driver of this trip then throw an error.
                if (!$ride->isDriver($me)) {
                    return RESTAPIHelper::response('You do not have permission to perform this action.', false, 'not_allowed');
                }

                $member = $ride->members()->where('user_id', $data->user_id)->confirmed()->first();

                if ($member) {
                    $myRating = $ride->ratings()->where([
                        'rater_id'   => $me->id,
                        'rater_type' => TripMember::TYPE_DRIVER,
                        'ratee_id'   => $member->user_id,
                        'ratee_type' => TripMember::TYPE_PASSENGER,
                    ])->first();

                    if (!$myRating) {
                        $tripRating = $ride->saveRatingByDriver($me, $member->user, $data->rating ?: null, $data->feedback ?: null);
                        event(new TripRated($ride, $tripRating));
                    }
                }
            } else {
                return RESTAPIHelper::response('Invalid rating data.', false, 'validation_error');
            }
        }

        return RESTAPIHelper::response(new \stdClass, true, 'Rating has been saved.');
    }

    public function driverUpdateSeats(Request $request, $rideId)
    {
        $validator = Validator::make($request->all(), [
            'seats' => 'required|integer|min:1|max:8',
        ]);

        if ($validator->fails()) {
            return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
        }

        $ride = TripRide::with('trip')->find($rideId);

        if (!$ride || $ride->isCanceled()) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        $me = $this->getUserInstance();

        if (!$ride->isDriver($me)) {
            return RESTAPIHelper::response('You do not have permission to perform this action.', false, 'not_allowed');
        }

        if (!in_array($ride->ride_status, [TripRide::RIDE_STATUS_PENDING, TripRide::RIDE_STATUS_ACTIVE])) {
            return RESTAPIHelper::response('Seats cannot be updated on this stage.', false, 'not_allowed');
        }

        $newSeatsAllotted      = intval($request->get('seats'));
        $currentMembers        = $ride->members()->count();

        if ($currentMembers > $newSeatsAllotted) {
            $message =  $currentMembers . ($currentMembers > 1 ? ' passengers are already' : ' passenger is already') . ' in this ride. It cannot be reduced further.';
            return RESTAPIHelper::response( $message , false, 'not_allowed');
        }

        $updatedAvailable      = $newSeatsAllotted - $currentMembers;
        $ride->seats_available = $updatedAvailable;
        $ride->seats_total     = $newSeatsAllotted;
        $ride->save();

        // No need to handle multiple ride since we're saving and dealing independently
        // Let it iterate through all rides
        /*$tripRides = $ride->trip->rides;

        $multiplyBy                    = ($ride->trip->isRoundTrip() ? 2 : 1);
        $totalMembers                  = TripMember::whereIn('trip_ride_id', $tripRides->pluck('id') )->count();
        $newSeatsAllottedForEntireTrip = ($newSeatsAllotted * $multiplyBy);

        if ($totalMembers > $newSeatsAllottedForEntireTrip) {
            $membersCount = $ride->members()->count();
            $message =  $membersCount . ($membersCount > 1 ? ' passengers are already' : ' passenger is already') . ' in this ride. It cannot be reduced further.';
            return RESTAPIHelper::response( $message , false, 'not_allowed');
        }

        foreach ($tripRides as $roundTripRide) {
            $currentMembers = $roundTripRide->members()->count();

            $roundTripRide->seats_total     = $newSeatsAllotted;
            $updatedAvailable               = $roundTripRide->seats_total - $currentMembers;
            $roundTripRide->seats_available = $updatedAvailable;
            $roundTripRide->save();
        }*/

        // This is not a payment but it also a part of trip completion
        event(new PassengerTripPayment(TripRide::with('trip')->find($rideId), $me, $me->id));

        return RESTAPIHelper::response(new \stdClass, true, 'Seats have been updated successfully.');
    }

    public function passengerInsertCreditCard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_token'  => 'required',
            'last_digits' => 'required',
        ], [
            'last_digits.required' => 'Last 4 digits are required.',
        ]);

        if ($validator->fails()) {
            return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
        }

        $me             = $this->getUserInstance();
        $stripeCustomer = StripeHelper::createCustomerFromToken($me, $request->get('card_token'));

        if (!$stripeCustomer) {
            return RESTAPIHelper::response('Invalid card token.', false, 'validation_error');
        }

        $card = $me->creditCards()->create([
            'stripe_customer_id' => $stripeCustomer->id,
            'card_token'         => $request->get('card_token'),
            'last_digits'        => $request->get('last_digits'),
            'active'             => 1,
            'is_default'         => $me->creditCard()->count() ? 0 : 1,
        ]);

        return RESTAPIHelper::response($card, true, 'Card has been saved successfully.');
    }

    public function passengerGetCreditCard(Request $request)
    {
        $me    = $this->getUserInstance();
        $cards = $me->creditCards()->active()->get();

        return RESTAPIHelper::response($cards->pluckMultiple(['id', 'last_digits', 'is_default']));
    }

    public function passengerSetDefaultCreditCard(Request $request)
    {
        $me   = $this->getUserInstance();
        $card = $me->creditCards()->whereId($request->get('card_id'))->active()->first();

        if ( !$card ) {
            return RESTAPIHelper::response('No card found.', false, 'not_found');
        }

        $me->creditCards()->active()->update(['is_default' => 0]);

        $card = PassengerCard::find($card->id);
        $card->is_default = 1;
        $card->save();

        return RESTAPIHelper::response(new \stdClass, true, 'Card has been set to default.');
    }

    public function passengerRemoveCreditCard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
        }

        $me   = $this->getUserInstance();
        $card = $me->creditCards()->whereId($request->get('card_id'))->active()->first();

        if (!$card) {
            return RESTAPIHelper::response('No card found.', false, 'not_found');
        }

        if ( $card->isDefault() ) {
            // return RESTAPIHelper::response('You cannot delete default card.', false, 'not_found');

            $newDefaultCard = $me->creditCards()->active()->first();

            if ($newDefaultCard) {
                // Make any active card to default
                $newDefaultCard->is_default = 1;
                $newDefaultCard->save();
            }
        }

        $card->delete();

        return RESTAPIHelper::response(new \stdClass, true, 'Card has been deleted.');
    }

    public function passengerPaymentHistory(Request $request)
    {
        $perPage = $request->get('limit', constants('api.config.defaultPaginationLimit'));
        $me      = $this->getUserInstance();

        $transactions = $me
            ->transactions()
            ->latest()
            ->paginate($perPage);

        // Load all required data
        $transactions->load([
            'ride.trip',
            'card' => function($query) {
                return $query->withTrashed();
            }
        ]);

        $records = [];
        foreach ($transactions->items() as $transaction) {
            $records[] = [
                'trip_name'         => $transaction->ride->trip->trip_name,
                'trip_id'           => $transaction->ride->id,
                'origin_title'      => $transaction->ride->origin_title,
                'destination_title' => $transaction->ride->destination_title,
                'last_digits'       => $transaction->card->last_digits,
                'amount'            => $transaction->amount,
                'transaction_fee'   => $transaction->transaction_fee,
                'payment_datetime'  => $transaction->created_at->format(constants('api.global.formats.datetime')),
                'unix_timestamp'    => $transaction->created_at->format('U'),
                'rfc_2822'          => $transaction->created_at->format('r'),
                'iso_8601'          => $transaction->created_at->format('c'),
            ];
        }

        return RESTAPIHelper::setPagination($transactions)->response($records);
    }

    public function driverPaymentHistory(Request $request)
    {
        $perPage = $request->get('limit', constants('api.config.defaultPaginationLimit'));
        $me      = $this->getUserInstance();

        // $trip_ids     = TripRide::ended()->whereIn('trip_id', Trip::whereUserId($me->id)->pluck('id'))->pluck('id');
        // $transactions = TripMember::whereIn('trip_ride_id', $trip_ids)
        //     ->groupBy('trip_ride_id')
        //     ->addSelect(DB::raw('*, SUM(fare) as total, TRUNCATE(SUM(fare * '.(constants('global.ride.driver_earning')/100).'), 2) as earning'))
        //     ->readyToFly()
        //     ->latest()
        //     ->paginate($perPage);

        $transactions = $me->earnings()->paginate($perPage);

        // Load all required data
        $transactions->load([
            'ride.trip'
        ]);

        $records = [];
        foreach ($transactions->items() as $transaction) {
            $records[] = [
                'trip_name'         => $transaction->ride->trip->trip_name,
                'trip_id'           => $transaction->ride->id,
                'origin_title'      => $transaction->ride->origin_title,
                'destination_title' => $transaction->ride->destination_title,
                'amount'            => $transaction->gross_amount,
                'earning'           => $transaction->earning,
                // 'payment_datetime'  => $transaction->created_at->format(constants('api.global.formats.datetime')),
                // 'unix_timestamp'    => $transaction->created_at->format('U'),
                // 'rfc_2822'          => $transaction->created_at->format('r'),
                // 'iso_8601'          => $transaction->created_at->format('c'),
            ];
        }

        return RESTAPIHelper::setPagination($transactions)->response($records);
    }

    public function passengerCancelTrip(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_id' => 'required',
        ]);

        if ($validator->fails()) {
            return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
        }

        $ride = TripRide::with('trip')->find($request->get('trip_id'));
        $me   = $this->getUserInstance();

        if (!$ride || $ride->isCanceled() || !$ride->trip) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        if (!$ride->trip->isRequest()) {

//            foreach ($ride->trip->rides as $roundTripRide) {
//                if ($roundTripRide->hasStarted()) {
//                    return RESTAPIHelper::response('You cannot leave this ride at this stage.', false, 'validation_error');
//                }
//            }

            $member = $ride
                ->members()
                ->memberId($me->id)
                ->first();

            if (!$member) {
                return RESTAPIHelper::response('You are not a part of this trip.', false, 'validation_error');
            }

            TripMember::doCancelTripByPassenger($ride, $member);

            event(new TripMembersUpdated($ride));

        } else {
            if ( false === $me->isSelf($ride->trip->initiated_by) ) {
                return RESTAPIHelper::response('Sorry, you dont own this trip.', false, 'validation_error');
            }

            $ride->trip->delete();
            return RESTAPIHelper::response(new \stdClass, true, 'Trip cancelled successfully.');
        }

        return $this->passengerRideDetail($request, $request->get('trip_id'), 'Trip left successfully.');
    }

    public function driverCancelTrip(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_id' => 'required',
        ]);

        if ($validator->fails()) {
            return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
        }

        $ride = TripRide::with('trip.rides')->find($request->get('trip_id'));

        if (!$ride || $ride->isCanceled()) {
            return RESTAPIHelper::response('Unable to get details of the trip. Please try again.', false, 'not_found');
        }

        $trip = $ride->trip;
        $me   = $this->getUserInstance();

        if (!$ride->isDriver($me)) {
            return RESTAPIHelper::response('You do not have permission to perform this action.', false, 'not_allowed');
        }

        if ($trip->isCanceled()) {
            return RESTAPIHelper::response('This trip is already marked as cancelled.', false, 'not_allowed');
        }

        // Restrict user to perform action on this stage.
        if (!in_array($ride->ride_status, Trip::statusesOfDriverCanCancelTrip())) {
            return RESTAPIHelper::response('You cannot cancel this trip at this stage.', false, 'not_allowed');
        }

        $trip->load('rides.members');

        // $trip->cancelTripByDriver();
        $ride->cancelRideByDriver();

        $trip->affectDriverRating();

        return RESTAPIHelper::response(new \stdClass, true, 'Trip cancelled successfully.');
    }

    public function driverGetBankAccounts(Request $request)
    {
        $me     = $this->getUserInstance();
        $bank   = $me->bankAccount;

        if ($bank && $bank->count()) {
            $response = \App\Classes\RijndaelEncryption::encrypt(json_encode([
                'bank_name' => $bank->bank_name,
                'account_title' => $bank->account_title,
                'routing_number' => $bank->routing_number,
                'account_number' => $bank->account_number,
                // 'personal_id_number' => $bank->personal_id_number,
                'ssn_last_4' => $bank->ssn_last_4,
                'address' => $me->address,
                'state' => $me->state,
                'city' => $me->city,
                'postal_code' => $me->postal_code,
                'birth_date' => $me->birth_date,

            ]));

        } else {
            $response = new \stdClass;
        }

        return RESTAPIHelper::response( $response );
    }

    public function driverUpdateBankAccounts(Request $request)
    {
        try {
            $decryptedBodyPayload = json_decode(\App\Classes\RijndaelEncryption::decrypt($request->get('body')), true);

            $request->merge($decryptedBodyPayload);
        } catch (\Exception $e) {
            return RESTAPIHelper::response('Unable to decrypt your payload.', false, 'validation_error');
        }

        $validator = Validator::make($decryptedBodyPayload, [
            // 'bank_name'          => 'required',
            'account_title'      => 'required',
            'routing_number'     => 'required',
            'account_number'     => 'required',
            // 'personal_id_number' => 'required',
            'ssn_last_4'         => 'required',
            'address'            => 'required|string|max:255',
            'state'              => 'required|integer',
            'city'               => 'required|integer',
            'postal_code'        => 'required|string|max:10',
            'birth_date'         => 'required|date_format:m/d/Y',
            // 'period'          => 'required|in:expedited,standard',
        ]);

        if ($validator->fails()) {
            return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
        }

        $me     = $this->getUserInstance();
        $bank   = $me->bankAccount;

        // Save profile information.
        $me->setMeta([
            'postal_code' => $request->get('postal_code'),
            'birth_date'  => $request->get('birth_date'),
        ]);

        $me->update([
            'address' => $request->get('address'),
            'state'   => $request->get('state'),
            'city'    => $request->get('city'),
        ]);

        if ( $bank && $bank->account_id ) {
            $bank->bank_name          = $request->get('bank_name');
            $bank->account_title      = $request->get('account_title');
            $bank->routing_number     = $request->get('routing_number');
            $bank->account_number     = $request->get('account_number');
            $bank->personal_id_number = $request->get('personal_id_number', '');
            $bank->ssn_last_4         = $request->get('ssn_last_4');

            $bank->save();

            $record = $bank;

        } else {

            try {
                $account = StripeHelper::createDriverAccount($request->all(), $me);
            } catch (\Exception $e) {
                info('createDriverAccount: ' . $e->getMessage() . ' File: ' . $e->getFile() . ' Line: ' . $e->getLine());
		$error = $e->getMessage().'. Line: '.$e->getLine();
                return RESTAPIHelper::response('Error: ' . $error, false, 'validation_error');
            }

            if ($bank) {
                $me->bankAccount()->delete();
            }

            $record = $me->bankAccount()->create([
                'account_id'          => $account->id,
                // 'bank_name'           => $request->get('bank_name', ''),
                'account_title'       => $request->get('account_title'),
                'routing_number'      => $request->get('routing_number'),
                'account_number'      => $request->get('account_number'),
                // 'personal_id_number'  => $request->get('personal_id_number', ''),
                'ssn_last_4'          => $request->get('ssn_last_4'),
                'active'              => 1,
                // 'period'           => $request->get('period'),
                // 'checking_account' => $request->get('checking_account'),
                // 'swift_code'       => $request->get('swift_code'),
                // 'bank_address'     => $request->get('bank_address'),
            ]);
        }

        $encryptedPayload = \App\Classes\RijndaelEncryption::encrypt(json_encode(collect($record)->only(['bank_name', 'account_title', 'routing_number', 'account_number', 'personal_id_number', 'ssn_last_4'])->toArray() + $request->only(['address', 'state', 'city', 'postal_code', 'birth_date'])));

        return RESTAPIHelper::response( ($record->count()
                ? $encryptedPayload
                : new \stdClass
            ), true, 'Bank details updated successfully.' );
    }
}
