<?php

namespace App\Http\Controllers\Frontend;

use App\Models\User;
use Illuminate\Http\Request;
use Auth;

class UserController extends FrontendController
{
    public function passwordResetStatus(Request $request, $status = null)
    {
        Auth::logout();

        return frontend_view('account.reset_status');
    }
}
