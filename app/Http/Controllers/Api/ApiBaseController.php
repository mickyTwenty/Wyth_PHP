<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\JWTUserTrait;
use Illuminate\Support\Facades\Request;

class ApiBaseController extends Controller {

	/**
	 * Extract token value from request
	 *
	 * @return string
	 */
	protected function extractToken($request=false) {
		return JWTUserTrait::extractToken($request);
	}

	/**
	 * Return User instance or false if not exist in DB
	 *
	 * @return mixed
	 */
	protected function getUserInstance($request=false) {
		return JWTUserTrait::getUserInstance($request);
	}

    protected function generateUserProfileResponse($user, $token=null)
    {
        $result = [
            'user_id'                 =>  $user->id,
            'user_type'               =>  $user->userRoleKey,
            'first_name'              =>  $user->first_name,
            'last_name'               =>  $user->last_name,
            'email'                   =>  $user->email,
            'trips_canceled'          =>  $user->getMetaMultiDefault('profile', 'canceled_trips', 0), // Don't change its placement, multi needs to be load first.
            'gender'                  =>  $user->getMetaDefault('gender', ''),
            'school_name'             =>  $user->getMetaDefault('school_name', ''),
            'student_organization'    =>  $user->getMetaDefault('student_organization', ''),
            'graduation_year'         =>  $user->getMetaDefault('graduation_year', ''),
            'city'                    =>  $user->city,
            'city_text'               =>  $user->city_title,
            'state'                   =>  $user->state,
            'state_text'              =>  $user->state_title,
            'phone'                   =>  $user->phone,
            'postal_code'             =>  strval($user->getMetaDefault('postal_code', '')),
            'birth_date'              =>  $user->getMetaDefault('birth_date', ''),
            'profile_picture'         =>  $user->profile_picture_auto,
            'follower_count'          =>  $user->following()->count(),
            'rating'                  =>  $user->getMetaDefault('rating', '0.00'),
            'has_pending_ratings'     =>  $user->getMetaDefault('pending_rating', false),
            'has_sync_friends'        =>  $user->getMetaObject('sync_friends')->exists(),
            'has_facebook_integrated' =>  $user->getMetaObject('has_facebook_integrated')->exists(),
        ];

        if ( $user->isDriver() ) {
            $driverData = [
                'trips_canceled_driver' => $user->getMetaMultiDefault('driver', 'canceled_trips', 0),
                'driving_license_no'    => $user->getMetaDefault('driving_license_no', ''),
                'vehicle_id_number'     => $user->getMetaDefault('vehicle_id_number', ''),
                'vehicle_type'          => $user->getMetaDefault('vehicle_type', ''),
                'vehicle_make'          => $user->getMetaDefault('vehicle_make', ''),
                'vehicle_model'         => $user->getMetaDefault('vehicle_model', ''),
                'vehicle_year'          => $user->getMetaDefault('vehicle_year', ''),
                'ssn'                   => $user->getMetaDefault('ssn', ''),
            ];

            $result = array_merge($result, $driverData);
        }

        if ( $token ) {
            $result['_token'] = $token;
        } else {
            $result['_token'] = $this->extractToken();
        }

        return $result;
    }

}
