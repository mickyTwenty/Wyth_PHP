<?php

namespace App\Http\Middleware\Api;

use App\Helpers\RESTAPIHelper;
use Cache;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Routing\Middleware\ThrottleRequests;
use JWTAuth;

class JWTThrottle extends ThrottleRequests
{

    /**
     * The rate limiter instance.
     *
     * @var \Illuminate\Cache\RateLimiter
     */
    public $limiter;

    /**
     * Holder for decay minutes
     *
     * @var integer
     */
    public $decayMinutes;

    /**
     * Holder for particular key
     *
     * @var integer
     */
    public $key;

    /**
     * Create a new request throttler.
     *
     * @param  \Illuminate\Cache\RateLimiter  $limiter
     * @return void
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int  $maxAttempts
     * @param  int  $decayMinutes
     * @return mixed
     */
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $key = null, $autoHit = true)
    {
        $this->decayMinutes = $decayMinutes;
        $this->key          = $key;

        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, ($maxAttempts-1), $decayMinutes)) {
            return $this->buildResponse($key, $maxAttempts);
        }

        if ( (bool) $autoHit ) {
            $this->limiter->hit($key, $decayMinutes);
        }

        return $next($request);
    }

    /**
     * Increase attempt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function incrementAttempts($request)
    {
        $key = $this->resolveRequestSignature($request);
        $this->limiter->hit($key, $this->decayMinutes);
    }

    /**
     * Resolve request signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function resolveRequestSignature($request)
    {
        if ( is_null($this->key) ) {
            $user = JWTAuth::toUser($request->input('_token'));
            return 'userId-' . $user->id;
        }

        return sprintf('api:%s:%s', $this->key, $request->getClientIp());

    }

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
