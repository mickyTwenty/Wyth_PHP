<?php

namespace App\Http\Controllers\Backend;

use App\Models\Transaction;
use App\Models\Trip;
use App\Models\TripEarning;
use App\Models\TripMember;
use App\Models\TripRide;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Yajra\Datatables\Datatables;

class TripController extends BackendController
{
    protected $thisModule = [
        'longModuleName'  => 'Payments',
        'shortModuleName' => 'Payment',
        'viewDir'         => 'modules.payments',
        'controller'      => 'trips/payments',
    ];

    public function __construct()
    {
        View::share([
            'moduleProperties' => $this->thisModule,
        ]);
    }

    public function payments(Request $request)
    {
        return backend_view('modules.payments.index');
    }

    public function paymentsData(Datatables $datatables)
    {
        $this->thisModule['viewDir'] = 'modules.payments';

        $query = Trip::has('driver')->with(['driver', 'rides.members', 'rides.earning'])->whereHas('rides', function ($query) {
            $query->ended()->whereHas('members', function ($query) {
                $query->readyToFly();
            });
        });

        return $datatables->eloquent($query)
            ->order(function ($query) {
                $usersTable  = (new User)->getTable();
                $tripsTable  = (new Trip)->getTable();
                $rideTable   = (new TripRide)->getTable();
                $memberTable = (new TripMember)->getTable();
                $earningTable = (new TripEarning)->getTable();

                $renameColumns = [
                    'estimates' => 'min_estimates',
                ];

                $column = data_get(request()->input('columns'), request()->input('order.0.column'), []);
                if (in_array($column['data'], ['driver'])) {
                    $query->leftJoin($usersTable, $usersTable . '.id', '=', $tripsTable . '.user_id')
                        ->select([$tripsTable . '.*', $usersTable . '.first_name'])
                        ->orderBy($usersTable . '.first_name', request()->input('order.0.dir'));
                } else if (in_array($column['data'], ['initiated_by'])) {
                    $query->leftJoin($usersTable, $usersTable . '.id', '=', $tripsTable . '.initiated_by')
                        ->select([$tripsTable . '.*', $usersTable . '.first_name'])
                        ->orderBy($usersTable . '.first_name', request()->input('order.0.dir'));
                } else if (in_array($column['data'], ['earning'])) {
                    $query->leftJoin($rideTable, $tripsTable . '.id', '=', $rideTable . '.trip_id')
                        ->leftJoin($earningTable, $rideTable . '.id', '=', $earningTable . '.trip_ride_id')
                        ->select([$tripsTable . '.*', DB::raw('SUM(' . $earningTable . '.earning) as earning')])
                        ->groupBy($tripsTable . '.id')
                        ->orderBy('earning', request()->input('order.0.dir'));
                } else if (in_array($column['data'], ['commission'])) {
                    $query->leftJoin($rideTable, $tripsTable . '.id', '=', $rideTable . '.trip_id')
                        ->leftJoin($earningTable, $rideTable . '.id', '=', $earningTable . '.trip_ride_id')
                        ->select([$tripsTable . '.*', DB::raw('SUM(' . $earningTable . '.commission) as commission')])
                        ->groupBy($tripsTable . '.id')
                        ->orderBy('commission', request()->input('order.0.dir'));
                } else {
                    $columnName = $column['data'];

                    if (array_key_exists($columnName, $renameColumns)) {
                        $columnName = $renameColumns[$columnName];
                    }

                    $query->orderBy($columnName, request()->input('order.0.dir'));
                }
            })
            ->editColumn('id', function ($trip) {
                return $trip->id;
            })
            ->editColumn('driver', function ($trip) {
                return $trip->driver->full_name;
            })
            ->editColumn('initiated_by', function ($trip) {
                return $trip->passenger->full_name;
            })
            ->editColumn('initiated_type', function ($trip) {
                return ucfirst($trip->initiated_type);
            })
            ->editColumn('estimates', function ($trip) {
                return $trip->estimates;
            })
            ->editColumn('earning', function ($trip) {
                return prefixCurrency($trip->getEarningObject('earning'));
            })
            ->editColumn('commission', function ($trip) {
                return prefixCurrency($trip->getEarningObject('commission'));
            })
            ->editColumn('is_request', function ($trip) {
                return $trip->status_text_formatted;
            })
            ->editColumn('is_roundtrip', function ($trip) {
                return $trip->round_trip_status;
            })
            ->editColumn('trip_name', function ($trip) {
                return $trip->trip_name;
            })
            ->editColumn('origin_title', function ($trip) {
                return $trip->origin_title;
            })
            ->editColumn('destination_title', function ($trip) {
                return $trip->destination_title;
            })
            ->editColumn('created_at', function ($record) {
                return $record->created_at->format(constants('back.theme.modules.datetime_format'));
            })
            ->addColumn('action', function ($record) {
                return backend_view($this->thisModule['viewDir'] . '.action', compact('record'));
            })
            ->rawColumns(['id', 'driver', 'initiated_by', 'initiated_type', 'estimates', 'is_request', 'is_roundtrip', 'trip_name', 'created_at', 'action'])
            ->make(true);
    }

    public function paymentDetail(Request $request, Trip $record)
    {
        $record->load('rides.earning');

        $rideIds              = $record->rides()->pluck('id');
        $transactions         = Transaction::whereIn('trip_ride_id', $rideIds)->notRefunded()->get();
        $refundedTransactions = Transaction::whereIn('trip_ride_id', $rideIds)->refunded()->get();
        $isDriverPaid         = (bool) ($record->rides->sum(function($row) {return $row->earning->is_paid;}) / count($record->rides));

        return backend_view($this->thisModule['viewDir'] . '.detail', compact('record', 'transactions', 'refundedTransactions', 'isDriverPaid'));
    }

    public function canceledTrips(Request $request)
    {
        $this->thisModule = [
            'longModuleName'  => 'Canceled Trips',
            'shortModuleName' => 'Canceled Trips',
            'viewDir'         => 'modules.trips',
            'controller'      => 'trips',
        ];

        View::share([
            'moduleProperties' => $this->thisModule,
        ]);

        return backend_view('modules.trips.canceled');
    }

    public function canceledTripsData(Datatables $datatables)
    {
//        $query = Trip::has('driver')->has('passenger')->canceled();
        $query = TripRide::whereHas('trip', function($q){

            $q->has('driver')->has('passenger');//->canceled();

        })->with(['trip', 'trip.driver', 'trip.passenger'])->whereNotNull("trip_rides.canceled_at");

        return $datatables->eloquent($query)
            ->order(function ($query) {
                $usersTable     = (new User)->getTable();
                $tripsTable     = (new Trip)->getTable();
                $tripRideTable  = (new TripRide)->getTable();

                $renameColumns = [
                    'estimates' => 'min_estimates',
                ];

                $column = data_get(request()->input('columns'), request()->input('order.0.column'), []);
                if (in_array($column['data'], ['driver']))
                {
                    $query->join($tripsTable,   $tripsTable . '.id', '=', $tripRideTable . '.trip_id')
                        ->leftJoin($usersTable, $usersTable . '.id', '=', $tripsTable . '.user_id')
                        ->select([$tripRideTable . '.*', $usersTable . '.first_name'])
                        ->orderBy($usersTable . '.first_name', request()->input('order.0.dir'));
                }
                else if (in_array($column['data'], ['initiated_by']))
                {
                    $query->join($tripsTable, $tripsTable . '.id', '=', $tripRideTable . '.trip_id')
                        ->leftJoin($usersTable, $usersTable . '.id', '=', $tripsTable . '.initiated_by')
                        ->select([$tripRideTable . '.*', $usersTable . '.first_name'])
                        ->orderBy($usersTable . '.first_name', request()->input('order.0.dir'));
                }
                else if (in_array($column['data'], ['canceled_at']))
                {
                    $query->orderBy($column['data'], request()->input('order.0.dir'));
                }
                else
                {
                    $columnName = $column['data'];

                    if (array_key_exists($columnName, $renameColumns))
                    {
                        $columnName = $renameColumns[$columnName];
                    }

                    $query->join($tripsTable, $tripsTable . '.id', '=', $tripRideTable . '.trip_id')
                        ->select([$tripRideTable . '.*'])
                        ->orderBy($tripsTable . '.' . $columnName, request()->input('order.0.dir'));
                }
            })
            ->editColumn('id', function ($trip) {
                return $trip->trip->id;
            })
            ->editColumn('driver', function ($trip) {
                return $trip->trip->driver->full_name;
            })
            ->editColumn('initiated_by', function ($trip) {
                return $trip->trip->passenger->full_name;
            })
            ->editColumn('initiated_type', function ($trip) {
                return ucfirst($trip->trip->initiated_type);
            })
            ->editColumn('estimates', function ($trip) {
                return $trip->trip->estimates;
            })
            ->editColumn('is_request', function ($trip) {
                return $trip->trip->status_text_formatted;
            })
            ->editColumn('is_roundtrip', function ($trip) {
                return $trip->trip->round_trip_status;
            })
            ->editColumn('trip_name', function ($trip) {
                return $trip->trip->trip_name;
            })
            ->editColumn('origin_title', function ($trip) {
                return $trip->trip->origin_title;
            })
            ->editColumn('destination_title', function ($trip) {
                return $trip->trip->destination_title;
            })
            ->editColumn('canceled_at', function ($record) {
                return $record->canceled_at->format(constants('back.theme.modules.datetime_format'));
            })
            ->rawColumns(['id', 'driver', 'initiated_by', 'initiated_type', 'estimates', 'is_request', 'is_roundtrip', 'trip_name', 'canceled_at'])
            ->make(true);
    }

    public function ridesListing(Request $request)
    {
        $this->thisModule = [
            'longModuleName'  => 'Trips Listing',
            'shortModuleName' => 'Trips Listing',
            'viewDir'         => 'modules.trips',
            'controller'      => 'trips',
        ];

        View::share([
            'moduleProperties' => $this->thisModule,
        ]);

        return backend_view('modules.trips.listing');
    }

    public function ridesListingData(Datatables $datatables, Request $request)
    {
        $this->thisModule['viewDir'] = 'modules.trips';

        $query = TripRide::withoutGlobalScopes()->has('trip.driver')->with(['trip.driver']);

        switch ($request->get('status')) {
            case 'completed':
                $query->whereNotNull('ended_at')->whereNull('canceled_at');
                break;
            case 'in-process':
                $query->whereNull('ended_at')->whereNotNull('started_at')->whereNull('canceled_at');
                break;
            case 'upcoming':
                /*$query->whereNull('ended_at')->whereNull('started_at')->upcoming()->whereHas('trip', function ($query) {
                    $query->whereNull('canceled_at');
                });*/
                $query->whereNull('ended_at')->whereNull('started_at')->upcoming()->whereNull('canceled_at');
                break;
            case 'canceled':
                /*$query->whereHas('trip', function ($query) {
                    $query->whereNotNull('canceled_at');
                });*/
                $query->whereNotNull('canceled_at');
                break;
            default:
                # code...
                break;
        }

        return $datatables->eloquent($query)
            ->order(function ($query) {
                $usersTable = (new User)->getTable();
                $tripsTable = (new Trip)->getTable();
                $rideTable  = (new TripRide)->getTable();

                $renameColumns = [
                    // None
                ];

                $column = data_get(request()->input('columns'), request()->input('order.0.column'), []);
                if (in_array($column['data'], ['driver'])) {
                    $query->leftJoin($tripsTable, $tripsTable . '.id', '=', $rideTable . '.trip_id')
                        ->leftJoin($usersTable, $usersTable . '.id', '=', $tripsTable . '.user_id')
                        ->select([$tripsTable . '.*', $rideTable . '.*', $usersTable . '.first_name'])
                        ->orderBy($usersTable . '.first_name', request()->input('order.0.dir'));
                } else if (in_array($column['data'], ['is_roundtrip'])) {
                    $query->leftJoin($tripsTable, $tripsTable . '.id', '=', $rideTable . '.trip_id')
                        ->select([$tripsTable . '.*', $rideTable . '.*'])
                        ->orderBy($tripsTable . '.is_roundtrip', request()->input('order.0.dir'));
                } else {
                    $columnName = $column['data'];

                    if (array_key_exists($columnName, $renameColumns)) {
                        $columnName = $renameColumns[$columnName];
                    }

                    $query->orderBy($columnName, request()->input('order.0.dir'));
                }
            })
            ->editColumn('id', function ($ride) {
                return $ride->trip->id;
            })
            ->editColumn('driver', function ($ride) {
                return $ride->trip->driver->full_name;
            })
            ->editColumn('is_roundtrip', function ($ride) {
                return $ride->trip->round_trip_status;
            })
            ->editColumn('trip_name', function ($ride) {
                return $ride->trip->trip_name;
            })
            ->editColumn('origin_title', function ($ride) {
                return $ride->origin_title;
            })
            ->editColumn('destination_title', function ($ride) {
                return $ride->destination_title;
            })
            ->editColumn('status', function ($ride) {
                if (null !== $ride->canceled_at)
                {
                    return '<span class="label label-danger">Canceled</span>';
                }
                else if (null !== $ride->started_at)
                {
                    if (null !== $ride->ended_at)
                    {
                        return '<span class="label label-success">Completed</span>';
                    }
                    else
                    {
                        return '<span class="label label-success">In-Process</span>';
                    }
                }
                else if ($ride->isUpcoming())
                {
                    return '<span class="label label-info">Upcoming</span>';
                }
                else
                {
                    return '<span class="label label-warning">Other</span>';
                }
            })
            ->editColumn('created_at', function ($record) {
                return $record->created_at->format(constants('back.theme.modules.datetime_format'));
            })
            ->addColumn('action', function ($record) {
                $cancelable = $record->isUpcoming() && in_array($record->ride_status, Trip::statusesOfDriverCanCancelTrip());

                return backend_view($this->thisModule['viewDir'] . '.action', compact('record', 'cancelable'));
            })
            ->rawColumns(['id', 'driver', 'is_roundtrip', 'status', 'trip_name', 'created_at', 'action'])
            ->make(true);
    }

    public function cancelTrip(Request $request, TripRide $ride)
    {
        $trip = $ride->trip;

        if ($trip->isCanceled()) {
            return redirect()->route('backend.trips.listing')->with('alert-danger', 'This trip is already marked as cancelled.');
        }

        if (!in_array($ride->ride_status, Trip::statusesOfDriverCanCancelTrip())) {
            return redirect()->route('backend.trips.listing')->with('alert-danger', 'You cannot cancel this trip at this stage.');
        }

        $trip->load('rides.members');
        $trip->cancelRideByAdmin();

        return redirect()->route('backend.trips.listing')->with('alert-success', 'Trip has been cancelled successfully.');
    }

    public function hotDestinations(Request $request)
    {
        $records = DB::table('trip_rides')->whereNotNull('started_at')->whereNotNull('ended_at')
            ->whereNotNull('destination_city')
            ->groupBy('destination_city')
            ->select('destination_city', DB::raw('count(*) as total'))
            ->selectRaw('UPPER(SUBSTRING(destination_city, 1, 2)) as shortname')
            ->get();


        return backend_view('modules.trips.hot-destinations', compact('records'));
    }
}
