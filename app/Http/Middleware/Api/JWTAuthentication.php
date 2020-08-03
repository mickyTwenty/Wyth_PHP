<?php

namespace App\Http\Middleware\Api;

use Closure;
use Exception;
use JWTAuth;
use App\Helpers\RESTAPIHelper;
use App\Http\Traits\JWTUserTrait;

class JWTAuthentication {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $user = JWTUserTrait::getUserInstance($request->input('_token'));

            if ( !$user ) {
                return RESTAPIHelper::response( 'Invalid token.', false, 'invalid_token' );
            }

        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return RESTAPIHelper::response( 'Invalid token.', false, 'invalid_token' );
            } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                return RESTAPIHelper::response( 'Your token has been expired, please login again.', false, 'invalid_token' );
            } else {
                if ( null === $request->get('_token') ) {
                    return RESTAPIHelper::response( '_token parameter not found.', false, 'invalid_token' );
                } else {
                    return RESTAPIHelper::response( 'Something went wrong.', false );
                }
            }
        }

        return $next($request);
    }
}
