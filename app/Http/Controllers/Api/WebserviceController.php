<?php
namespace App\Http\Controllers\Api;

use App\Classes\Email;
use App\Classes\Facebook as FacebookHelper;
use App\Events\Api\NotificationsListed;
use App\Events\Api\JWTUserLogin;
use App\Events\Api\JWTUserLogout;
use App\Events\Api\JWTUserRegistration;
use App\Events\Api\JWTUserUpdate;
use App\Events\UserEmailAccountVerified;
use App\Events\UserPasswordChanged;
use App\Helpers\RESTAPIHelper;
use App\Http\Requests\Api\UserRegisterRequest;
use App\Http\Requests\Api\UserUpdateRequest;
use App\Models\City;
use App\Models\Industry;
use App\Models\RidePreference;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserFacebook;
use App\Models\TripMember;
use App\Models\School;
use Auth;
use Config;
use Exception;
use Illuminate\Http\Request;
use JWTAuth;
use Propaganistas\LaravelPhone\PhoneNumber;
use Validator;

class WebserviceController extends ApiBaseController {

    public function __construct()
    {
        // Set rate limiter (max 1 hit in 5 minutes)
        if ( app()->environment('production') ) {
            // $this->middleware('jwt.throttle:2,5,emailVerification', ['only' => ['resendVerificationEmail']]);
            // $this->middleware('jwt.throttle:2,5,resetPassword', ['only' => ['resetPassword']]);
        }
    }

    public function initializationConfigs(Request $request)
    {
        $allInOneConfigs                 = [];
        $allInOneConfigs['preferences']  = generatePreferencesResponse(RidePreference::getPreferences());
        $allInOneConfigs['vehicle_type'] = [
            'Coupe',
            'Hatchback',
            'Micro',
            'Mini SUV',
            'Mini Van',
            'Rodster',
            'Sedan',
            'SUV',
            'Van',
        ];
        // $allInOneConfigs['schools'] = School::returnSchoolsForBootMeUp();
        $configs = Setting::extracts([
            'setting.min_estimate',
            'setting.max_estimate',
            'setting.application.transaction_fee',
	    'setting.application.transaction_fee_local',
	    'setting.application.local_max_distance,',
            'setting.hear_about_us_options',
        ]);

        $allInOneConfigs['min_estimate']     = floatval($configs->get('setting.min_estimate', ''));
        $allInOneConfigs['max_estimate']     = floatval($configs->get('setting.max_estimate', ''));
        $allInOneConfigs['transaction_fee']  = floatval($configs->get('setting.application.transaction_fee', ''));
        $allInOneConfigs['transaction_fee_local']  = floatval($configs->get('setting.application.transaction_fee_local', ''));
        $allInOneConfigs['local_max_distance']  = floatval($configs->get('setting.application.local_max_distance', '16093'));
        $allInOneConfigs['reference_source'] = array_merge(call_user_func(function(array $a){sort($a);return $a;}, $configs->get('setting.hear_about_us_options', [])), ['Other']);
        $allInOneConfigs['make']             = \App\Models\CarMake::generateBootMeUpDataCached();

        return RESTAPIHelper::response( $allInOneConfigs );
    }

    public function getSchools(Request $request)
    {
        return RESTAPIHelper::response( School::returnSchoolsForBootMeUp() );
    }

    public function register(UserRegisterRequest $request)
    {
        $input             = $request->all();
        $input['password'] = bcrypt($input['password']);
        $input['active']   = 0;

        if ( $request->has('phone') ) {
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
            $input['role_id']  = User::getRoleIdByUserType( $request->get('user_type', 'normal') );
        } catch (\App\Exceptions\InvalidUserTypeException $e) {
            return RESTAPIHelper::response('Invalid user_type detected.', false);
        }

        if ( $request->hasFile('profile_picture') ) {
            $imageName = \Illuminate\Support\Str::random(12) . '.' . $request->file('profile_picture')->getClientOriginalExtension();
            $path = public_path( config('constants.front.dir.profilePicPath') );
            $request->file('profile_picture')->move($path, $imageName);

            //if ( Image::open( $path . '/' . $imageName )->scaleResize(200, 200)->save( $path . '/' . $imageName ) ) {
                $input['profile_picture'] = $imageName;
            //}
        }

        $user = User::create($input);
        $user = User::find($user->id); // Just because we need complete model attributes for event based activities

        $user->email_verification = User::generateUniqueVerificationCode();

        // Link facebook account if access token found!
        if ( $request->has('facebook_token') ) {
            $fbToken = $request->get('facebook_token');
            $fbUserObject = FacebookHelper::resolveByToken($fbToken);
            if ( $fbUserObject ) {
                $user->addFacebook( $fbUserObject->id, $fbToken );

                // Since user is registering through facebook, mark this account as verified
                // In seatus, email verification is required.
                // $user->email_verification = 1;
            }
        }

        $metaDataToUpdate = array_filter([
            'gender'               =>  $request->get('gender', false),
            'school_name'          =>  $request->get('school_name', false),
            'student_organization' =>  $request->get('student_organization', false),
            'graduation_year'      =>  $request->get('graduation_year', false),
            'postal_code'          =>  $request->get('postal_code', false),
            'birth_date'           =>  $request->get('birth_date', false),
            'reference_source'     =>  $request->get('reference_source', false),
        ]);

        if ( !empty($metaDataToUpdate) ) {
            $user->setMeta( $metaDataToUpdate );
        }

        $user->save();

        // Fire user registration event
        event(new JWTUserRegistration($user));

        if ( $user->email_verification != 1 ) {
            return RESTAPIHelper::response([], true, 'Your account has been registered and email address requires verification. A verification code is sent to your email. Please also check Junk/Spam folder as well.');
        } else {
            return $this->login($request);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'        => 'required|email',
            'password'     => 'required',
            'device_type'  => 'in:ios,android',
            'device_token' => 'min:10',
        ]);

        if ($validator->fails()) {
            return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
        }

        // Login with email OR username supported!
        if ( valid_email($request->get('email')) ) {
            $input = $request->only(['email', 'password']);
        } else {
            $request->merge(['username' => $request->get('email')]);
            $input = $request->only(['username', 'password']);
        }

        // $input['role_id'] = User::getRoleIdByUserType( $request->get('user_type', 'normal') );

        if (!$token = JWTAuth::attempt($input)) {
            return RESTAPIHelper::response('Invalid credentials, please try-again.', false);
        }

        $userData = JWTAuth::toUser($token);

        // @TODO
        // 1. Check from constants if enable single device login

        /* Do your additional/manual validation here like email verification or enable/disable */
        try {
            $userData->validateUserActiveCriteria();
        } catch (\App\Exceptions\UserNotAllowedToLogin $e) {
            return RESTAPIHelper::response($e->getMessage(), false, $e->getResolvedErrorCode());
        }

        if ( constants('api.config.allowSingleDeviceLogin') ) {
            $userData->removeDevice( $token );
        }

        // Add user device
        $userData->addDevice( $request->get('device_token', ''), $request->get('device_type', null), $token );

        // Generate user response
        $result = $this->generateUserProfileResponse( $userData, $token );

        event(new JWTUserLogin($userData));

        return RESTAPIHelper::response( $result );
    }

    public function loginWithFacebook(Request $request)
    {
        $fbToken      = $request->get('facebook_token', '');
        $fbUserObject = FacebookHelper::resolveByToken($fbToken);

        if ( !$fbUserObject ) {
            return RESTAPIHelper::response('Facebook token is invalid', false, 'invalid_fb_token');
        }

        $user = User::getUserByFacebookId( $fbUserObject->id );

        if ( !$user ) {
            return RESTAPIHelper::response('Facebook token validated but signature not found.', false, 'action_signup');
        }

        try {
            $user->validateUserActiveCriteria();
        } catch (\App\Exceptions\UserNotAllowedToLogin $e) {
            return RESTAPIHelper::response($e->getMessage(), false, $e->getResolvedErrorCode());
        }

        $token = JWTAuth::fromUser($user);

        // Add user device
        if ( !empty($request->get('device_token', '')) ) {
            $user->addDevice( $request->get('device_token', ''), $request->get('device_type', null), $token );
        }

        // Generate user response
        $result = $this->generateUserProfileResponse( $user, $token );

        event(new JWTUserLogin($user));

        return RESTAPIHelper::response( $result );
    }

    public function bindAccountWithFacebook(Request $request)
    {
        $me           = $this->getUserInstance();
        $fbToken      = $request->get('facebook_token', '');
        $fbUserObject = FacebookHelper::resolveByToken($fbToken);

        if ( !$fbUserObject ) {
            return RESTAPIHelper::response('Facebook token is invalid', false, 'invalid_fb_token');
        }

        // If we have this facebook account already other than this user, then throw an error.
        if ( UserFacebook::where('facebook_uid', '=', $fbUserObject->id)->where('user_id', '<>', $me->id)->count() ) {
            return RESTAPIHelper::response('Sorry! This facebook account already exist in our system. You may need to integrate a different account.', false, 'account_exist');
        }

        $me->addFacebook( $fbUserObject->id, $fbToken );

        return RESTAPIHelper::emptyResponse();
    }

    public function logout(Request $request)
    {
        try {
            $me = $this->getUserInstance();

            if ( $me ) {
                $me->removeDevice( $this->extractToken() );

                // Fire user logout event
                event(new JWTUserLogout($me, [
                    'sendLogoutPush' => false,
                ]));
            }

            JWTAuth::invalidate( $this->extractToken() );

            // @TODO
            // 1. Send hidden push to device token and expire current jwt auth token

        } catch (Exception $e) {}

        return RESTAPIHelper::emptyResponse();
    }

    public function deleteAccount(Request $request)
    {
        try {
            $me = $this->getUserInstance();

            if ( $me ) {
                $me->removeDevice( $this->extractToken() );

                event(new \App\Events\AccountDeleted($me));
            }

            JWTAuth::invalidate( $this->extractToken() );

        } catch (Exception $e) {}

        return RESTAPIHelper::response([], true, 'Your account has been deleted.' );
    }

    public function resetPassword(Request $request) {
        /*$userRequested = User::users()->whereEmail($request->get('email', ''))->first();

        if ( !$userRequested )
            return RESTAPIHelper::response('Email not found in the system.', false, 'invalid_email');

        $passwordGenerated = \Illuminate\Support\Str::random(12);

        $userRequested->password = bcrypt( $passwordGenerated );
        $userRequested->save();

        // Send reset password email
        $userRequested->notify( new \App\Notifications\Api\ResetPassword($userRequested, $passwordGenerated) );*/

        $response = \Password::broker()->sendResetLink($request->only('email'));

        switch ($response) {
            case \Password::INVALID_USER:
                return RESTAPIHelper::response('Email not found in the system.', false, 'invalid_email');
                break;
            case \Password::RESET_LINK_SENT:
                return RESTAPIHelper::response([], true, 'An email containing information on how to reset your password has been sent to your email.' );
                break;
            default:
                return RESTAPIHelper::response('Unexpected error occurred.', false);
                break;
        }
    }

    public function resendVerificationEmail(Request $request) {
        $user = User::users()->whereEmail($request->get('email', ''))->first();

        if ( !$user )
            return RESTAPIHelper::response('Email not found in the system.', false, 'invalid_email');

        if ( $user->email_verification == 1 )
            return RESTAPIHelper::response('It seems you\'ve already verified your account. Please log-in with your credentials or reset your password.', false, 'invalid_action');

        event(new JWTUserRegistration($user, [
            'syncFirestore' => false,
        ]));

        return RESTAPIHelper::response([], true, 'A verification code has been resent to your email. Please also check Junk/Spam folder as well.' );
    }

    public function verifyAccountByEmailCode(Request $request)
    {
        $user = User::users()->whereEmailVerification($request->get('code'))->first();

        if ( !$user )
            return RESTAPIHelper::response('Invalid code entered. Please try again.', false);

        $user->email_verification = 1;

        // Driver need to get approval from admin first.
        // if ( $user->isNormalUser() ) {
            $user->active = 1;
        // }

        $user->save();

        event(new UserEmailAccountVerified($user));

        $response = $this->generateUserProfileResponse( $user, JWTAuth::fromUser($user) );

        return RESTAPIHelper::response($response, true, 'Account verified successfully.' );
    }

    public function viewMyProfile(Request $request)
    {
        $me = $this->getUserInstance();

        $result = $this->generateUserProfileResponse( $me );

        return RESTAPIHelper::response( $result );
    }

    public function updateMyProfile(UserUpdateRequest $request)
    {
        $me = $this->getUserInstance();

        // This will work with empty fields.
        $dataToUpdate = array_filter([
            'first_name'           =>  $request->get('first_name', false),
            'last_name'            =>  $request->get('last_name', false),
            'email'                =>  $request->get('email', false),
            'address'              =>  $request->get('address', false),
            'state'                =>  $request->get('state', false),
            'city'                 =>  $request->get('city', false),
            'profile_picture'      =>  $request->get('profile_picture', false),
            // 'gender'               =>  $request->get('gender', false),
            // 'school_name'          =>  $request->get('school_name', false),
            // 'student_organization' =>  $request->get('student_organization', false),
            // 'graduation_year'      =>  $request->get('graduation_year', false),
            // 'postal_code'          =>  $request->get('postal_code', false),
            // 'birth_date'           =>  $request->get('birth_date', false),
        ], function($a){return false !== $a;});

        if ( $request->has('phone') ) {
            try {
                $dataToUpdate['phone'] = phone($request->get('phone'), 'US')->formatE164();
            } catch (\Exception $e) {
                $dataToUpdate['phone'] = '';
            }
        }

        if ( $request->has('password') && $request->get('password', '') !== '' ) {

            // Validate old password first
            $oldPasswordValidation = Auth::validate([
                'email' => $me->email,
                'password' => $request->get('old_pwd'),
            ]);

            if ( !$oldPasswordValidation ) {
                return RESTAPIHelper::response('Old password is incorrect', false, 'auth_error');
            }

            $dataToUpdate['password'] = bcrypt( $request->get('password') );
        }

        if ( $request->hasFile('profile_picture') ) {

            if ( !in_array($request->file('profile_picture')->getClientOriginalExtension(), ['jpg','jpeg','png','bmp']) ) {
                return RESTAPIHelper::response('Invalid profile_picture given. Please use only image as your profile picture.', false, 'validation_error');
            }

            $imageName = $me->id . '-' . str_random(12) . '.' . $request->file('profile_picture')->getClientOriginalExtension();
            $path = public_path( config('constants.front.dir.profilePicPath') );
            $request->file('profile_picture')->move($path, $imageName);

            //if ( Image::open( $path . '/' . $imageName )->scaleResize(200, 200)->save( $path . '/' . $imageName ) ) {
                $dataToUpdate['profile_picture'] = $imageName;
            //}
        }

        // Here i'm receiving empty fields as null and DB constraints doesn't allow to set null.
        foreach ($dataToUpdate as $key => $value) {

            // Set null value for columns other than text|varchar
            if ( in_array($key, ['birth_date']) ) {
                $dataToUpdate[$key] = $value ?: null;
            } else {
                $dataToUpdate[$key] = strval($value);
            }
        }

        // User meta attributes
        $metaDataToUpdate = array_filter([
            'gender'                =>  $request->get('gender', false),
            'school_name'           =>  $request->get('school_name', false),
            'student_organization'  =>  $request->get('student_organization', false),
            'graduation_year'       =>  $request->get('graduation_year', false),
            'postal_code'           =>  $request->get('postal_code', false),
            'birth_date'            =>  $request->get('birth_date', false),

            // Driver user role
            'driving_license_no'    =>  $request->get('driving_license_no', false),
            'vehicle_id_number'     =>  $request->get('vehicle_id_number', false),
            'vehicle_type'          =>  $request->get('vehicle_type', false),
            'vehicle_make'          =>  $request->get('vehicle_make', false),
            'vehicle_model'         =>  $request->get('vehicle_model', false),
            'vehicle_year'          =>  $request->get('vehicle_year', false),
            'ssn'                   =>  $request->get('ssn', false),
        ], function($a){return false !== $a;});

        /*if ( array_key_exists('full_name', $dataToUpdate) ) {
            list($dataToUpdate['first_name'], $dataToUpdate['last_name']) = str_split_name($dataToUpdate['full_name']);
        }*/

        // Save driver register time on meta
        // Unfortunately, app dev calling update profile service upon registration.
        // That's sad isn't it?

        if ( $me->isDriver() && !$me->getMetaObject('driver_upgrade_time')->exists() ) {
            $metaDataToUpdate['driver_upgrade_time'] = \Carbon\Carbon::now();
        }

        if ( empty($dataToUpdate) && empty($metaDataToUpdate) )
            return RESTAPIHelper::response('Nothing to update', false);

        if ( !empty($metaDataToUpdate) ) {
            $me->setMeta( $metaDataToUpdate );
        }

        $me->update( $dataToUpdate );

        // Add user device
        if ( !empty($request->get('device_token', '')) ) {
            $me->updateDevice( $this->extractToken(), $request->get('device_token', ''), $request->get('device_type', null) );
        }

        // Trigger some action upon changing password
        if ( array_key_exists('password', $dataToUpdate) ) {
            event(new UserPasswordChanged($me, $dataToUpdate));
        }

        // Fire user update event
        event(new JWTUserUpdate($me));

        $result = $this->generateUserProfileResponse( $me );

        return RESTAPIHelper::response( $result, true, 'Profile updated successfully.' );

    }

    public function upgradeToDriverRequest(Request $request)
    {
        $me = $this->getUserInstance();

        if ( $me->isDriver() ) {
            return RESTAPIHelper::response('You cannot perform this action.', false);
        }

        // User meta attributes
        $metaDataToUpdate = array_filter([
            'driving_license_no'    =>  $request->get('driving_license_no', false),
            'vehicle_id_number'     =>  $request->get('vehicle_id_number', false),
            'vehicle_type'          =>  $request->get('vehicle_type', false),
            'vehicle_make'          =>  $request->get('vehicle_make', false),
            'vehicle_model'         =>  $request->get('vehicle_model', false),
            'vehicle_year'          =>  $request->get('vehicle_year', false),
        ], function($a){return false !== $a;});

        $metaDataToUpdate['was_passenger'] = true;
        $metaDataToUpdate['driver_upgrade_time'] = \Carbon\Carbon::now();

        if ( !empty($metaDataToUpdate) ) {
            $me->setMeta( $metaDataToUpdate, 'driver' );
        }

        $me->upgradeToDriver();

        return RESTAPIHelper::response([], true, 'You have now been successfully registered as a Driver.');
    }

    public function viewProfile(Request $request, $userId)
    {
        $me   = $this->getUserInstance();
        $user = User::users()->find($userId);

        if ( !$user || $user->isAdmin() ) {
            return RESTAPIHelper::response('Something went wrong here.', false);
        }

        $payload                      = $this->generateUserProfileResponse( $user );
        $payload['is_self']           = $me->isSelf($user);
        $payload['mutual_followers']  = $me->getMutualFollowers($user)->count();

        return RESTAPIHelper::response( $payload );
    }

    public function getListOfMutualFollowers(Request $request, $userId)
    {
        $me   = $this->getUserInstance();
        $user = User::users()->find($userId);

        if ( !$user || $user->isAdmin() ) {
            return RESTAPIHelper::response('Something went wrong here.', false);
        }

        $mutualFollowers = $me->getMutualFollowers($user);

        $payload = [];
        foreach ($mutualFollowers as $follower) {
            $payload[] = array_merge(User::extractUserBasicDetails($follower), [
            ]);
        }

        return RESTAPIHelper::response( $payload );
    }

    public function syncFriends(Request $request)
    {
        $me = $this->getUserInstance();

        // Collect data
        $facebookIds   = explode(constants('api.separator'), ($request->get('facebook_ids', '')));
        $mobileNumbers = explode(constants('api.separator'), ($request->get('numbers', '')));

        // Remove previous friends
        $me->following()->detach();

        // Format mobile numbers
        $mobileNumbers = array_map(function($value) {
            try {
                return phone($value, 'US')->formatE164();
            } catch (\Exception $e) {}
        }, $mobileNumbers);

        // Get matched facebook ids
        $facebookIds = UserFacebook::whereIn('facebook_uid', $facebookIds)->pluck('user_id');

        $matchedFriends = User::users()->active()->excludeSelf()->where(function($query) use($facebookIds, $mobileNumbers) {
            $query
                ->whereIn('phone', $mobileNumbers)
                ->orWhereIn( (new User)->getKeyName(), $facebookIds )
                ;
        })->get();

        foreach ($matchedFriends as $friend) {
            $me->followUser($friend);
        }

        $me->setMeta('sync_friends', $matchedFriends->count(), 'application');
        $me->save();

        return RESTAPIHelper::response([], true, $matchedFriends->count() . ' members has been added to your friend list.');

    }

    public function addFavorite(Request $request, User $user)
    {
        $me = $this->getUserInstance();

        if ( $me->isSelf($user) ) {
            return RESTAPIHelper::response('You are not allowed to perform this action.', false);
        }

        if ( $me->followUser($user) ) {
            return RESTAPIHelper::response([], true, 'You have started following.');
        }

        return RESTAPIHelper::response('Error while following.', false);
    }

    public function removeFavorite(Request $request, User $user)
    {
        $me = $this->getUserInstance();

        if ( $me->isSelf($user) ) {
            return RESTAPIHelper::response('You are not allowed to perform this action.', false);
        }

        if ( $me->unfollowUser($user) ) {
            return RESTAPIHelper::response([], true, 'Removed from following list.');
        }

        return RESTAPIHelper::response('Error while unfollowing.', false);
    }

    public function doBlock(Request $request, User $user)
    {
        $me = $this->getUserInstance();

        if ( $me->isSelf($user) ) {
            return RESTAPIHelper::response('You are not allowed to perform this action.', false);
        }

        if ( $me->doBlock($user) ) {
            return RESTAPIHelper::response([], true, 'User blocked successfully.');
        }

        return RESTAPIHelper::response('Error while blocking user.', false);
    }

    public function doUnblock(Request $request, User $user)
    {
        $me = $this->getUserInstance();

        if ( $me->isSelf($user) ) {
            return RESTAPIHelper::response('You are not allowed to perform this action.', false);
        }

        if ( $me->doUnblock($user) ) {
            return RESTAPIHelper::response([], true, 'User unblocked successfully.');
        }

        return RESTAPIHelper::response('Error while unblocking user.', false);
    }

    public function listMyFollowings(Request $request)
    {
        if ( intval($request->get('user_id', 0)) ) {
            $user = User::users()->find($request->get('user_id'));
        } else {
            $user = $this->getUserInstance();
        }

        if ( !$user ) {
            return RESTAPIHelper::response('Something went wrong here.', false);
        }

        $perPage    = $request->get('limit', constants('api.config.defaultPaginationLimit') );
        $pagination = $user->following()->orderBy('first_name', 'ASC')->paginate( $perPage );

        $result = [];
        foreach ($pagination->items() as $record) {
            $additional = [];

            if ( !$request->get('user_id') ) {
                $additional = [
                    'email'   => $record->email,
                    'phone'   => $record->phone,
                    'role_id' => $record->user_role_key,
                ];

                if ( $record->isDriver() ) {
                    $additional = array_merge($additional, [
                        'driving_license_no' => $record->getMetaDefault('driving_license_no', ''),
                        'vehicle_type'       => $record->getMetaDefault('vehicle_type', ''),
                    ]);
                }
            }

            $result[] = [
                'user_id'         => $record->id,
                'first_name'      => $record->first_name,
                'last_name'       => $record->last_name,
                'profile_picture' => $record->profile_picture_auto,
                'rating'          => $record->getMetaDefault('rating', 0.0),
                'is_blocked'      => $user->isBlocked($record),
                'is_self'         => $user->isSelf($record),
            ] + $additional;
        }

        return RESTAPIHelper::setPagination( $pagination )->response( $result );
    }

    public function listMyNotifications(Request $request)
    {
        $rules = [
            'user_type' => 'required|in:passenger,driver',
        ];

        if ( ($validators = appValidations($request, $rules)) !== null ) {
            return $validators;
        }

        $me = $this->getUserInstance();

        $perPage         = $request->get('limit', constants('api.config.defaultPaginationLimit'));
        $paginationQuery = $me->notifications()->where('user_type', '=', $request->get('user_type'));

        $pagination      = clone $paginationQuery;
        $pagination      = $pagination->orderBy('id', 'DESC')->paginate($perPage);

        $result = [];
        foreach ($pagination->items() as $record) {
            $result[] = [
                'id'                => $record->id,
                'receiver_type'     => $record->user_type,
                // 'notification'      => $record->notification,
                // 'notification_type' => $record->notification_type,
                // 'notification_data' => $record->notification_data,
                'payload'           => $record->payload,
                'actionable'        => data_get($record->notification_data, 'actionable', true),
                'datetime'          => $record->created_at->format(constants('api.global.formats.datetime')),
                'unix_timestamp'    => $record->created_at->format('U'),
                'rfc_2822'          => $record->created_at->format('r'),
                'iso_8601'          => $record->created_at->format('c'),
            ];
        }

        $paginationQuery->update(['is_read' => 1]);

        event(new NotificationsListed($me));

        return RESTAPIHelper::setPagination($pagination)->response($result);
    }

    public function actionCompletedOnNotification(Request $request, $notification_id = null)
    {
        $me = $this->getUserInstance();

        $notification = $me->notifications()->find($notification_id);

        if (!$notification) {
            return RESTAPIHelper::response('Notification not found.', false);
        }

        $notification->notification_data = array_merge($notification->notification_data, [
            'actionable' => false,
        ]);
        $notification->save();


        return RESTAPIHelper::emptyResponse();
    }

    public function firebaseNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'   => 'required',
            'user_type' => 'required|in:passenger,driver',
            'payload'   => 'required',
        ]);

        if ($validator->fails()) {
            return RESTAPIHelper::response(array_flatten($validator->messages()->toArray()), false, 'validation_error');
        }

        $userId   = $request->get('user_id');
        $userType = $request->get('user_type');
        $payload  = json_decode($request->get('payload'), true);

        $user = User::users()->find($userId);

        if ( !$user ) {
            return RESTAPIHelper::response('User not found in database', false);
        }

        if ( !is_array($payload) ) {
            return RESTAPIHelper::response('Invalid payload detected', false);
        }

        $customPayload = data_get($payload, 'data') + [
            'click_action' => data_get($payload, 'notification.click_action'),
        ];

        $user->createNotification($userType, data_get($payload, 'notification.title'), [
            'message' => data_get($payload, 'notification.body'),
            'type' => data_get($payload, 'notification.click_action'),
        ])->customPayload($customPayload)->build();

        return RESTAPIHelper::response(new \stdCLass, true, 'Notification Added');
    }

    public function testMethods(Request $request)
    {
        $request->parseBody();

        return RESTAPIHelper::emptyResponse();
    }
}
