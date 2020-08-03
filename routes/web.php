<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
use App\Models\User;
use App\Classes\FireStoreHandler;
use App\Models\Trip;
use App\Models\TripRide;
use App\Models\TripRideOffer;
use App\Models\TripMember;
use App\Models\PassengerCard;

Route::prefix('')->namespace('Frontend')->group(function () {
    Route::get('/track/ride/{rideShared}', 'RideController@trackRide')->name('track.ride');
    Route::get('verification/email', 'Auth\LoginController@emailVerification')->name('api.verification.email'); // For direct password reset

    Route::get('/home', function() {
        return view('welcome');
    });
    Route::get('/', function() {
        return view('welcome');
    });

    Route::get('/agreement/{userType}', function($userType) {

        $content = \App\Models\Setting::extract('setting.user_agreement_' . $userType);
        return frontend_view('help.' . $userType);

    })->where('userType', '(driver|passenger)');

    Route::get('/help/{userType}', function($userType) {

        // if (!file_exists(resource_path('views/frontend/help/'.$userType.'.blade.php')))
        //     abort(404);

        if (!in_array($userType, ['passenger', 'driver'])) {
            abort(404);
        }

        $faqs = App\Models\FAQ::where('type', $userType)->get();

        return frontend_view('help.help', compact('userType', 'faqs'));
    });

    // Reset password via link
    Route::get('account/reset/{status}', 'UserController@passwordResetStatus');
    Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('frontend.password.reset');
    Route::post('password/reset', 'Auth\ResetPasswordController@reset')->name('frontend.password.request');
});

Route::prefix('backend')->namespace('Backend')->group(function () {
    Route::get('/', function() { return redirect('/backend/dashboard'); });
    Route::get('/login', 'Auth\LoginController@showLoginForm');
    Route::post('/login', 'Auth\LoginController@login');
    Route::get('/logout', 'Auth\LoginController@logout');
    Route::get('/cities/{stateID}', 'DashboardController@getCities');
    Route::get('find/school', 'SchoolController@findSchool')->name('find.school');

    // Password Reset Routes...
    Route::get('reset-password', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('backend.password.request');
    Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('backend.password.email');
    Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('backend.password.reset');
    Route::post('reset-password', 'Auth\ResetPasswordController@reset')->name('backend.password.final');

    Route::group(['middleware' => 'backend.auth'], function () {
        Route::get('/dashboard', 'DashboardController@getIndex')->name('backend.dashboard');
        Route::match(['GET', 'POST'], '/system/edit-profile', 'DashboardController@editProfile')->name('backend.profile.setting');

        Route::group(['middleware' => 'backend.admin'], function () {
            Route::get('/users/push-notification', 'UserController@pushNotificationUsers');
            Route::post('/users/push-notification', 'UserController@sendPushNotification');
            Route::get('/users/verification', 'UserController@verificationListPending');
            Route::get('/users/data', 'UserController@data');
            Route::get('/users/detail/{record}', 'UserController@detail');
            Route::get('/users/purchases/{record}', 'UserController@purchases');
            Route::get('/users/block/{record}', 'UserController@block');
            Route::get('/users/unblock/{record}', 'UserController@unblock');
            Route::get('/users/verified/{record}', 'UserController@verify');
            Route::get('/users/unverified/{record}', 'UserController@unverify');
            Route::post('/users/{record}', 'UserController@handleVerification');
            Route::get('/users/{index?}', 'UserController@index');
            Route::delete('/users/{record}', 'UserController@destroy');

            Route::get('/users/create/driver', 'DriverController@createDriver');
            Route::post('/users/create/driver', 'DriverController@storeDriver');
            Route::get('/users/edit/driver/{record}', 'DriverController@editDriver');
            Route::post('/users/edit/driver/{record}', 'DriverController@updateDriver');

            Route::get('/users/create/passenger', 'PassengerController@createPassenger');
            Route::post('/users/create/passenger', 'PassengerController@storePassenger');
            Route::get('/users/edit/passenger/{record}', 'PassengerController@editPassenger');
            Route::post('/users/edit/passenger/{record}', 'PassengerController@updatePassenger');

            Route::get('/reviews/data', 'ReviewController@data');
            Route::get('/reviews/{index?}', 'ReviewController@index');
            Route::delete('/reviews/{record}', 'ReviewController@destroy');
            Route::get('/reviews/approve/{record}', 'ReviewController@approve');
            Route::get('/reviews/disapprove/{record}', 'ReviewController@disapprove');

            Route::get('/user-stats/data', 'StatsController@data');
            Route::get('/user-stats/detail/{record}', 'StatsController@userStats');
            Route::get('/user-stats/{index?}', 'StatsController@paymentTransaction');

            Route::get('/promo-codes/create', 'PromoCodeController@create');
            Route::post('/promo-codes', 'PromoCodeController@save');
            Route::delete('/promo-codes/{record}', 'PromoCodeController@delete');
            Route::get('/promo-codes/data', 'PromoCodeController@data');
            Route::get('/promo-codes/edit/{record}', 'PromoCodeController@edit');
            Route::post('/promo-codes/{record}', 'PromoCodeController@update');
            Route::get('/promo-codes/{index?}', 'PromoCodeController@index')->name('promo-codes.index');

            Route::get('/schools/create', 'SchoolController@create');
            Route::post('/schools', 'SchoolController@save');
            Route::delete('/schools/{record}', 'SchoolController@delete');
            Route::get('/schools/edit/{record}', 'SchoolController@edit');
            Route::post('/schools/{record}', 'SchoolController@update');
            Route::get('/schools/{index?}', 'SchoolController@index')->name('schools.index');

            Route::match(['GET', 'POST'], '/reports', 'ReportsController@index');
            Route::match(['GET', 'POST'], '/system/edit-settings', 'DashboardController@editSettings')->name('backend.settings');

            Route::group(['prefix' => 'trips'], function() {
                Route::get('listing', 'TripController@ridesListing')->name('backend.trips.listing');
                Route::get('listing/data', 'TripController@ridesListingData')->name('backend.trips.listing.data');
                Route::get('payments', 'TripController@payments')->name('backend.payments');
                Route::get('payments/data', 'TripController@paymentsData')->name('backend.payments.data');
                Route::get('payments/detail/{record}', 'TripController@paymentDetail')->name('backend.payments.detail');

                Route::get('cancel/{ride}', 'TripController@cancelTrip')->name('backend.trips.cancel');

                Route::get('canceled', 'TripController@canceledTrips')->name('backend.trips.canceled');
                Route::get('canceled/data', 'TripController@canceledTripsData')->name('backend.trips.canceled.data');

                Route::get('hot-destinations', 'TripController@hotDestinations')->name('backend.hot.destinations');
            });

            Route::group(['prefix' => 'reports'], function() {
                Route::get('dashboard', 'ReportsController@dashboard')->name('backend.reports.dashboard');
                Route::match(['GET', 'POST'], 'car/statistics', 'ReportsController@carStatistics')->name('backend.reports.car.statistics');
                Route::match(['GET', 'POST'], 'popular/driver', 'ReportsController@popularDriver')->name('backend.reports.popular.driver');
                Route::match(['GET', 'POST'], 'driver/earning', 'ReportsController@driverEarning')->name('backend.reports.driver.earning');
            });

            Route::get('/faqs/create', 'FAQController@create');
            Route::post('/faqs', 'FAQController@save');
            Route::delete('/faqs/{record}', 'FAQController@delete');
            Route::get('/faqs/edit/{record}', 'FAQController@edit');
            Route::post('/faqs/{record}', 'FAQController@update');
            Route::get('/faqs/{index?}', 'FAQController@index')->name('faqs.index');

            Route::match(['GET', 'POST'], '/system/user-agreement', 'DashboardController@actionUserAgreement')->name('backend.system.agreement');
        });
    });
});

Route::group(['middleware' => 'backend.auth'], function () {
    Route::get('appmaisters-logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
});
