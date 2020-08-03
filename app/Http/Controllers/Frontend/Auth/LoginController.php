<?php

namespace App\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Validator;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function emailVerification(Request $request)
    {
        $validator = Validator::make($request->only(['code']), [
            'code'        => 'required|min:100|max:100|exists:users,email_verification',
        ]);

        if ($validator->fails()) {
            return frontend_view('message', [
                'heading' => 'Technical Error',
                'text'    => 'Invalid verification code format. Please try again.',
            ]);
        }

        $code = $request->get('code');

        $user = User::whereEmailVerification($code)->first();

        if ( $user->email_verification != '1' ) {
            $user->email_verification = 1;
            $user->save();

            // Throw verification email
            $user->notify( new \App\Notifications\Api\AccountVerified($user) );

            return frontend_view('message', [
                'heading' => 'Account Verified',
                'text'    => 'Verification completed successfully.',
            ]);
        }

        return frontend_view('message', [
            'heading' => 'Technical Error',
            'text'    => 'Invalid verification code.',
        ]);
    }
}
