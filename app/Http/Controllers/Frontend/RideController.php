<?php

namespace App\Http\Controllers\Frontend;

use App\Models\TripRideShared;
use App\Models\User;
use Illuminate\Http\Request;

class RideController extends FrontendController
{
    public function trackRide(Request $request, TripRideShared $rideShared)
    {
        $rideId = $rideShared->trip_ride_id;
        $userId = $rideShared->user_id;
        $user   = $rideShared->user;
        $ride   = $rideShared->ride;

        // When returning ride is active, return updated information
        if ( $ride->isTimeToSwitchTheRide() ) {
            // Time to set the pointer for the ride to coming trip
            $ride = $ride->getReturningRideOfTrip();
            info(__FUNCTION__ . ' @ Ride switched from ' . $rideId . ' to ' . $ride->id);
        }

        // Validate if still member?
        $passengers         = $ride->members()->readyToFly()->pluck('user_id')->toArray();
        $driver             = $ride->trip->driver->id;
        $passengerAndDriver = collect(array_merge($passengers, [$driver]));

        if ( false === $passengerAndDriver->contains($userId) ) {
            abort(404);
        }

        $showDetails = $tripMember = null;
        if (str_contains(key($request->toArray()), 'details') || $ride->hasEnded()) {
            $showDetails = true;
            $tripMember = $ride->members()->readyToFly()->memberId($userId)->first();
        } else {
            $rideShared->increment('views');
        }

        return frontend_view('ride.tracking-ride', compact('rideId', 'ride', 'userId', 'user', 'showDetails', 'tripMember'));
    }
}
