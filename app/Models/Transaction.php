<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'stripe_charge_id',
        'trip_ride_id',
        'amount',
        'transaction_fee',
        'payload',
        'is_refunded',
        'refunded_at',
    ];

    public function getStatusTextFormattedAttribute()
    {
        return $this->attributes['is_refunded'] == '1' ?
        '<span class="label label-danger">Yes</span>' :
        '<span class="label label-success">No</span>';
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_refunded' => 'boolean',
        'refunded_at' => 'datetime',
    ];

    /**
     * Scopes
     */
    public function scopeMemberId($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRefunded($query)
    {
        return $query->where('is_refunded', 1);
    }

    public function scopeNotRefunded($query)
    {
        return $query->where('is_refunded', 0);
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

    public function card()
    {
        return $this->belongsTo(PassengerCard::class, 'passenger_card_id');
    }
}
