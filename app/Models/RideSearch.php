<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Geocode;

class RideSearch extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'origin_latitude',
        'origin_longitude',
        'destination_latitude',
        'destination_longitude',
        'extra',
    ];

    /**
     * Extract extra search details form request payload
     *
     * @param  Request $request
     * @return array
     */
    public static function extractSearchDetails(Request $request)
    {
        try {
            $city = getCityFromLatLng($request->get('destination_latitude'), $request->get('destination_longitude'));

            $payload = [
                'expected_start_date' => $request->get('expected_start_date'),
                'time_range'          => $request->get('time_range'),
                'is_roundtrip'        => $request->get('is_roundtrip'),
                'invited_members'     => $request->get('invited_members'),
                'preferences'         => $request->get('preferences'),
                'city'                => $city,
            ];
        } catch (Exception $e) {
            $payload = [];
        }

        return $payload;
    }

    /*
     * @Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
