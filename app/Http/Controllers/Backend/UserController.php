<?php

namespace App\Http\Controllers\Backend;

use App\Jobs\PushNotificationJob;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserMeta;
use App\Models\UserVerification;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use View;
use Yajra\Datatables\Datatables;

class UserController extends BackendController
{
    private $thisModule = [
        // @var: Module properties
        'longModuleName'  => 'Users',
        'shortModuleName' => 'Users',
        'viewDir'         => 'users',
        'controller'      => 'users',

        // @var: Module's field type
        'field.images'    => ['profile_picture'],

        // @array: Undeletable record id(s)
        'undeleteable'    => [1, 2],
        'uneditable'      => [1],
    ];

    public function __construct()
    {
        $this->thisModule['undeleteable'] = array_values(array_unique(array_merge([User::ADMIN_USER_ID, User::CLIENT_USER_ID], $this->thisModule['undeleteable'])));

        View::share([
            'moduleProperties' => $this->thisModule,
        ]);
    }

    public function index()
    {
        $states     = State::listStates(Country::DEFAULT_COUNTRY_ID)->toArray();
        $firstState = key($states);
        $states     = ['' => 'Select State'] + $states;
        $cities     = ['' => 'Select City'] + City::listCities($firstState)->toArray();
        $genders    = ['' => 'Select Gender', 'Male' => 'Male', 'Female' => 'Female'];
        $ageRanges  = ['' => 'Select Age Range', '10-17' => '10-17', '18-25' => '18-25', '26-35' => '26-35', '36-50' => '36-50', '50' => '50+'];
        $userTypes  = ['' => 'All', User::ROLE_NORMAL_USER => 'Passenger', User::ROLE_DRIVER => 'Driver'];

        return backend_view($this->thisModule['viewDir'] . '.index', compact('states', 'cities', 'genders', 'ageRanges', 'userTypes', 'schools'));
    }

    public function verificationListPending()
    {
        $records = User::has('verification')->with('verification')->get();

        $moduleProperties = $this->thisModule + [
            'longModuleName' => 'User Verification',
        ];

        return backend_view($this->thisModule['viewDir'] . '.verification_list', compact('records', 'moduleProperties'));
    }

    public function handleVerification(Request $request, User $record)
    {
        if ($record->isAdmin()) {
            abort(404);
        }

        $verifyRequest = UserVerification::find($request->get('id'));

        if (!$verifyRequest) {
            abort(404);
        }

        if ($request->get('action') == 'approve') {
            $record->verify();

            $verifyRequest->is_approved = 1;
            $verifyRequest->save();

            // Email::shoot( $record->email, 'Verification Request Processed', sprintf("Dear %s,\n\nYour verification request has been approved, now verify badge will display along with your profile.\n\nThanks", trim($record->full_name)) );
            $record->notify(new \App\Notifications\Backend\UserVerificationRequestAccepted($record));

            session()->flash('alert-success', 'Request has been verified successfully!');

        } else if ($request->get('action') == 'reject') {
            // $record->unverify();

            $verifyRequest->is_approved = -1;
            $verifyRequest->save();

            // Email::shoot( $record->email, 'Verification Request Processed', sprintf("Dear %s,\n\nYour verification request has been rejected, Please ensure that you filled all required details properly and try again.\n\nThanks", trim($record->full_name)) );
            $record->notify(new \App\Notifications\Backend\UserVerificationRequestRejected($record));

            session()->flash('alert-success', 'Request has been rejected successfully!');

        } else {
            session()->flash('alert-danger', 'Error while handle request, please try again!');
        }

        return redirect('backend/' . $this->thisModule['controller'] . '/verification');
    }

    public function data(Datatables $datatables, Request $request)
    {
        $state        = $request->get('state');
        $city         = $request->get('city');
        $gender       = $request->get('gender');
        $age          = $request->get('age');
        $role_id      = $request->get('role_id');
        $school_name  = $request->get('school_name');
        $verification = $request->get('email_verification');

        $query = User::users();

        if ($state) {
            $query->where('state', $state);
        }

        if ($city) {
            $query->where('city', $city);
        }

        if ($role_id) {
            $query->where('role_id', $role_id);
        }

        if ($gender) {
            $query->whereHas('metas', function ($query) use ($gender) {
                $query->where('key', 'gender')->where('value', 'LIKE', $gender . '%');
            });
        }

        if ($age) {
            $ageArray = explode('-', $age);
            $query->whereHas('metas', function ($query) use ($ageArray) {
                if (isset($ageArray[1])) {
                    $query->where('key', 'birth_date')
                        ->whereRaw('STR_TO_DATE(value, \'%m/%d/%Y\') >= "' . Carbon::now()->subYear($ageArray[1])->toDateString() . '"')
                        ->whereRaw('STR_TO_DATE(value, \'%m/%d/%Y\') <= "' . Carbon::now()->subYear($ageArray[0])->toDateString() . '"');
                } else {
                    $query->where('key', 'birth_date')
                        ->whereRaw('STR_TO_DATE(value, \'%m/%d/%Y\') < "' . Carbon::now()->subYear($ageArray[0])->toDateString() . '"');
                }
            });
        }

        if ($school_name) {
            $query->whereHas('metas', function ($query) use ($school_name) {
                $query->where('key', 'school_name')->where('value', 'LIKE', '%' . $school_name . '%');
            });
        }

        if (!is_null($verification)) {
            $query->where('email_verification', $verification);
        }

        return $datatables->eloquent($query)
            ->filter(function ($query) {
                if (request()->has('search.value')) {
                    $query->search(request('search.value'));
                }
            }, false)
            ->order(function ($query) {

                $column = data_get(request()->input('columns'), request()->input('order.0.column'), []);

                if (in_array($column['data'], ['commission_earned'])) {
                    $query->leftJoin((new Transaction)->getTable(), (new User)->getTable() . '.id', '=', (new Transaction)->getTable() . '.receiver_id')
                        ->select([(new User)->getTable() . '.*', (new Transaction)->getTable() . '.' . $column['data']])
                        ->orderBy(\DB::raw('SUM(' . (new Transaction)->getTable() . '.' . $column['data'] . ')'), request()->input('order.0.dir'))
                        ->groupBy((new User)->getTable() . '.id');
                } else {
                    $query->orderBy($column['data'], request()->input('order.0.dir'));
                }

            })
            ->editColumn('first_name', function ($user) {
                return $user->first_name;
            })
            ->editColumn('last_name', function ($user) {
                return $user->last_name;
            })
            ->editColumn('active', function ($user) {
                return $user->status_text_formatted;
            })
            ->editColumn('role_id', function ($user) {
                return ucfirst($user->user_role_key_web);
            })
            ->editColumn('profile_picture', function ($user) {
                return '<a href="' . $user->profile_picture_auto . '" class="cboxImages">' . \Html::image($user->profile_picture_path, null, ['class' => 'img-responsive', 'style' => 'max-width: 50px;max-height: 50px']) . '</a>';
            })
            ->editColumn('created_at', function ($record) {
                return $record->created_at->format(constants('back.theme.modules.datetime_format'));
            })
            ->addColumn('action', function ($record) {
                return backend_view($this->thisModule['viewDir'] . '.action', compact('record') + [
                    'edit' => false,
                ]);
            })
            ->rawColumns(['first_name', 'last_name', 'active', 'profile_picture', 'action'])
            ->make(true);
    }

    public function detail(User $record)
    {
        $userMeta = $record->getMetaMulti(UserMeta::GROUPING_PROFILE);
        $driverMeta = $record->getMetaMulti(UserMeta::GROUPING_DRIVER);
        return backend_view($this->thisModule['viewDir'] . '.detail', compact('record', 'userMeta', 'driverMeta'));
    }

    public function purchases(User $record)
    {
        return backend_view($this->thisModule['viewDir'] . '.purchases', compact('record'));
    }

    public function destroy(User $record)
    {
        if ($record->isAdmin() || in_array($record->id, array_get($this->thisModule, 'undeleteable', []))) {
            abort(404);
        }

        $record->delete();

        session()->flash('alert-success', str_singular($this->thisModule['shortModuleName']) . ' has been deleted successfully!');
        return redirect('backend/' . $this->thisModule['controller']);
    }

    public function block(User $record)
    {
        if ($record->isAdmin()) {
            abort(404);
        }

        $record->deactivate();

        session()->flash('alert-success', str_singular($this->thisModule['shortModuleName']) . ' has been blocked successfully!');
        return redirect('backend/' . $this->thisModule['controller']);
    }

    public function unblock(User $record)
    {
        if ($record->isAdmin()) {
            abort(404);
        }

        $record->activate();

        session()->flash('alert-success', str_singular($this->thisModule['shortModuleName']) . ' has been unblocked successfully!');
        return redirect('backend/' . $this->thisModule['controller']);
    }

    public function verify(User $record)
    {
        if ($record->isAdmin()) {
            abort(404);
        }

        $record->verify();

        session()->flash('alert-success', str_singular($this->thisModule['shortModuleName']) . ' has been marked as verified!');
        return redirect('backend/' . $this->thisModule['controller']);
    }

    public function unverify(User $record)
    {
        if ($record->isAdmin()) {
            abort(404);
        }

        $record->unverify();

        session()->flash('alert-success', 'Verified mark has been removed');
        return redirect('backend/' . $this->thisModule['controller']);
    }

    public function pushNotificationUsers()
    {
        $drivers    = ['0' => 'Select All Drivers'] + User::listDrivers()->toArray();
        $passengers = ['0' => 'Select All Passengers'] + User::listPassengers()->toArray();

        return backend_view($this->thisModule['viewDir'] . '.push-notification', compact('drivers', 'passengers'));
    }

    public function sendPushNotification(Request $request)
    {
        $driverIDs    = $request->get('driver_ids', []);
        $passengerIDs = $request->get('passenger_ids', []);
        $message      = $request->get('message');

        $this->validate($request, [
            'message' => 'required|string',
        ]);

        if (!count($driverIDs) && !count($passengerIDs)) {
            return redirect()->back()->with('alert-danger', 'Please select some drivers or passengers!');
        }

        if (count($driverIDs) > 0) {
            if (in_array(0, $driverIDs)) {
                $drivers = User::cachedDrivers();
                foreach ($drivers as $user) {
                    dispatch(new PushNotificationJob($user, $message));
                }
            } else {
                foreach ($driverIDs as $driverID) {
                    $user = User::cachedDrivers($driverID);
                    if ($user) {
                        dispatch(new PushNotificationJob($user, $message));
                    }
                }
            }
        }

        if (count($passengerIDs) > 0) {
            if (in_array(0, $passengerIDs)) {
                $passengers = User::cachedPassengers();
                foreach ($passengers as $user) {
                    dispatch(new PushNotificationJob($user, $message));
                }
            } else {
                foreach ($passengerIDs as $passengerID) {
                    $user = User::cachedPassengers($passengerID);
                    if ($user) {
                        dispatch(new PushNotificationJob($user, $message));
                    }
                }
            }
        }

        return redirect()->back()->with('alert-success', 'Notification has been sent successfully!');
    }
}
