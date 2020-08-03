<?php

namespace App\Http\Middleware\Api;

use App\Helpers\RESTAPIHelper;
use Illuminate\Routing\Middleware\ThrottleRequests;

class ThrottleJWTRequests extends ThrottleRequests
{
    /**
     * Create a 'too many attempts' response.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return \Illuminate\Http\Response
     */
    protected function buildResponse($key, $maxAttempts)
    {
        $response = RESTAPIHelper::response( 'Too Many Attempts', false, 'jwt_too_many_attempts' );

        $retryAfter = $this->limiter->availableIn($key);

        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts, $retryAfter),
            $retryAfter
        );
    }
}
