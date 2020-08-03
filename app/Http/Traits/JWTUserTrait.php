<?php
namespace App\Http\Traits;

use Illuminate\Support\Facades\Request;
// use JWTAuth;

trait JWTUserTrait {

    protected static $userInstance;

    /**
     * Extract token value from request
     *
     * @return string|Request
     */
    public static function extractToken($request=false) {
        if ( $request && $request instanceof Request ) {
            $token = $request->only('_token');
        } else if ( is_string($request) ) {
            $token = $request;
        } else {
            $token = Request::get('_token');
        }

        return $token;
    }

    /**
     * Return User instance or false if not exist in DB
     *
     * @return string|Request
     */
    public static function getUserInstance($request=false) {

        if ( !self::$userInstance ) {
            $token = self::extractToken($request);

            self::$userInstance = \JWTAuth::toUser( $token );
        }

        return self::$userInstance;
    }

}
