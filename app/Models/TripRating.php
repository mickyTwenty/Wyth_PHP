<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripRating extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'rater_id',
        'rater_type',
        'ratee_id',
        'ratee_type',
        'trip_ride_id',
        'rating',
        'feedback',
        'is_approved',
    ];

    public function isRater($userId)
    {
        return (bool) ($this->attributes['rater_id'] == $userId);
    }

    public function isRatee($userId)
    {
        return (bool) ($this->attributes['ratee_id'] == $userId);
    }

    public function getStatusTextFormattedAttribute()
    {
        return $this->attributes['is_approved'] == '1' ?
        '<span class="label label-success">Approved</span>' :
        '<span class="label label-danger">Pending</span>';
    }

    public function approve()
    {
        $this->is_approved = 1;
        $this->save();
    }

    public function disapprove()
    {
        $this->is_approved = 0;
        $this->save();
    }

    /**
     * Scopes
     */
    public function scopeRaterId($query, $raterId)
    {
        return $query->where('rater_id', $raterId);
    }

    /**
     * Relations
     */
    public function rater()
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function ratee()
    {
        return $this->belongsTo(User::class, 'ratee_id');
    }

    public function tripRide()
    {
        return $this->belongsTo(TripRide::class, 'trip_ride_id');
    }
}
