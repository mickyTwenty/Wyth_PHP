<?php

namespace App\Http\Controllers\Backend;

use App\Http\Requests\Backend\UserRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserVerification;
use DB;
use Illuminate\Http\Request;
use View;
use Yajra\Datatables\Datatables;

class StatsController extends BackendController
{
    private $thisModule = [
        // @var: Module properties
        'longModuleName'  => 'User Stats',
        'shortModuleName' => 'Stats',
        'viewDir'         => 'modules.stats',
        'controller'      => 'user-stats',

        // @var: Module's field type
        'field.images'   => ['profile_picture'],

        // @array: Undeletable record id(s)
        'undeleteable'    => [1],
        'uneditable'      => [1],
    ];

    public function __construct()
    {
        View::share([
            'moduleProperties' => $this->thisModule,
        ]);
    }

    public function paymentTransaction()
    {
        return backend_view( $this->thisModule['viewDir'] . '.index' );
    }

    public function userStats(Request $request, User $record)
    {
        $allTransactions = Transaction::where([
            'receiver_id' => $record->id,
            'is_refunded' => 0,
        ])->get();

        $pendingCommission = $allTransactions->filter(function ($record) {
            return ($record->is_earned == 0 && $record->is_paid == 0);
        });
        $earnedCommission = $allTransactions->filter(function ($record) {
            return ($record->is_earned == 1);
        });
        $paidCommission = $allTransactions->filter(function ($record) {
            return ($record->is_paid == 1);
        });

        return backend_view( $this->thisModule['viewDir'] . '.detail', compact('record', 'pendingCommission', 'earnedCommission', 'paidCommission') );
    }

    public function data(Datatables $datatables)
    {
        $data = $datatables->eloquent(User::users())
            ->filter(function ($query) {
                if ( request()->has('search.value') ) {
                    $query->search( request('search.value') );
                }
            }, false)
            ->editColumn('full_name', function ($user) {
                return $user->full_name;
            })
            ->editColumn('active', function ($user) {
                return $user->status_text_formatted;
            })
            ->editColumn('role_id', function ($user) {
                return ucfirst($user->user_role_key);
            })
            ->editColumn('created_at', function ($record) {
                return $record->created_at->format(constants('back.theme.modules.datetime_format'));
            })
            ->addColumn('action', function ($record) {
                return backend_view($this->thisModule['viewDir'] . '.action', compact('record'));
            })
            ->rawColumns(['full_name', 'active', 'verified', 'profile_picture', 'action'])
            ->make(true);

        return $data;
    }
}
