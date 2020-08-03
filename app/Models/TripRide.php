<?php

namespace App\Models;

use App\Events\NotifySubscribedUser;
use App\Events\PassengerAcceptedOffer;
use App\Events\PassengerAddedToTrip;
use App\Events\TerminateOfferUponTimeChange;
use App\Events\TripMembersAdded;
use App\Events\TripMembersUpdated;
use App\Events\TripRideCreated;
use App\Http\Traits\Metable\Metable;
use App\Models\TripMember;
use App\Models\TripRideOffer;
use App\Models\User;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use InvalidArgumentException;

class TripRide extends Model
{
    use Metable;

    /*
     * Ride status with integer values
     */
    // When ride doesn't have driver associated.
    const RIDE_STATUS_PENDING            = 0;

    // When ride has driver associated and appearing in search result.
    const RIDE_STATUS_ACTIVE             = 1;

    // When all passengers of ride has been confirmed, driver can select departure time if set to this status.
    const RIDE_STATUS_FILLED             = 2;

    // Ready-to-fly situation. When all passengers of ride has been confirmed, driver has selected departure time.
    const RIDE_STATUS_CONFIRMED          = 3;

    // When driver has marked ride as started.
    const RIDE_STATUS_STARTED            = 4;

    // When driver has marked ride as ended.
    const RIDE_STATUS_ENDED              = 5;

    // When ride is canceled, not decided yet how this status gonna be set.
    const RIDE_STATUS_CANCELED           = 6;

    // When trip is a round-trip and going ride has completed then this status will be set (without time confirmed)
    const RIDE_STATUS_ONE_TRIP_COMPLETED = 7;

    // When trip is a round-trip and going ride has completed and time is confirmed then status will be.
    const RIDE_STATUS_GOING_CONFIRMED    = 8;

    // When ride failed to start, not decided yet how this status gonna be set.
    const RIDE_STATUS_FAILED             = 19;

    // When ride failed to start, expired or anything bad happened.
    const RIDE_STATUS_EXPIRED            = 20;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'time_range',
        'desired_gender',
        'ride_status',
        'origin_latitude',
        'origin_longitude',
        'origin_title',
        'origin_city',
        'destination_latitude',
        'destination_longitude',
        'destination_title',
        'destination_city',
        'seats_total',
        'seats_available',
        'start_time',
        'started_at',
        'ended_at',
    ];

    /**
     * Meta table for this model.
     *
     * @var string
     */
    protected $metaTable = 'trip_ride_meta';

    /**
     * Meta data model relating to this model.
     *
     * @var string
     */
    protected $metaModel = 'App\Models\TripRideMeta';

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $events = [
        'created' => TripRideCreated::class,
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'ride_status_text'
    ];

    protected $dates   = ['canceled_at'];

    public function isUpcoming()
    {
        return (bool) ($this->attributes['start_time'] >= rideExpectedStartTime());
    }

    public function isActiveRide()
    {
        $trip = $this->trip;

        if (!$this->isUpcoming()) {
            return false;
        }

        if ($trip->isCanceled()) {
            return false;
        }

        return true;
    }

    public function isValidRide()
    {
        $trip = $this->trip;

        if (!$trip->hasDriver()) {
            return false;
        }

        // if ( !$this->hasAvailableSeats() )
        //     return false;

        return true;
    }

    public function isCanceled()
    {
        return (bool) ($this->attributes['canceled_at'] !== null);
    }

    /**
     * A method to identify is this going ride of the trip?
     *
     * @return boolean
     */
    public function isGoingRideOfTrip()
    {
        return (bool) ($this->trip->rides->pluck('id')->first() === intval($this->id));
    }

    /**
     * A method to identify is this returning ride of the trip?
     *
     * @return boolean
     */
    public function isReturningRideOfTrip()
    {
        return (bool) ($this->trip->rides->pluck('id')->last() === intval($this->id));
    }

    public function getGoingRideOfTrip()
    {
        return $this->trip->getGoingRideOfTrip();
    }

    public function getReturningRideOfTrip()
    {
        return $this->trip->getReturningRideOfTrip();
    }

    /**
     * This method will determine which ride to get details/do action,
     * Since driver will only receive going's ride ID on a roundtrip then
     * we will switch internally to perform such actions on appropriate ride
     *
     * @return boolean
     */
    public function isTimeToSwitchTheRide()
    {
        // HIGHLIGHT: Client want to handle ride separately, so all excessive tasks has been disabled
        // that's why this method needs to be `return false` so that internal switching don't work.
        return false;

        return (bool) (
            $this->trip->isRoundTrip() &&
            $this->hasEnded() &&
            false === $this->isReturningRideOfTrip()
        );
    }

    public function hasAvailableSeats()
    {
        return intval($this->seats_available) > 0;
    }

    public function isDriver($user)
    {
        $user = User::extractUserId($user);

        try {
            return intval($this->trip->user_id) === intval($user);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Does this ride's trip has a driver associated?
     *
     * @return boolean
     */
    public function hasDriver()
    {
        return $this->trip->hasDriver();
    }

    /**
     * Get driver details
     *
     * @return boolean
     */
    public function getDriver()
    {
        return $this->trip->driver;
    }

    public function getPassengerRecord($passengerId)
    {
        return $this->members()->memberId($passengerId)->first();
    }

    public function hasPassenger($passengerId)
    {
        return (bool) ($this->members()->memberId($passengerId)->count() > 0);
    }

    public function hasStarted()
    {
        return (bool) (
            /*$this->attributes['ride_status'] == self::RIDE_STATUS_STARTED &&*/
            $this->attributes['started_at'] != null
        );
    }

    public function hasEnded()
    {
        return (bool) (
            /*$this->attributes['ride_status'] == self::RIDE_STATUS_ENDED &&*/
            $this->attributes['ended_at'] != null
        );
    }

    public function makeConfirmRideByPassenger(TripRideOffer $offer, User $passenger, User $driver, $coupon = null)
    {
        try {

            $isPrivateTrip = false;

            DB::beginTransaction();

            // Assign driver to ride
            if (!$this->trip->hasDriver()) {
                $this->trip->user_id = $driver->id;

                // Validate driver's availability on trip's date.
                $this->trip->validateRideDates($driver, $this->start_time, ($offer->is_roundtrip ? $this->getReturningRideOfTrip()->start_time : null));

                $this->updateRideStatus(self::RIDE_STATUS_ACTIVE);
            }

            // Mark trip as public
            if ($this->trip->isRequest()) {
                $this->trip->markAsPublic(false);

                $isPrivateTrip = true;
            }

            $this->trip->save();

            $this->changeTimeRangeOfRide(collect($offer), $passenger);

            // NOTE: Different amount can be charge from all-passengers because of roundtrip association.
            $amountToCharge = 0;
            $rideIndex = -1;

            foreach ($this->trip->rides as $roundTripRide) {

                // If passenger does not wants to book roundtrip then dont add them to other-way ride
                if ($offer->is_roundtrip != 1 && $roundTripRide->id !== $this->id) {
                    continue;
                }

                $rideIndex++;

                if (
                    $rideIndex == 0 && // Going-Trip
                    $offer->seats_total > 0 &&
                    true === $isPrivateTrip
                ) {
                    // Update seats_total, then seats_available will be adjusted acordingly.
                    $roundTripRide->seats_total = $offer->seats_total;
                    $roundTripRide->save();

                    event(new TripMembersUpdated($roundTripRide));
                } elseif (
                    $rideIndex == 1 && // Returning-Trip
                    $offer->seats_total_returning > 0 &&
                    true === $isPrivateTrip
                ) {
                    // Update seats_total, then seats_available will be adjusted acordingly.
                    $roundTripRide->seats_total = $offer->seats_total_returning;
                    $roundTripRide->save();

                    event(new TripMembersUpdated($roundTripRide));
                }

                // First, we need to check if passenger has part of group for the trip?
                // If yes, then we need to add grouped-passengers to reserved passengers list
                if (!empty($offer->group_id)) {

                    // Do we need to run all co-passengers check? No. Okay good then move forward!
                    if (
                        false === $roundTripRide->getMetaObject('info.passengers_added_' . $offer->group_id)->exists()
                    ) {

                        $groupMembers        = $roundTripRide->offers()->groupId($offer->group_id)->get();
                        $groupMembersUserIds = $groupMembers->map(function ($row) {
                            return $row->extractUserIdByUserType(TripMember::TYPE_PASSENGER);
                        });

                        // We won't add passenger to the reserved-list if they're already in
                        // So we need to filter the list of users first
                        $existingMembers = $roundTripRide->members()->pluck('user_id');

                        // Now we've ids of those users which are not the passengers for this trip
                        $toAddNewPassengers = $groupMembersUserIds->diff($existingMembers->intersect($groupMembersUserIds));

                        $roundTripRide->addPassengers($toAddNewPassengers->toArray(), new TripMember([
                            'is_confirmed' => 0,
                            'group_id'     => $offer->group_id,
                            'invited_by'   => $passenger->id,
                        ]));

                        $roundTripRide->setMeta('info.passengers_added_' . $offer->group_id, true);
                        $roundTripRide->save();
                    }

                } else {
                    // Passenger is not a part of group

                    // Attach self-passenger if isn't
                    if (!$roundTripRide->hasPassenger($passenger->id)) {
                        $roundTripRide->addPassengers([$passenger->id], new TripMember([
                            'is_confirmed'  => 1,
                            'fare'          => $offer->proposed_amount,
                            'bags_quantity' => $offer->bags_quantity,
                        ]));
                    } else {
                        \Log::debug('Passenger found already, there is a 1% chance of is_confirmed still zero');
                    }
                }

                $amountToCharge += ($offer->is_roundtrip == 1 ? ($offer->proposed_amount / $this->trip->rides->count()) : $offer->proposed_amount);

                // Update self passenger record
                $passengerRecord                = $roundTripRide->getPassengerRecord($passenger->id);
                $passengerRecord->group_id      = $offer->group_id;
                $passengerRecord->is_confirmed  = 1;
                $passengerRecord->fare          = ($offer->is_roundtrip == 1 ? ($offer->proposed_amount / $this->trip->rides->count()) : $offer->proposed_amount);
                $passengerRecord->bags_quantity = $offer->bags_quantity;
                $passengerRecord->coupon_id     = ($coupon) ? $coupon->id : null;

                $passengerRecord->save();

                event(new PassengerAddedToTrip($roundTripRide));
            }

            event(new PassengerAcceptedOffer($roundTripRide, $passengerRecord, $offer, $amountToCharge));

            DB::commit();

        } catch (Exception $e) {
            DB::rollback();

            throw $e;

        }
    }

    public function updateRideStatus($status, $saveActivity = true)
    {
        $this->ride_status = $status;
        $this->save();

        if ($saveActivity) {
            $this->addTripActivity($status);
        }
    }

    public function changeTimeRangeOfRide($offer, $passenger)
    {
        // Intimate passengers about the ride time change and void all ongoing offers.
        $intimatePassengersOfRide = [];

        // Change ride time if applicable
        // If requested for a roundtrip, then both rides's time should change
        if ($offer->get('is_roundtrip') == 1) {
            foreach ($this->trip->rides as $index => $ride) {
                if (
                    $index == 0 && // Going-Trip
                    $offer->get('time_range') > 0 &&
                    $offer->get('time_range') < $ride->time_range
                ) {
                    $ride->time_range = $offer->get('time_range');
                    $ride->save();

                    $intimatePassengersOfRide[] = [
                        'ride'         => $ride,
                        'group_id'     => $offer->get('group_id'),
                        'passenger_id' => $passenger->id,
                    ];
                } elseif (
                    $index == 1 && // Returning-Trip
                    $offer->get('time_range_returning') > 0 &&
                    $offer->get('time_range_returning') < $ride->time_range
                ) {
                    $ride->time_range = $offer->get('time_range_returning');
                    $ride->save();

                    $intimatePassengersOfRide[] = [
                        'ride'         => $ride,
                        'group_id'     => $offer->get('group_id'),
                        'passenger_id' => $passenger->id,
                    ];
                }
            }
        } else {
            if ($offer->get('time_range') > 0 && $offer->get('time_range') < $this->time_range) {
                $this->time_range = $offer->get('time_range');

                $intimatePassengersOfRide[] = [
                    'ride'         => $this,
                    'group_id'     => $offer->get('group_id'),
                    'passenger_id' => $passenger->id,
                ];
            }
        }

        $this->save();

        event(new TerminateOfferUponTimeChange($intimatePassengersOfRide));
    }

    public function detailForDriverPassengerOffer(self $ride, $passengerId, $driverId)
    {
        $offer = $ride->offers()->hasAnyOfferByPassengerTo($passengerId, $driverId)->first();

        if (!$offer) {
            throw new InvalidArgumentException('No offer found.');
        }

        $records                             = [];
        $records['offer_id']                 = $offer->id;
        $records['group_id']                 = $offer->group_id;
        $records['trip_id']                  = $offer->ride->id;
        $records['trip_name']                = $offer->ride->trip->trip_name;
        $records['date']                     = $offer->ride->expected_start_date;
        $records['min_estimates']            = $offer->ride->trip->min_estimates;
        $records['max_estimates']            = $offer->ride->trip->max_estimates;
        $records['expected_distance']        = $offer->ride->trip->expected_distance;
        $records['expected_distance_format'] = $offer->ride->trip->expected_distance_format;
        $records['expected_duration']        = $offer->ride->trip->expected_duration;
        $records['time_range']               = $offer->ride->time_range;
        $records['origin_latitude']          = $offer->ride->origin_latitude;
        $records['origin_longitude']         = $offer->ride->origin_longitude;
        $records['origin_title']             = $offer->ride->origin_title;
        $records['destination_latitude']     = $offer->ride->destination_latitude;
        $records['destination_longitude']    = $offer->ride->destination_longitude;
        $records['destination_title']        = $offer->ride->destination_title;
        $records['is_roundtrip']             = !!$offer->is_roundtrip;

        $coupon = isset($offer->coupon) ? $offer->coupon->code : '';

        $passengerSavedPreferences = [
            'time_range'      => $offer->time_range,
            'proposed_amount' => $offer->proposed_amount,
            'bags_quantity'   => $offer->bags_quantity,
            'has_accepted'    => $offer->has_accepted,
            'promo_code'      => $coupon,
        ];

        if ($offer->isSender($passengerId)) {
            $driver               = $offer->receiver;
            $records['passenger'] = array_merge(User::extractUserBasicDetails($offer->sender), $passengerSavedPreferences);
        } else {
            $driver               = $offer->sender;
            $records['passenger'] = array_merge(User::extractUserBasicDetails($offer->receiver), $passengerSavedPreferences);
        }

        try {
            $records['driver'] = array_merge(User::extractUserBasicDetails($driver), [
                'driving_license_no' => $driver->getMetaDefault('driving_license_no', ''),
                'vehicle_type'       => $driver->getMetaDefault('vehicle_type', ''),
                'vehicle_id_number'  => $driver->getMetaDefault('vehicle_id_number', ''),
            ]);
        } catch (Exception $e) {
            $records['driver'] = new \stdClass;
        }

        return $records;
    }

    public function rideInProcessResponse($members)
    {
        $response = [
            'trip_id'                  => $this->id,
            'time_range'               => $this->time_range,
            'trip_name'                => $this->trip->trip_name,
            'origin_latitude'          => $this->origin_latitude,
            'origin_longitude'         => $this->origin_longitude,
            'origin_title'             => $this->origin_title,
            'destination_latitude'     => $this->destination_latitude,
            'destination_longitude'    => $this->destination_longitude,
            'destination_title'        => $this->destination_title,
            'seats_available'          => $this->seats_available,
            'seats_total'              => $this->seats_total,
            'date'                     => $this->expected_start_date,
            'ride_status'              => $this->ride_status_text,
            'expected_start_time'      => $this->start_time,
            'route_polyline'           => $this->route->stepped_route,
        ];

        try {
            $response['driver'] = User::extractUserBasicDetails($this->trip->driver);
        } catch (Exception $e) {
            $response['driver'] = new \stdClass;
        }

        $rideMetaData = collect($this->getMeta());

        $response['passengers'] = [];
        foreach ($members as $member) {
            $response['passengers'][] = User::extractUserBasicDetails($member->user) + [
                'is_picked' => $member->is_picked,
                'is_dropped' => $member->is_dropped,
            ] + $rideMetaData->get('geo.passenger_'.$member->user_id, [
                'pickup_latitude' => '',
                'pickup_longitude' => '',
                'pickup_title' => '',
                'dropoff_latitude' => '',
                'dropoff_longitude' => '',
                'dropoff_title' => '',
            ]);
        }

        return $response;
    }

    public function intimateDriverAboutFailedPayment(User $passenger)
    {
        $driver = $this->trip->driver;

        $driver->createNotification(TripMember::TYPE_DRIVER, 'Payment failed for ' . $passenger->full_name, [
            'message' => 'Payment failed for ' . $passenger->full_name,
            'type'    => 'payment_failed_driver',
        ])->notActionable()
        ->customPayload([
            'click_action'     => 'payment_failed_driver',
            'trip_id'          => $this->id,
            'driver_id'        => $driver->id,
            'passenger_id'     => $passenger->id,
        ])->disableThrowing('push')->build();
    }

    /**
     * Accessors
     */
    public function getExpectedStartDateAttribute()
    {
        return Carbon::parse($this->attributes['start_time'])->format(constants('api.global.formats.date'));
    }

    public function getEndedAtDateAttribute()
    {
        return Carbon::parse($this->attributes['ended_at'])->format(constants('api.global.formats.date'));
    }

    public function addTripActivity($status)
    {
        return $this->activity()->create([
            'status' => $status,
        ]);
    }

    public function createEmptyOffer(array $userIds, $invitedBy = null, $trip = null)
    {
        $invitedBy = is_null($invitedBy) ? null : User::extractUserId($invitedBy);

        $this->validateAvailableSeats($userIds);

        try {
            DB::beginTransaction();

            $groupId = '';

            // Generate group_id only if users found more than 1
            if (count($userIds) > 1) {
                $groupId = TripMember::generateUniqueGroupId($this->trip, $userIds);
            }

            foreach ($userIds as $userId) {
                $existingOffer = $this->offers()->hasAnyOfferByPassenger($userId)->first();

                if ($existingOffer) {
                    // do not do any thing
                } else {
                    $this->offers()->create([
                        'from_user_id'   => $invitedBy,
                        'from_user_type' => TripMember::TYPE_DRIVER,
                        'to_user_id'     => $userId,
                        'to_user_type'   => TripMember::TYPE_PASSENGER,
                        'group_id'       => $groupId,
                        'is_roundtrip'   => $trip ? $trip->is_roundtrip : 0,
                    ]);
                }
            }

            DB::commit();
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            throw new InvalidArgumentException("Unable to invite members because user does not exist in system.");
        }
    }

    public function addPassengers(array $userIds, $payload = null)
    {
        if (count($userIds) == 0) {
            return; // nothing to do!
        }

        if (!is_null($payload) && (!$payload instanceof TripMember)) {
            throw new InvalidArgumentException('#2 Argument passed is not a member of TripMember');
        }

        $payload   = collect($payload);
        $invitedBy = is_null($payload->get('invited_by')) ? null : User::extractUserId($payload->get('invited_by'));

        $this->canJoinRide($userIds);
        $this->validateAvailableSeats($userIds);

        try {
            DB::beginTransaction();

            // NOTE: Group id will be overwrite if provided in payload variable
            $groupId = '';

            // Generate group_id only if users found more than 1
            if (count($userIds) > 1) {
                $groupId = TripMember::generateUniqueGroupId($this->trip, $userIds);
            }

            foreach ($userIds as $userId) {
                $this->members()->create([
                    'user_id'        => $userId,
                    'is_confirmed'   => $payload->get('is_confirmed', 0),
                    'payment_status' => TripMember::DEFAULT_PAYMENT_STATUS,
                    'bags_quantity'  => $payload->get('bags_quantity', 0),
                    'fare'           => $payload->get('fare', 0.00),
                    'group_id'       => $payload->get('group_id', $groupId),
                    'invited_by'     => $invitedBy,
                ]);
            }

            event(new TripMembersAdded($this, $userIds, $payload));

            DB::commit();
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            throw new InvalidArgumentException("Unable to invite members because user does not exist in system.");
        }
    }

    /**
     * Update ride preferences
     *
     * @param  array $payload
     * @return void
     */
    public function updateRidePreferences($payload)
    {
        if (is_array($payload)) {
            foreach ($payload as $preferenceKey => $preferenceValue) {
                $this->setMeta('preference_' . $preferenceKey, $preferenceValue, null, true);
                $this->save();
            }
        }
    }

    public function canJoinRide(array $userIds)
    {
        # Validate same passenger and driver cannot be on same trip.
        if ( $this->hasDriver() && $driver = $this->getDriver() ) {
            if ( in_array(User::extractUserId($driver), $userIds) ) {
                throw new \App\Exceptions\UserCanNotJoinRide('You cannot be driver and passenger both on same ride.', 'unable_to_join');
            }
        }
    }

    public function validateAvailableSeats(array $userIds)
    {
        if ($this->seats_available < count($userIds)) {
            throw new \App\Exceptions\RideSeatsExhausted("There are not any available seats left.");
        }
    }

    /**
     * Resolution of ride status should be camelCase
     *
     * @return string
     */
    public function resolveRideStatusHumanReadable($status = null)
    {
        if (is_null($status)) {
            $status = $this->attributes['ride_status'];
        }

        switch ($status) {
            case self::RIDE_STATUS_PENDING:
                return 'pending';
                break;
            case self::RIDE_STATUS_ACTIVE:
                return 'active';
                break;
            case self::RIDE_STATUS_STARTED:
                return 'started';
                break;
            case self::RIDE_STATUS_ENDED:
                return 'ended';
                break;
            case self::RIDE_STATUS_ONE_TRIP_COMPLETED:
                return 'goingCompleted';
                break;
            case self::RIDE_STATUS_GOING_CONFIRMED:
                return 'returnConfirmed';
                break;
            case self::RIDE_STATUS_CANCELED:
                return 'canceled';
                break;
            case self::RIDE_STATUS_FAILED:
                return 'failed';
                break;
            case self::RIDE_STATUS_CONFIRMED:
                return 'confirmed';
                break;
            case self::RIDE_STATUS_FILLED:
                return 'filled';
                break;
            case self::RIDE_STATUS_EXPIRED:
                return 'expired';
                break;
            default:
                return 'undefined';
                break;
        }
    }

    public function saveRatingByPassenger(User $passenger, User $driver, $request)
    {
        return $this->ratings()->create([
            'rater_id'   => $passenger->id,
            'rater_type' => TripMember::TYPE_PASSENGER,
            'ratee_id'   => $driver->id,
            'ratee_type' => TripMember::TYPE_DRIVER,
            'rating'     => $request->get('rating'),
            'feedback'   => $request->get('feedback'),
        ]);
    }

    public function saveRatingByDriver(User $driver, User $passenger, $rating, $feedback)
    {
        return $this->ratings()->create([
            'rater_id'   => $driver->id,
            'rater_type' => TripMember::TYPE_DRIVER,
            'ratee_id'   => $passenger->id,
            'ratee_type' => TripMember::TYPE_PASSENGER,
            'rating'     => $rating,
            'feedback'   => $feedback,
        ]);
    }

    public function affectPassengerRating(User $passenger)
    {
        return Trip::affectPassengerRating($this, $passenger);
    }

    public function markRideAsCanceled()
    {
        $this->canceled_at = Carbon::now();
        $this->save();
    }

    public function cancelRideByDriver($countCancel = true)
    {
        # Remove passengers from trip and offers

        # Get the list of members to send notification to, event based need to fetch first.
        $members = $this->members->pluck('user_id')->toArray();

        $this->offers()->delete(); // Delete all offers relating to this ride.

        foreach ($this->members as $tripMember) {
            TripMember::refundAndRemoveElement($this, $tripMember, false);
        }

        $this->updateRideStatus(TripRide::RIDE_STATUS_CANCELED, true);

        // Mark trip as canceled.
        $this->markRideAsCanceled();

        $this->load('trip');
        $trip = $this->trip;

        // We were canceling entire trip when any ride canceled, now we need to make
        // sure if a trip doesn't have any rides left inside it then if initiator is
        // passenger, replicate the trip behalf of passenger for better experience..
        $noRidesLeft = false;

        // Replicate since trip has only one ride since beginning because of no-roundtrip
        if (!$trip->isRoundTrip() || $trip->rides->count() == 0) {
            $noRidesLeft = true;
        }

        if ($noRidesLeft) {

            $trip->markAsCanceled();

            if ($trip->initiated_type == 'passenger') {
                try {
                    $trip->resetTripData();
                } catch (\Exception $e) {
                    info('Unable to replicate trip data');
                }
            }
        }
        else
        {
            event(new NotifySubscribedUser($trip));

        }

        if ($countCancel) {
            $driver             = $this->getDriver();
            $currentCancelRides = intval($driver->getMetaMulti(UserMeta::GROUPING_DRIVER)->get('canceled_trips', 0));

            $driver->setMeta(['canceled_trips' => $currentCancelRides + 1], UserMeta::GROUPING_DRIVER);
            $driver->save();
        }

        event(new \App\Events\RideCanceledByDriver($this, $members));
    }

    public function cancelRideByAdmin()
    {
        # Remove passengers from trip and offers

        # Get the list of members to send notification to, event based need to fetch first.
        $members = $this->members->pluck('user_id')->toArray();

        $this->offers()->delete(); // Delete all offers relating to this ride.

        foreach ($this->members as $tripMember) {
            TripMember::refundAndRemoveElement($this, $tripMember, false);
        }

        $this->updateRideStatus(TripRide::RIDE_STATUS_CANCELED, true);

        // Mark trip as canceled.
        $this->markRideAsCanceled();

        $this->load('trip');
        $trip = $this->trip;

        $noRidesLeft = false;

        // Replicate since trip has only one ride since beginning because of no-roundtrip
        if (!$trip->isRoundTrip() || $trip->rides->count() == 0) {
            $noRidesLeft = true;
        }

        if ($noRidesLeft) {
            $trip->markAsCanceled();
        }

        event(new \App\Events\RideCanceledByDriver($this, $members));
    }

    /*
     * @Scopes
     */
    public function scopeSeatsAvailable($query)
    {
        return $query->where('seats_available', '>=', '1');
    }

    public function scopeEnded($query)
    {
        return $query
            ->whereNotNull($this->getTable() . '.ended_at')
            ->whereNotNull($this->getTable() . '.started_at');
    }

    public function scopeNotEnded($query)
    {
        return $query->where('ride_status', '!=', self::RIDE_STATUS_ENDED);
    }

    public function scopePast($query)
    {
        return $query->where('ended_at', '<', rideExpectedStartTime());
    }

    public function scopeDestination($query, $latitude, $longitude)
    {
        return $query->whereRaw("ST_CONTAINS( createBuffer(POINT(destination_longitude, destination_latitude), ?), ST_GEOMFROMTEXT(CONCAT('POINT({$longitude} {$latitude})')) )", [
            constants('global.ride.previous_search'),
        ]);

        return $query->where('destination_latitude', $latitude)
            ->where('destination_longitude', $longitude);
    }

    public function scopeEndedDate($query, $date)
    {
        return $query->whereDate('ended_at', '<=', $date);
    }

    public function scopeFutureRides($query)
    {
        // return $query->where('seats_available', '>=', '1');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>=', rideExpectedStartTime());
    }

    public function scopeExpectedStartDate($query, $date)
    {
        return $query->whereDate('start_time', $date);
    }

    public function scopeExpectedStartDateGreaterThan($query, $date)
    {
        return $query->whereDate('start_time', '>=', $date);
    }

    public function scopeCanceled($query)
    {
        return $query->whereNotNull('canceled_at');
    }

    public function scopeNotCanceled($query)
    {
        return $query->whereNull('canceled_at');
    }

    /*
     * @Accessors
     */
    public function getRideStatusTextAttribute()
    {
        return $this->resolveRideStatusHumanReadable();
    }

    /*
     * @Relationships
     */
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function earning()
    {
        return $this->hasOne(TripEarning::class)->orderBy('id', 'DESC');
    }

    public function member()
    {
        return $this->hasOne(TripMember::class)->orderBy('id', 'DESC');
    }

    public function members()
    {
        return $this->hasMany(TripMember::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function requestedMembers()
    {
        return $this->hasMany(TripRequestMember::class);
    }

    public function offers()
    {
        return $this->hasMany(TripRideOffer::class, 'trip_ride_id');
    }

    public function activity()
    {
        return $this->hasMany(TripActivity::class);
    }

    public function route()
    {
        return $this->hasOne(TripRideRoute::class);
    }

    public function rating()
    {
        return $this->hasOne(TripRating::class)->orderBy('id', 'DESC');
    }

    public function ratings()
    {
        return $this->hasMany(TripRating::class);
    }

    public function shareItenerary()
    {
        return $this->hasMany(TripRideShared::class);
    }

    public function metas()
    {
        return $this->hasMany(TripRideMeta::class, 'trip_ride_id');
    }
}
