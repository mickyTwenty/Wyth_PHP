<?php

namespace App\Models;

use App\Models\TripMember;
use Illuminate\Database\Eloquent\Model;

class TripRideOffer extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'trip_ride_id',
        'from_user_id',
        'from_user_type',
        'to_user_id',
        'to_user_type',
        'group_id',
        'coupon_id',
        'seats_total',
        'seats_total_returning',
        'proposed_amount',
        'has_accepted',
        'bags_quantity',
        'time_range',
        'is_roundtrip',
    ];

    public function isPassenger($userId)
    {
        return (bool) (
            ($this->from_user_id == $userId && $this->from_user_type == TripMember::TYPE_PASSENGER) ||
            ($this->to_user_id == $userId && $this->to_user_type == TripMember::TYPE_PASSENGER)
        );
    }

    public function isDriver($userId)
    {
        return (bool) (
            ($this->from_user_id == $userId && $this->from_user_type == TripMember::TYPE_DRIVER) ||
            ($this->to_user_id == $userId && $this->to_user_type == TripMember::TYPE_DRIVER)
        );
    }

    public function isSender($userId)
    {
        return (bool) ($this->from_user_id == $userId);
    }

    public function isReceiver($userId)
    {
        return (bool) ($this->to_user_id == $userId);
    }

    /**
     * This will extract user_id from record when asked for it by user type
     * eg: Return me user_id of record from either `from_user_type` or `to_user_type`
     *
     * @param  string $userType
     * @return integer
     */
    public function extractUserIdByUserType($userType)
    {
        if ( $userType == TripMember::TYPE_DRIVER ) {
            return $this->from_user_type == TripMember::TYPE_DRIVER ? $this->from_user_id : $this->to_user_id;
        }

        return $this->from_user_type == TripMember::TYPE_PASSENGER ? $this->from_user_id : $this->to_user_id;
    }

    /*
     * @Scopes
     */
    public function scopeHasAnyOfferByPassenger($query, $userId)
    {
        return $query->where(function($query) use ($userId) {
            $query->where(function($query) use ($userId) {
                $query->fromPassenger($userId);
            })->orWhere(function ($query) use ($userId) {
                $query->toPassenger($userId);
            });
        });
    }

    /**
     * Does passenger has any offer associated with x driver on x trip?
     */
    public function scopeHasAnyOfferByPassengerTo($query, $passengerId, $driverId)
    {
        return $query->where(function($query) use ($passengerId, $driverId) {
            $query->where(function($query) use ($passengerId, $driverId) {
                $query->fromPassenger($passengerId)
                    ->toDriver($driverId);
            })->orWhere(function ($query) use ($passengerId, $driverId) {
                $query->toPassenger($passengerId)
                    ->fromDriver($driverId);
            });
        });
    }

    public function scopeFromPassenger($query, $userId)
    {
        return $query->where('from_user_id', $userId)
            ->where('from_user_type', TripMember::TYPE_PASSENGER);
    }

    public function scopeToPassenger($query, $userId)
    {
        return $query->where('to_user_id', $userId)
            ->where('to_user_type', TripMember::TYPE_PASSENGER);
    }

    public function scopeHasAnyOfferByDriver($query, $driverId)
    {
        return $query->where(function($query) use ($driverId) {
            $query->where(function($query) use ($driverId) {
                $query->fromDriver($driverId);
            })->orWhere(function ($query) use ($driverId) {
                $query->toDriver($driverId);
            });
        });
    }

    public function scopeHasAnyOfferByDriverTo($query, $driverId, $passengerId)
    {
        return $query->where(function($query) use ($driverId, $passengerId) {
            $query->where(function($query) use ($driverId, $passengerId) {
                $query->fromDriver($driverId)
                    ->toPassenger($passengerId);
            })->orWhere(function ($query) use ($driverId, $passengerId) {
                $query->toDriver($driverId)
                    ->fromPassenger($passengerId);
            });
        });
    }

    public function scopeFromDriver($query, $userId)
    {
        return $query->where('from_user_id', $userId)
            ->where('from_user_type', TripMember::TYPE_DRIVER);
    }

    public function scopeToDriver($query, $userId)
    {
        return $query->where('to_user_id', $userId)
            ->where('to_user_type', TripMember::TYPE_DRIVER);
    }

    public function scopeAccepted($query)
    {
        return $query->where('has_accepted', 1);
    }

    public function scopeGroupId($query, $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    public function scopeNotAccepted($query)
    {
        return $query->where('has_accepted', 0);
    }

    /*
     * @Relationships
     */

    public function ride()
    {
        return $this->belongsTo(TripRide::class, 'trip_ride_id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
