<?php

namespace App\Http\Middleware\Backend;

use App\Models\User;
use Auth;
use Closure;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if ( Auth::guard($guard)->check() ) {
            if ( Auth::guard($guard)->user()->isBackendUser() ) {
                return $next($request);
            } else {
                // Unauthorized Access
                abort(404);
            }
        } else {
            return redirect()->guest('backend/login');
        }
    }
}
