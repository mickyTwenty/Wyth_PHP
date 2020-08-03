<?php

namespace App\Models;

use App\Events\PassengerCanceledTrip;
use App\Helpers\StripeHelper;
use App\Models\Trip;
use App\Models\TripRide;
use App\Models\User;
use Cache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TripMember extends Model
{
    use SoftDeletes;
    
    const TYPE_PASSENGER = 'passenger';
    const TYPE_DRIVER    = 'driver';

    const REFUND_CURFEW_PERCENTAGE = 20;

    // NOTE: This constant won't be usable anywhere once we implement payment in system, its better to search and remove this to avoid confusion
    const DEFAULT_PAYMENT_STATUS = 0;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'trip_id',
        'trip_ride_id',
        'user_id',
        'fare',
        'bags_quantity',
        'group_id',
        'payment_mode',
        'payment_status',
        'is_confirmed',
        'invited_by',
        'coupon_id',
        'picked_at',
        'dropped_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_confirmed' => 'boolean',
        'fare'         => 'float',
    ];

    public static function generateUniqueGroupId($trip, array $userIds)
    {
        if ($trip instanceof Trip) {
            $trip = $trip->id;
        }

        sort($userIds);

        return md5(implode('-', $userIds) . '-' . $trip);
    }

    public function isConfirmed()
    {
        return (bool) ($this->attributes['is_confirmed'] == 1);
    }

    public function isReadyToFly()
    {
        return (bool) ($this->attributes['is_confirmed'] == 1 && $this->attributes['payment_status'] == 1);
    }

    public static function intimateMemberForFailedPayment(TripRide $tripRide, User $user)
    {
        $user->createNotification(self::TYPE_PASSENGER, 'Payment processing failed! Please update your card', [
            'message' => 'Payment processing failed! Please update your card',
            'type'    => 'payment_processing_failed',
        ])->customPayload([
            'click_action'     => 'payment_processing_failed',
            'trip_id'          => $tripRide->id,
            'driver_id'        => $tripRide->trip->driver->id,
            'passenger_id'     => $user->id,
            'trip_name'        => $tripRide->trip->trip_name,
            'origin_text'      => $tripRide->origin_title,
            'destination_text' => $tripRide->destination_title,
        ])->throwNotificationsVia('push')->build();
    }

    public static function doCancelTripByPassenger(TripRide $ride, self $tripMember)
    {
        $passenger          = $tripMember->user;
        $currentCancelRides = intval($passenger->getMetaMulti(UserMeta::GROUPING_PROFILE)->get('canceled_trips', 0));

        $passenger->setMeta(['canceled_trips' => $currentCancelRides + 1], UserMeta::GROUPING_PROFILE);
        $passenger->save();

        $ride->affectPassengerRating($passenger);

        self::refundAndRemoveElement($ride, $tripMember, true);

        event(new PassengerCanceledTrip($ride, $ride->trip->driver, $tripMember->user_id));
    }

    public static function refundAndRemoveElement(TripRide $ride, self $tripMember, $canceledByPassenger=true)
    {
        if ( (bool) $tripMember->payment_status ) {
            $transaction = $ride->transactions()->memberId($tripMember->user_id)->orderBy('id', 'DESC')->first();

            if ($transaction) {

                $configs = Setting::extracts([
                    'setting.application.cancellation_fee',
                ]);

                // If cancellation time of ride lie with-in 24 hours of start time then charge cancellation fee defined.
                // If canceled by passenger.
                $amountToRefund = null;
                if ( $canceledByPassenger && Carbon::now()->diffInSeconds(Carbon::parse($ride->start_time), false) <= 86400 ) {
                    $amountToRefund = ($transaction->amount - calculatePercentage($transaction->amount, $configs->get('setting.application.cancellation_fee')));
                }

                if ( $refund = StripeHelper::refund($transaction->stripe_charge_id, $amountToRefund) ) {
                    $transaction->is_refunded     = 1;
                    $transaction->refunded_amount = $amountToRefund ? $amountToRefund : $transaction->amount;
                    $transaction->refunded_at     = Carbon::now();

                    if ( false !== $refund ) {
                        $transaction->stripe_refund_id = $refund->id;
                    }

                    $transaction->save();
                }
            }
        }

        $tripMember->delete();
    }

    public function getEntireTripFareByMember()
    {
        $tripMember = $this;

        return Cache::remember('fare_'.$this->user_id.'_'.$this->trip_ride_id, 60, function () use($tripMember) {
            $trip         = $this->ride->trip;
            $tripRidesIds = $trip->rides->pluck('id');

            return self::whereIn('trip_ride_id', $tripRidesIds)->where('user_id', $tripMember->user_id)->sum('fare');
        });
    }

    /**
     * Accessors
     */
    public function getIsPickedAttribute()
    {
        return !empty($this->attributes['picked_at']);
    }

    public function getIsDroppedAttribute()
    {
        return !empty($this->attributes['dropped_at']);
    }

    /**
     * Scopes
     */
    public function scopeConfirmed($query)
    {
        return $query->where('is_confirmed', 1);
    }

    public function scopeReadyToFly($query)
    {
        return $query->where(['is_confirmed' => 1, 'payment_status' => 1]);
    }

    public function scopeMemberId($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeGroupId($query, $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    public function scopeDropped($query)
    {
        return $query->whereNotNull('dropped_at');
    }

    public function scopeReservedPassengers($query)
    {
        return $query->where(function ($query) {
            $query->where('payment_status', '=', 0);
        });
    }

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ride()
    {
        return $this->belongsTo(TripRide::class, 'trip_ride_id');
    }
}
