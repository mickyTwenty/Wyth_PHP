<?php

namespace App\Http\Controllers\Backend;

use App\Models\TripRating;
use App\Models\User;
use Illuminate\Http\Request;
use View;
use Yajra\Datatables\Datatables;

class ReviewController extends BackendController
{
    private $thisModule = [
        // @var: Module properties
        'longModuleName'  => 'Reviews',
        'shortModuleName' => 'Reviews',
        'viewDir'         => 'modules.reviews',
        'controller'      => 'reviews',
    ];

    public function __construct()
    {
        View::share([
            'moduleProperties' => $this->thisModule,
        ]);
    }

    public function index()
    {
        return backend_view($this->thisModule['viewDir'] . '.index');
    }

    public function data(Datatables $datatables)
    {
        $query = TripRating::with(['rater' => function($query) {
            return $query;
            // return $query->withTrashed();
        }])->with(['ratee' => function($query) {
            return $query;
            // return $query->withTrashed();
        }])->with('tripRide.trip');

        return $datatables->eloquent($query)
            ->order(function ($query) {
                $usersTable = (new User)->getTable();
                $ratingTable = (new TripRating)->getTable();

                $column = data_get(request()->input('columns'), request()->input('order.0.column'), []);
                if (in_array($column['data'], ['rater'])) {
                    $query->leftJoin($usersTable, $usersTable . '.id', '=', $ratingTable . '.rater_id')
                        ->select([$ratingTable . '.*', $usersTable . '.first_name'])
                        ->orderBy($usersTable . '.first_name', request()->input('order.0.dir'))
                        ->groupBy($usersTable . '.id');
                } else if (in_array($column['data'], ['ratee'])) {
                    $query->leftJoin($usersTable, $usersTable . '.id', '=', $ratingTable . '.rater_id')
                        ->select([$ratingTable . '.*', $usersTable . '.first_name'])
                        ->orderBy($usersTable . '.first_name', request()->input('order.0.dir'))
                        ->groupBy($usersTable . '.id');
                } else {
                    $query->orderBy($column['data'], request()->input('order.0.dir'));
                }

            })
            ->editColumn('id', function ($tripRating) {
                return $tripRating->id;
            })
            ->editColumn('rater', function ($tripRating) {
                return $tripRating->rater->full_name;
            })
            ->editColumn('rater_type', function ($tripRating) {
                return ucfirst($tripRating->rater_type);
            })
            ->editColumn('ratee', function ($tripRating) {
                return $tripRating->ratee->full_name;
            })
            ->editColumn('ratee_type', function ($tripRating) {
                return ucfirst($tripRating->ratee_type);
            })
            ->editColumn('trip_name', function ($tripRating) {
                return $tripRating->tripRide->trip->trip_name;
            })
            ->editColumn('rating', function ($tripRating) {
                return $tripRating->rating;
            })
            ->editColumn('feedback', function ($tripRating) {
                return $tripRating->feedback ?: '';
            })
            ->editColumn('is_approved', function ($tripRating) {
                return $tripRating->status_text_formatted;
            })
            ->editColumn('created_at', function ($record) {
                return $record->created_at->format(constants('back.theme.modules.datetime_format'));
            })
            ->addColumn('action', function ($record) {
                return backend_view($this->thisModule['viewDir'] . '.action', compact('record') + [
                    'edit' => false,
                ]);
            })
            ->rawColumns(['id', 'rater', 'rater_type', 'ratee', 'ratee_type', 'trip_name', 'rating', 'feedback', 'is_approved', 'action'])
            ->make(true);
    }

    public function destroy(TripRating $record)
    {
        $record->delete();

        session()->flash('alert-success', str_singular($this->thisModule['shortModuleName']) . ' has been deleted successfully!');
        return redirect('backend/' . $this->thisModule['controller']);
    }

    public function approve(TripRating $record)
    {
        $record->approve();

        session()->flash('alert-success', str_singular($this->thisModule['shortModuleName']) . ' has been approved successfully!');
        return redirect('backend/' . $this->thisModule['controller']);
    }

    public function disapprove(TripRating $record)
    {
        $record->disapprove();

        session()->flash('alert-success', str_singular($this->thisModule['shortModuleName']) . ' has been disapproved successfully!');
        return redirect('backend/' . $this->thisModule['controller']);
    }
}
