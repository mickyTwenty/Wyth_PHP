<?php

namespace App\Http\Controllers\Backend;

use App\Events\Api\JWTUserUpdate;
use App\Events\CreateUserFromBackend;
use App\Events\UserPasswordChanged;
use App\Http\Requests\PassengerRegisterRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Log;
use View;

class PassengerController extends BackendController
{
    private $thisModule = [
        // @var: Module properties
        'longModuleName'  => 'Passengers',
        'shortModuleName' => 'Passengers',
        'viewDir'         => 'passengers',
        'controller'      => 'passengers',
    ];

    public function __construct()
    {
        View::share([
            'moduleProperties' => $this->thisModule,
        ]);
    }

    public function createPassenger(Request $request)
    {
        $states = State::listStates(Country::DEFAULT_COUNTRY_ID);
        
        if (old('state')) {
            $cities = City::listCities(old('state'));
        } else {
            $cities = [];
        }

        return backend_view($this->thisModule['viewDir'] . '.create', compact('states', 'cities'));
    }

    public function storePassenger(PassengerRegisterRequest $request)
    {
        $input             = $request->all();
        $input['password'] = bcrypt($input['password']);
        $input['active']   = 0;

        if ($request->has('phone')) {
            try {
                $input['phone'] = phone($request->get('phone'), 'US')->formatE164();
            } catch (\Exception $e) {
                $input['phone'] = '';
            }
        }

        // In this project, there are first & last name fields.
        // So split name is not required here.
        // list($input['first_name'], $input['last_name']) = str_split_name($input['full_name']);

        try {
            $input['role_id'] = User::getRoleIdByUserType('normal');

            if ($request->hasFile('profile_picture')) {
                $imageName = \Illuminate\Support\Str::random(12) . '.' . $request->file('profile_picture')->getClientOriginalExtension();
                $path      = public_path(config('constants.front.dir.profilePicPath'));
                $request->file('profile_picture')->move($path, $imageName);

                //if ( Image::open( $path . '/' . $imageName )->scaleResize(200, 200)->save( $path . '/' . $imageName ) ) {
                $input['profile_picture'] = $imageName;
                //}
            }

            DB::beginTransaction();

            $user = User::create($input);
            $user = User::find($user->id); // Just because we need complete model attributes for event based activities

            $user->email_verification = 1;

            $metaDataToUpdate = array_filter([
                'gender'               => $request->get('gender', false),
                'school_name'          => $request->get('school_name', false),
                'student_organization' => $request->get('student_organization', false),
                'graduation_year'      => $request->get('graduation_year', false),
                'postal_code'          => $request->get('postal_code', false),
                'birth_date'           => $request->get('birth_date', false),
            ]);

            if (!empty($metaDataToUpdate)) {
                $user->setMeta($metaDataToUpdate);
            }

            $user->save();
            DB::commit();

            // Fire user registration event
            event(new CreateUserFromBackend($user, $request));
            return redirect('backend/users/index')->with('alert-success', 'Account has been created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            Log::debug('CreatePassenger: ' . $e->getMessage() . ' File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            return redirect()->back()->withInput()->with('alert-danger', 'Something went wrong! account could not be created.');
        }
    }

    public function editPassenger(Request $request, User $record)
    {
        if (!$record->isNormalUser()) {
            abort(404);
        }

        $states = State::listStates(Country::DEFAULT_COUNTRY_ID);
        $cities = City::listCities($record->state);
        return backend_view($this->thisModule['viewDir'] . '.edit', compact('record', 'states', 'cities'));
    }

    public function updatePassenger(PassengerRegisterRequest $request, User $record)
    {
        if (!$record->isNormalUser()) {
            abort(404);
        }

        // This will work with empty fields.
        $dataToUpdate = array_filter([
            'first_name'      => $request->get('first_name', false),
            'last_name'       => $request->get('last_name', false),
            'email'           => $request->get('email', false),
            'address'         => $request->get('address', false),
            'state'           => $request->get('state', false),
            'city'            => $request->get('city', false),
            'profile_picture' => $request->get('profile_picture', false),
        ], function ($a) {return false !== $a;});

        if ($request->has('phone')) {
            try {
                $dataToUpdate['phone'] = phone($request->get('phone'), 'US')->formatE164();
            } catch (\Exception $e) {
                $dataToUpdate['phone'] = '';
            }
        }

        if ($request->has('password') && $request->get('password', '') !== '') {
            $dataToUpdate['password'] = bcrypt($request->get('password'));
        }

        if ($request->hasFile('profile_picture')) {

            if (!in_array($request->file('profile_picture')->getClientOriginalExtension(), ['jpg', 'jpeg', 'png', 'bmp'])) {
                return redirect()->back()->withInput()->with('alert-danger', 'Invalid profile_picture given. Please use only image as your profile picture.');
            }

            $imageName = $record->id . '-' . str_random(12) . '.' . $request->file('profile_picture')->getClientOriginalExtension();
            $path      = public_path(config('constants.front.dir.profilePicPath'));
            $request->file('profile_picture')->move($path, $imageName);

            //if ( Image::open( $path . '/' . $imageName )->scaleResize(200, 200)->save( $path . '/' . $imageName ) ) {
            $dataToUpdate['profile_picture'] = $imageName;
            //}
        }

        // Here i'm receiving empty fields as null and DB constraints doesn't allow to set null.
        foreach ($dataToUpdate as $key => $value) {

            // Set null value for columns other than text|varchar
            if (in_array($key, ['birth_date'])) {
                $dataToUpdate[$key] = $value ?: null;
            } else {
                $dataToUpdate[$key] = strval($value);
            }
        }

        // User meta attributes
        $metaDataToUpdate = array_filter([
            'gender'               => $request->get('gender', false),
            'school_name'          => $request->get('school_name', false),
            'student_organization' => $request->get('student_organization', false),
            'graduation_year'      => $request->get('graduation_year', false),
            'postal_code'          => $request->get('postal_code', false),
            'birth_date'           => $request->get('birth_date', false),
        ], function ($a) {return false !== $a;});

        if (!empty($metaDataToUpdate)) {
            $record->setMeta($metaDataToUpdate);
        }

        $record->update($dataToUpdate);

        // Fire user update event
        event(new JWTUserUpdate($record));
        return redirect('backend/users/index')->with('alert-success', 'User updated successfully.');
    }
}
