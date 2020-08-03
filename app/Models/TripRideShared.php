<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class TripRideShared extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'trip_ride_id',
        'user_id',
        'first_name',
        'last_name',
        'email',
        'mobile',
        'views',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'string',
    ];

    protected $table = 'trip_ride_shared';

    public function getFullNameAttribute()
    {
        return trim($this->attributes['first_name'] . ' ' . $this->attributes['last_name']);
    }

    /*
     * @Relationships
     */
    public function ride()
    {
        return $this->belongsTo(TripRide::class, 'trip_ride_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function passenger()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
