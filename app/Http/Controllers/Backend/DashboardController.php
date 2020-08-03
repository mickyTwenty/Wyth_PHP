<?php

namespace App\Http\Controllers\Backend;

use App\Models\City;
use App\Models\Setting;
use App\Models\User;
use Auth;
use Cache;
use Illuminate\Http\Request;

class DashboardController extends BackendController
{
    protected $thisModule = [];

    public function getIndex()
    {
        $methodName = 'get' . ucfirst(user()->user_role_key) . 'Index';
        return app(__CLASS__)->$methodName();
    }

    public function getAdminIndex()
    {
        $allUsers = User::users()->get();

        $verifiedUsers = $allUsers->filter(function ($record) {
            return ($record->isVerified());
        });

        $stats                       = new \stdClass;
        $stats->total_users          = $allUsers->count();
        $stats->total_drivers        = User::whereRoleId(User::ROLE_DRIVER)->count();
        $stats->total_passengers     = User::whereRoleId(User::ROLE_NORMAL_USER)->count();
        $stats->total_verified_users = $verifiedUsers->count();

        return backend_view('dashboard', compact('stats'));
    }

    public function getCities($stateID)
    {
        return City::listCities($stateID);
    }

    public function editProfile(Request $request)
    {
        $record = Auth::user();

        if ($request->getMethod() == 'GET') {
            return backend_view('settings.profile', compact('record'));
        }

        $validator = \Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name'  => 'required',
            'email'      => 'required|email|max:255|unique:users,email,' . $record->id . ',id',
            'password'   => ($request->get('password') != '' ? 'min:6' : ''),
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $postData = $request->except('password');

        // Filter out remove images if selected and configured in access modifiers
        foreach (['profile_picture'] as $field) {
            if (isset($postData['remove_' . $field]) && $postData['remove_' . $field] == '1') {
                $postData[$field] = '';

                // Delete file as well
                $this->safelyRemoveFile(public_path($record->profile_picture_path));
            }
        }

        if ($request->hasFile('profile_picture')) {
            $imageName = $record->id . '-' . str_random(12) . '.' . $request->file('profile_picture')->getClientOriginalExtension();
            $path      = public_path(config('constants.front.dir.profilePicPath'));
            $request->file('profile_picture')->move($path, $imageName);
            $postData['profile_picture'] = $imageName;
        }

        if ($request->has('password') && $request->get('password', '') != '') {
            $postData['password'] = bcrypt($request->get('password'));
        }

        $record->update($postData);

        session()->flash('alert-success', 'Your profile has been updated successfully!');
        return redirect(route('backend.profile.setting'));
    }

    public function editSettings(Request $request)
    {
        Cache::forget('app.setting');
	
        $configs = Setting::extracts([
            'setting.application.cancellation_fee',
            'setting.application.transaction_fee',
            'setting.application.transaction_fee_local',
            'setting.application.local_max_distance',
            'setting.min_estimate',
            'setting.max_estimate',
            'setting.ride_cancellation_count',
            'setting.ride_cancellation_penalty',
            'setting.hear_about_us_options',
        ]);

        $allInOneConfigs['cancellation_fee']          = $configs->get('setting.application.cancellation_fee', '');
        $allInOneConfigs['transaction_fee']           = floatval($configs->get('setting.application.transaction_fee', 0));
        $allInOneConfigs['transaction_fee_local']     = floatval($configs->get('setting.application.transaction_fee_local', 0));
        $allInOneConfigs['local_max_distance']        = floatval($configs->get('setting.application.local_max_distance', 0));
        $allInOneConfigs['min_estimate']              = floatval($configs->get('setting.min_estimate', ''));
        $allInOneConfigs['max_estimate']              = floatval($configs->get('setting.max_estimate', ''));
        $allInOneConfigs['ride_cancellation_count']   = floatval($configs->get('setting.ride_cancellation_count'));
        $allInOneConfigs['ride_cancellation_penalty'] = floatval($configs->get('setting.ride_cancellation_penalty'));
        $allInOneConfigs['reference_source']          = renameKeyAsValue((array) $configs->get('setting.hear_about_us_options'));

        if ($request->getMethod() == 'GET') {
            return backend_view('settings.settings', compact('allInOneConfigs'));
        }

        $validator = \Validator::make($request->all(), [
            'cancellation_fee'          => 'required|numeric|max:100',
            'transaction_fee'           => 'required|numeric',
            'transaction_fee_local'     => 'required|numeric',
            'local_max_distance'        => 'required|numeric',
            'ride_cancellation_penalty' => 'required|numeric|max:100',
            'ride_cancellation_count'   => 'required|numeric|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        Setting::updateSettingArray([
            ['config_key' => 'setting.application.cancellation_fee', 'config_value' => intval($request->get('cancellation_fee'))],
            ['config_key' => 'setting.application.transaction_fee', 'config_value' => floatval($request->get('transaction_fee'))],
            ['config_key' => 'setting.application.transaction_fee_local', 'config_value' => floatval($request->get('transaction_fee_local'))],
            ['config_key' => 'setting.application.local_max_distance', 'config_value' => floatval($request->get('local_max_distance'))],
            ['config_key' => 'setting.min_estimate', 'config_value' => floatval($request->get('min_estimate'))],
            ['config_key' => 'setting.max_estimate', 'config_value' => floatval($request->get('max_estimate'))],
            ['config_key' => 'setting.ride_cancellation_count', 'config_value' => intval($request->get('ride_cancellation_count'))],
            ['config_key' => 'setting.ride_cancellation_penalty', 'config_value' => intval($request->get('ride_cancellation_penalty'))],
            ['config_key' => 'setting.hear_about_us_options', 'config_value' => json_encode($request->get('reference_source'))],
        ]);

        session()->flash('alert-success', 'Settings have been updated successfully!');
        return redirect(route('backend.settings'));
    }

    public function actionUserAgreement(Request $request)
    {
        Cache::forget('app.setting');

        $configs = Setting::extracts([
            'setting.user_agreement_driver',
            'setting.user_agreement_passenger',
        ]);

        $allInOneConfigs['user_agreement_driver']    = $configs->get('setting.user_agreement_driver', '');
        $allInOneConfigs['user_agreement_passenger'] = $configs->get('setting.user_agreement_passenger', '');

        if ($request->getMethod() == 'GET') {
            return backend_view('settings.agreements', compact('allInOneConfigs'));
        }

        $validator = \Validator::make($request->all(), [
            'user_agreement_driver'    => 'required|empty_html',
            'user_agreement_passenger' => 'required|empty_html',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        Setting::updateSettingArray([
            ['config_key' => 'setting.user_agreement_driver', 'config_value' => $request->get('user_agreement_driver')],
            ['config_key' => 'setting.user_agreement_passenger', 'config_value' => $request->get('user_agreement_passenger')],
        ]);

        session()->flash('alert-success', 'Agreements have been updated successfully!');
        return redirect(route('backend.system.agreement'));
    }
}
