<?php

namespace App\Http\Controllers\Backend;

use App\Models\Trip;
use App\Models\TripMember;
use App\Models\TripRide;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use View;

class ReportsController extends BackendController
{
    private $thisModule = [
        // @var: Module properties
        'longModuleName'  => 'Reports',
        'shortModuleName' => 'Reports',
        'viewDir'         => 'modules.reports',
        'controller'      => 'reports',

        // @array: Undeletable record id(s)
        'undeleteable'    => [],
        'uneditable'      => [],
    ];

    public function __construct()
    {
        View::share([
            'moduleProperties' => $this->thisModule,
        ]);
    }

    public function dashboard(Request $request)
    {
        $rideIds = TripRide::whereHas('trip', function ($query) {
            $query->notCanceled();
        })->notCanceled()->groupBy('trip_id')->pluck('id');

        $bookingCount    = TripMember::readyToFly()->whereIn('trip_ride_id', $rideIds)->count();
        $bookingDuration = TripRide::ended()->first([DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(started_at, ended_at))/3600) total_hours')]);
        $bookingDuration = ($bookingDuration->exists() ? number_format(abs($bookingDuration->total_hours), 2) : 0) . ' hours';

        $totalFare = TripMember::whereHas('ride', function ($query) {
            $query->ended();
        })->readyToFly()->sum('fare');

        $distance = Trip::whereHas('rides', function ($query) {
            $query->ended();
        })->sum('expected_distance');

        $miles         = $distance / 1609.34;
        $farePerMile   = prefixCurrency(($totalFare > 0) ? number_format($miles / $totalFare, 2) : 0);
        $totalDistance = distanceText($distance);

        $stats                  = new \stdClass;
        $stats->bookingCount    = $bookingCount;
        $stats->bookingDuration = $bookingDuration;
        $stats->totalDistance   = $totalDistance;
        $stats->farePerMile     = $farePerMile;

        return backend_view($this->thisModule['viewDir'] . '.dashboard', compact('stats'));
    }

    public function carStatistics(Request $request)
    {
        $tripTable = (new Trip)->getTable();
        $rideTable = (new TripRide)->getTable();

        $drivers = User::drivers()->active()
            ->select(DB::raw("CONCAT(first_name,' ',last_name) AS name"), 'id')
            ->pluck('name', 'id');

        if ($request->getMethod() == 'POST') {
            $driver = User::drivers()->active()->whereId($request->get('driver_id'))->first();
        } else {
            $driver = User::drivers()->active()->first();
        }

        if (!$driver) {
            $driver = new User;
        }

        $rideDays = TripRide::ended()->has('trip')->whereHas('trip', function ($query) use ($driver) {
            $query->driverId($driver->id);
        })->groupBy(DB::raw('DATE(started_at)'))->get();

        $rideDays = count($rideDays);

        $rideData = DB::table($rideTable)
            ->join($tripTable, $rideTable . '.trip_id', '=', $tripTable . '.id')
            ->where($rideTable . '.ride_status', TripRide::RIDE_STATUS_ENDED)
            ->whereNotNull($rideTable . '.started_at')
            ->whereNotNull($rideTable . '.ended_at')
            ->where($tripTable . '.user_id', $driver->id)
            ->select([DB::raw('SUM(earned_by_driver) as driver_earning, SUM(' . $tripTable . '.expected_distance) as distance')])
            ->first();

        $distance       = $rideData->distance ? distanceText($rideData->distance) : distanceText(0);
        $driver_earning = $rideData->driver_earning ? prefixCurrency($rideData->driver_earning) : prefixCurrency(0);

        return backend_view($this->thisModule['viewDir'] . '.car-statistics', compact(
            'drivers', 'driver', 'rideDays', 'distance', 'driver_earning'
        ));
    }

    public function popularDriver(Request $request)
    {
        $tripTable = (new Trip)->getTable();
        $userTable = (new User)->getTable();

        $passengers = User::users()->active()
            ->select(DB::raw("CONCAT(first_name,' ',last_name) AS name"), 'id')
            ->orderBy('first_name', 'ASC')
            ->pluck('name', 'id')->toArray();

        if ($request->getMethod() == 'POST' && $request->get('passenger_id')) {
            $passenger = User::users()->active()->whereId($request->get('passenger_id'))->first();
            $rideIds   = TripMember::readyToFly()->memberId($passenger->id)->pluck('trip_ride_id');

            $tripIds = Trip::whereHas('rides', function ($query) use ($rideIds) {
                $query->ended()->whereIn('id', $rideIds);
            })->pluck('id');
        } else {
            $tripIds = Trip::whereHas('rides', function ($query) {
                $query->ended()->whereHas('members', function ($query) {
                    $query->readyToFly();
                });
            })->pluck('id');
        }

        $tripStats = Trip::whereIn($tripTable . '.id', $tripIds)
            ->join($userTable, $tripTable . '.user_id', '=', $userTable . '.id')
            ->select($tripTable . '.user_id', DB::raw("CONCAT(first_name,' ',last_name) AS name"), DB::raw('COUNT(*) number_of_uses'))
            ->groupBy($tripTable . '.user_id')
            ->get(['name']);

        return backend_view($this->thisModule['viewDir'] . '.popular-driver', compact(
            'passengers', 'tripStats'
        ));
    }

    public function driverEarning(Request $request)
    {
        $tripTable = (new Trip)->getTable();
        $rideTable = (new TripRide)->getTable();
        $userTable = (new User)->getTable();

        $drivers = User::drivers()->active()
            ->select(DB::raw("CONCAT(first_name,' ',last_name) AS name"), 'id')
            ->pluck('name', 'id')->toArray();

        $earnings     = [];
        $totalEarning = 0;

        if ($request->getMethod() == 'POST') {
            $earnings = DB::table($tripTable)
                ->join($userTable, $tripTable . '.user_id', '=', $userTable . '.id')
                ->join($rideTable, $tripTable . '.id', '=', $rideTable . '.trip_id')
                ->where($tripTable . '.user_id', $request->get('driver_id'))
                ->where('earned_by_driver', '!=', 0)
                ->whereNotNull($rideTable . '.ended_at');

            if ($request->get('startDate') && $request->get('endDate')) {
                $earnings
                    ->whereDate($rideTable . '.ended_at', '>=', $request->get('startDate'))
                    ->whereDate($rideTable . '.ended_at', '<=', $request->get('endDate'));
            }

            $earnings = $earnings->select($tripTable . '.id', DB::raw("CONCAT(first_name,' ',last_name) AS name"), 'earned_by_driver', $rideTable . '.ended_at')
                ->groupBy($tripTable . '.id')
                ->get(['id', 'name', 'earned_by_driver', 'ended_at']);

            if (count($earnings)) {
                foreach ($earnings as $earning) {
                    $totalEarning += $earning->earned_by_driver;
                }
            }
        }

        return backend_view($this->thisModule['viewDir'] . '.driver-earning', compact(
            'drivers', 'earnings', 'totalEarning'
        ));
    }
}
