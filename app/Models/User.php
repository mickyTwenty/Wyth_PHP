<?php

namespace App\Models;

use App\Classes\FirebaseHandler;
use App\Events\Api\JWTUserUpdate;
use App\Events\BlockEvent;
use App\Events\MessagePayment;
use App\Events\MessageSent;
use App\Events\NewFollowingEvent;
use App\Events\NewUnfollowingEvent;
use App\Events\UnblockEvent;
use App\Events\UserActivated;
use App\Events\UserDeactivated;
use App\Events\UserDeleted;
use App\Events\UserFacebookAccountSynced;
use App\Http\Traits\JWTUserTrait;
use App\Http\Traits\Metable\Metable;
use App\Models\Message;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\UserCharge;
use App\Models\UserFacebook;
use App\Notifications\Backend\ResetPassword as BackendResetPasswordNotification;
use App\Notifications\Frontend\ResetPassword as FrontendResetPasswordNotification;
use Exception;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Sofa\Eloquence\Eloquence;

class User extends Authenticatable
{
    use Notifiable, Eloquence, Metable
    {
        Metable::toArray insteadof Eloquence;
        Metable::getAttribute insteadof Eloquence;
        Metable::__unset insteadof Eloquence;
        Metable::__isset insteadof Eloquence;
    }

    const ROLE_ADMIN          = 1;
    const ROLE_NORMAL_USER    = 2;
    const ROLE_DRIVER         = 3;

    const ADMIN_USER_ID       = 1;
    const CLIENT_USER_ID      = 2;

    const BACKEND_ALLOW_ROLES = [
        self::ROLE_ADMIN,
    ];

    /**
     * The attributes which are searchable for User class | Eloquence
     *
     * @var array
     */
    protected $searchableColumns = ['first_name', 'last_name', 'email'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'password', 'role_id', 'first_name', 'last_name', 'phone', 'address', 'city', 'state', 'country', 'profile_picture', 'industry_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $events = [
        'deleted' => UserDeleted::class,
    ];

    /**
     * Meta table for this model.
     *
     * @var string
     */
    protected $metaTable = 'user_meta';

    /**
     * Meta data model relating to this model.
     *
     * @var string
     */
    protected $metaModel = 'App\Models\UserMeta';

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        if ( $this->isBackendUser() ) {
            $this->notify(new BackendResetPasswordNotification($token));
        } else {
            $this->notify(new FrontendResetPasswordNotification($token));
        }
    }

    public static function extractUserId($user)
    {
        if ( $user instanceof User ) {
            return $user->id;
        }

        return $user;
    }

    /*
     * @User related methods
     */
    public static function getRoleIdByUserType($value)
    {
        switch ($value) {
            case 'normal':
                return self::ROLE_NORMAL_USER;
                break;
            case 'driver':
                return self::ROLE_DRIVER;
                break;
            default:
                throw new \App\Exceptions\InvalidUserTypeException("Invalid string type detected");
                break;
        }
    }

    public function validateUserActiveCriteria()
    {
        if ( $this->attributes['email_verification'] != '1' ) {
            throw new \App\Exceptions\UserNotAllowedToLogin('Please verify your email first.', 'action_verify_email');
        } else if ( $this->attributes['sms_verification'] != '1' ) {
            throw new \App\Exceptions\UserNotAllowedToLogin('Please verify your sms first.', 'action_verify_number');
        } else if ( $this->attributes['active'] != '1' ) {
            throw new \App\Exceptions\UserNotAllowedToLogin('Your account is not in active state.', 'inactive_account');
        } else if ( $this->isAdmin() ) {
            throw new \App\Exceptions\UserNotAllowedToLogin('Invalid credentials, please try-again.', 'invalid_credentials');
        }

        return true;
    }

    public function isBackendUser()
    {
        return (defined('self::BACKEND_ALLOW_ROLES') && in_array($this->attributes['role_id'], self::BACKEND_ALLOW_ROLES));
    }

    public function isVerified()
    {
        return (bool) (array_key_exists('email_verification', $this->attributes) && intval($this->attributes['email_verification']) === 1 && array_key_exists('sms_verification', $this->attributes) && intval($this->attributes['sms_verification']) === 1);
    }

    public function isAdmin()
    {
        return (bool) ($this->attributes['role_id'] == self::ROLE_ADMIN);
    }

    public function isNormalUser()
    {
        return (bool) ($this->attributes['role_id'] == self::ROLE_NORMAL_USER);
    }

    public function isDriver()
    {
        return (bool) ($this->attributes['role_id'] == self::ROLE_DRIVER);
    }

    public function isFollowing($user)
    {
        return (bool) $this->following()->where('following_id', '=', self::extractUserId($user))->count();
    }

    public function isFriend($user)
    {
        return (bool) ($this->isFollowing($user) && $user->isFollowing($this));
    }

    public function isBlocked($user)
    {
        return (bool) $this->blocked()->where('blockee_id', '=', self::extractUserId($user))->count();
    }

    public function isSelf($user)
    {
        return (bool) ($this->attributes['id'] == self::extractUserId($user));
    }

    public function isEmailVerified()
    {
        return (bool) ($this->attributes['email_verification'] === 1);
    }

    public static function generateUniqueVerificationCode($length=6)
    {
        $code  = mt_rand( intval(str_repeat(1, $length)), intval(str_repeat(9, $length)) );
        $exist = self::whereEmailVerification($code)->count();

        return $exist ? self::generateUniqueVerificationCode() : $code;
    }

    public function activate()
    {
        $this->active = 1;
        $this->save();

        event(new UserActivated($this));
    }

    public function deactivate()
    {
        $this->active = 0;
        $this->save();

        event(new UserDeactivated($this));
    }

    public function followUser($user)
    {
        try {
            $this->following()->attach($user);

            event(new NewFollowingEvent($this, $user));

            return true;

        } catch (\Illuminate\Database\QueryException $e) {
            // It is "Integrity constraint violation"
            if ( $e->getCode() == "23000" ) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function unfollowUser($user)
    {
        try {
            $this->following()->detach($user);

            event(new NewUnfollowingEvent($this, $user));

            return true;

        } catch (\Illuminate\Database\QueryException $e) {
            // It is "Integrity constraint violation"
            if ( $e->getCode() == "23000" ) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function doBlock($user)
    {
        try {
            $this->blocked()->attach($user);

            event(new BlockEvent($this, $user));

            return true;

        } catch (\Illuminate\Database\QueryException $e) {
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function doUnblock($user)
    {
        try {
            $this->blocked()->detach($user);

            event(new UnblockEvent($this, $user));

            return true;

        } catch (\Illuminate\Database\QueryException $e) {
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function addDevice($deviceToken, $deviceType, $authToken)
    {
        UserDevice::whereDeviceToken($deviceToken)->delete();

        return $this->devices()->create([
            'device_token' => $deviceToken,
            'device_type'  => $deviceType,
            'auth_token'   => $authToken,
        ]);
    }

    public function updateDevice($authToken, $deviceToken, $deviceType)
    {
        $record = $this->devices()->whereAuthToken($authToken)->limit(1)->first();

        if ( $record ) {
            $record->device_token = $deviceToken;
            $record->device_type = $deviceType;
            $record->save();
        }
    }

    public function removeDevice($authToken)
    {
        $record = $this->devices()->whereAuthToken($authToken)->limit(1)->first();

        if ( $record ) {
            $record->delete();
        }
    }

    public function addFacebook($facebookUid, $accessToken)
    {
        try {
            $this->facebook()->delete();
            UserFacebook::whereFacebookUid($facebookUid)->delete();
        } catch (\Illuminate\Database\QueryException $e) {}

        $facebook = $this->facebook()->create([
            'facebook_uid' => $facebookUid,
            'access_token'  => $accessToken,
        ]);

        event(new UserFacebookAccountSynced($this, $facebook));

        return $facebook;
    }

    public function hasFacebook($facebookUid)
    {
        return (bool) ($this->facebook()->whereFacebookUid($facebookUid)->limit(1)->first() ? true : false);
    }

    public function hasBankAccount()
    {
        return (bool) ($this->bankAccount ? true : false);
    }

    public static function getUserByFacebookId($facebookUid)
    {
        $record = UserFacebook::whereFacebookUid($facebookUid)->first();

        return $record ? $record->user : null;
    }

    public function addActivity($key, $value)
    {
        return $this->activities()->create([
            'event_key'  => $key,
            'event_data' => is_scalar($value) ? $value : json_encode($value),
            'is_encoded' => is_scalar($value) ? 0 : 1,
        ]);
    }

    public function canJoinRide()
    {
        // Does user has active card?
        if (!$this->creditCard) {
            throw new \App\Exceptions\UserCanNotJoinRide('User does not have active credit card.', 'invalid_credit_card');
        }

        return true;
    }

    public function createNotification($userType, $text, array $notification_data=[])
    {
        $notification = (new Notification)->createNotification([
            'notification'      => $text,
            'notification_type' => array_key_exists('type', $notification_data) ? $notification_data['type'] : '',
            'notification_data' => $notification_data, // InApp payload
            'is_read'           => 1,
        ])->setOwner($this, $userType);

        return $notification;
    }

    public function saveSearch($payload)
    {
        return $this->searches()->create([
            'origin_latitude'  => $payload->get('origin_latitude'),
            'origin_longitude' => $payload->get('origin_longitude'),
            'destination_latitude' => $payload->get('destination_latitude'),
            'destination_longitude' => $payload->get('destination_longitude'),
            'extra' => json_encode( RideSearch::extractSearchDetails($payload) ),
        ]);
    }

    public function getActivity($key)
    {
        $activity = $this->activities()->whereEventKey($key)->first();

        return $activity ?: new UserActivity;
    }

    public function upgradeToDriver()
    {
        $this->role_id = self::ROLE_DRIVER;
        $this->save();
    }

    public function downgradeToNormalUser()
    {
        $this->role_id = self::ROLE_NORMAL_USER;
        $this->save();
    }

    public function device()
    {
        return $this->devices()->orderBy('id', 'DESC')->first();
    }

    public function avgRating()
    {
        return $this->ratings->avg('rating') ?: 0;
    }

    public static function extractUserBasicDetails($user)
    {
        if ( !$user instanceof self ) {
            throw new Exception('Argument 1 passed to App\Models\User::extractUserBasicDetails() must be an instance of App\Models\User, null given');
        }

        return [
            'user_id'               => $user->id,
            'first_name'            => $user->first_name,
            'last_name'             => $user->last_name,
            'profile_picture'       => $user->profile_picture_auto,
            'rating'                => $user->getMetaDefault('rating', 0.0),
            'trips_canceled'        => $user->getMetaMultiDefault('profile', 'canceled_trips', 0),
            'trips_canceled_driver' => $user->getMetaMultiDefault('driver', 'canceled_trips', 0),
        ];
    }

    function getUserTypeAttribute()
    {
        switch ($this->attributes['role_id']) {
            case self::ROLE_NORMAL_USER:
                return 'passenger';
                break;
            case self::ROLE_DRIVER:
                return 'driver';
                break;
            case self::ROLE_ADMIN:
                return 'admin';
                break;
            default:
                throw new \App\Exceptions\InvalidUserTypeException("Invalid user role detected", 1);
                break;
        }
    }

    public static function listPassengers()
    {
        return Cache::remember('listPassengers', 15, function () {
            return self::passengers()
                ->select(\DB::raw("CONCAT(first_name,' ',last_name) AS name"), 'id')
                ->pluck('name', 'id');
        });
    }

    public static function listDrivers()
    {
        return Cache::remember('listDrivers', 15, function () {
            return self::drivers()
                ->select(\DB::raw("CONCAT(first_name,' ',last_name) AS name"), 'id')
                ->pluck('name', 'id');
        });
    }

    public static function listUsers()
    {
        return Cache::remember('listUsers', 15, function () {
            return self::users()
                ->select(\DB::raw("CONCAT(first_name,' ',last_name) AS name"), 'id')
                ->pluck('name', 'id');
        });
    }

    public static function cachedPassengers($id = null)
    {
        return Cache::remember('cachedPassengers' . $id, 15, function () use ($id) {
            if ($id) {
                return self::passengers()->whereId($id)->first();
            }

            return self::passengers()->get();
        });
    }

    public static function cachedDrivers($id = null)
    {
        return Cache::remember('cachedDrivers' . $id, 15, function () use ($id) {
            if ($id) {
                return self::drivers()->whereId($id)->first();
            }

            return self::drivers()->get();
        });
    }

    public static function cachedUsers($id = null)
    {
        return Cache::remember('cachedUsers' . $id, 15, function () use ($id) {
            if ($id) {
                return self::users()->whereId($id)->first();
            }

            return self::users()->get();
        });
    }

    /*
     * @2018-10-18 Start
     * */
    public function hasProcessRequest()
    {
        return $this->ridesubscriber()->exists() ? $this->ridesubscriber()->pending()->count() : false;
    }

    public function checkRequestExist($latitude, $longitude)
    {
        $distanceFormula = distanceFormula($latitude, $longitude);

        return $this->ridesubscriber()
            ->pending()
            ->whereRaw("$distanceFormula <= ?", [10])
            ->count();
    }
    /*
     * @2018-10-18 End
     * */

    /*
     * @Attributes
     */
    public function getPrefixUidAttribute()
    {
        return 'u' . $this->attributes['id'];
    }

    public function getUserRoleKeyAttribute()
    {
        switch ($this->attributes['role_id']) {
            case self::ROLE_NORMAL_USER:
                return 'normal';
                break;
            case self::ROLE_DRIVER:
                return 'driver';
                break;
            case self::ROLE_ADMIN:
                return 'admin';
                break;
            default:
                throw new \App\Exceptions\InvalidUserTypeException("Invalid user role detected", 1);
                break;
        }
    }

    public function getUserRoleKeyWebAttribute()
    {
        switch ($this->attributes['role_id']) {
            case self::ROLE_NORMAL_USER:
                return 'passenger';
                break;
            case self::ROLE_DRIVER:
                return 'driver';
                break;
            case self::ROLE_ADMIN:
                return 'admin';
                break;
            default:
                throw new \App\Exceptions\InvalidUserTypeException("Invalid user role detected", 1);
                break;
        }
    }

    public function getFullNameAttribute()
    {
        return trim($this->attributes['first_name'] . ' ' . $this->attributes['last_name']);
    }

    public function getProfilePicturePathAttribute()
    {
        return config('constants.front.dir.profilePicPath') . ($this->attributes['profile_picture'] ?: config('constants.front.default.profilePic'));
    }

    public function getProfilePictureAutoAttribute()
    {
        return asset( config('constants.front.dir.profilePicPath') . ($this->attributes['profile_picture'] ?: config('constants.front.default.profilePic')) );
    }

    public function getStatusTextFormattedAttribute()
    {
        return $this->attributes['active'] == '1' ?
            '<span class="label label-success">Active</span>' :
            '<span class="label label-danger">Inactive</span>';
    }

    /**
     * All Mutators will goes here
     */
    public function setUsernameAttribute($value)
    {
        $this->attributes['username'] = strtolower($value);
    }

    /**
     * Accessor to fetch title attribute via industry relation property
     *
     * @return string
     */
    public function getIndustryTitleAttribute()
    {
        return $this->attributes['industry_id'] > 0 ? $this->industry->title : '';
    }

    public function getStateTitleAttribute()
    {
        try {
            return intval($this->attributes['state']) > 0 ? $this->userstate->name : '';
        } catch (Exception $e) {
            return '';
        }
    }

    public function getCityTitleAttribute()
    {
        try {
            return intval($this->attributes['city']) > 0 ? $this->usercity->name : '';
        } catch (Exception $e) {
            return '';
        }
    }

    public function getHasSyncFriendsAttribute()
    {
        try {
            return $this->activities()->whereEventKey('sync_friends')->first() ? true : false;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getBitwiseGenderValueByUserIds(array $userIds)
    {
        if ( count($userIds) == 0 )
            return 0;

        $result = \DB::select(\DB::raw("
            SELECT
              user_id,
              value,
              CASE
                value
                WHEN 'Male'
                THEN 1
                WHEN 'Female'
                THEN 2
              END AS gender
            FROM
              user_meta
            WHERE `key` = 'gender'
            AND user_id IN (".implode(',', $userIds).")
            GROUP BY gender
        "));

        $cummulativeValue = 0;
        foreach ($result as $value) {
            $cummulativeValue = $cummulativeValue | $value->gender;
        }

        return $cummulativeValue;
    }

    /*
     * @Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('active', '=', 1);
    }

    public function scopeUsers($query)
    {
        return $query->whereIn('role_id', [self::ROLE_DRIVER, self::ROLE_NORMAL_USER]);
    }

    public function scopeDrivers($query)
    {
        return $query->where('role_id', self::ROLE_DRIVER);
    }

    public function scopePassengers($query)
    {
        return $query->where('role_id', self::ROLE_NORMAL_USER);
    }

    public function scopeExcludeSelf($query)
    {
        return $query->where( $this->getKeyName(), '<>', self::extractUserId(JWTUserTrait::getUserInstance()) );
    }

    /*
     * @Relationships
     */
    public function blocked()
    {
        return $this->belongsToMany('App\Models\User', 'blocks', 'blocker_id', 'blockee_id');
    }

    public function following()
    {
        return $this->belongsToMany('App\Models\User', 'favorites', 'user_id', 'following_id');
    }

    public function followers()
    {
        return $this->belongsToMany('App\Models\User', 'favorites', 'following_id', 'user_id');
    }

    public function creditCard()
    {
        return $this->hasOne(PassengerCard::class, 'user_id')->active()->default()->latest();
    }

    public function creditCards()
    {
        return $this->hasMany(PassengerCard::class, 'user_id');
    }

    public function bankAccount()
    {
        return $this->hasOne(DriverBankDetail::class, 'user_id')->active()->latest();
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    public function earnings()
    {
        return $this->hasMany(TripEarning::class, 'user_id');
    }

    public function friends()
    {
        return $this->whereHas('followers', function($query) {
            $query->where('user_id', '=', $this->id);
        })->whereHas('following', function($query) {
            $query->where('following_id', '=', $this->id);
        });
    }

    /**
     * List of mutual followers
     *
     * @param  User $user
     * @return object
     */
    public function getMutualFollowers($user)
    {
        if ( !$user instanceof self ) {
            $user = self::find($user);
        }

        $mutual = $user->following()->whereIn('following_id', $this->following->pluck('id')->diff([$user->id]))->get();

        return $mutual;
    }

    public function activities()
    {
        return $this->hasMany('App\Models\UserActivity');
    }

    public function devices()
    {
        return $this->hasMany('App\Models\UserDevice');
    }

    public function purchases()
    {
        return $this->hasMany('App\Models\UserPurchase')->orderBy('id', 'DESC');
    }

    public function facebook()
    {
        return $this->hasOne('App\Models\UserFacebook');
    }

    public function industry()
    {
        return $this->belongsTo('App\Models\Industry');
    }

    public function usercity()
    {
        return $this->belongsTo('App\Models\City', 'city', 'id');
    }

    public function userstate()
    {
        return $this->belongsTo('App\Models\State', 'state', 'id');
    }

    public function trips()
    {
        return $this->hasMany('App\Models\Trip');
    }

    public function tripshared()
    {
        return $this->hasMany(TripRideShared::class);
    }

    public function searches()
    {
        return $this->hasMany('App\Models\RideSearch');
    }

    public function ridesubscriber()
    {
        return $this->hasMany(RideSubscriber::class);
    }

    public function ratings()
    {
        return $this->hasMany(TripRating::class, 'ratee_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function metas()
    {
        return $this->hasMany(UserMeta::class, 'user_id');
    }
}
