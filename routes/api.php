<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['api'])->namespace('Api')->prefix('v1')->group(function () {

    Route::get('boot-me-up', 'WebserviceController@initializationConfigs');
    Route::get('list/schools', 'WebserviceController@getSchools');

    Route::post('register', 'WebserviceController@register');
    Route::post('verify-account', 'WebserviceController@verifyAccountByEmailCode');
    Route::post('login', 'WebserviceController@login');
    Route::post('reset-password', 'WebserviceController@resetPassword');
    Route::post('resend-verification-email', 'WebserviceController@resendVerificationEmail');
    Route::post('logout', 'WebserviceController@logout');
    Route::post('fb-login', 'WebserviceController@loginWithFacebook');
    Route::post('firebase/notification', 'WebserviceController@firebaseNotification');

    Route::match(['GET', 'PUT', 'POST'], 'test', 'WebserviceController@testMethods');

    Route::group(['middleware' => 'jwt-auth'], function () {

        Route::group(['prefix' => 'account'], function() {
            Route::post('delete', 'WebserviceController@deleteAccount');
            Route::post('me', 'WebserviceController@viewMyProfile');
            Route::post('update', 'WebserviceController@updateMyProfile');
            Route::post('view/{user_id}', 'WebserviceController@viewProfile');
            Route::post('view/mutual/{user_id}', 'WebserviceController@getListOfMutualFollowers');
            Route::post('upgrade', 'WebserviceController@upgradeToBusiness');
            Route::post('downgrade', 'WebserviceController@downgradeToNormal');
            Route::put('facebook', 'WebserviceController@bindAccountWithFacebook');
            Route::put('favorite/{user}', 'WebserviceController@addFavorite');
            Route::delete('favorite/{user}', 'WebserviceController@removeFavorite');
            Route::put('block/{user}', 'WebserviceController@doBlock');
            Route::delete('block/{user}', 'WebserviceController@doUnblock');
            Route::post('sync-friends', 'WebserviceController@syncFriends');
            Route::post('upgrade/driver', 'WebserviceController@upgradeToDriverRequest');

            Route::post('list/followings', 'WebserviceController@listMyFollowings');
            Route::post('list/notifications', 'WebserviceController@listMyNotifications');
            Route::post('list/pending-ratings', 'RideController@pendingTripRating');
            Route::post('notification/disable/{notification_id}', 'WebserviceController@actionCompletedOnNotification');
        });

        Route::group(['prefix' => 'ride'], function() {
            Route::post('driver/popularity', 'RideController@ridePopularity');
            Route::post('estimates', 'RideController@calculateRideEstimates');
            Route::post('create/public', 'RideController@createPublicRide');
            Route::post('search', 'RideController@passengerSearchRide');
            Route::post('requests', 'RideController@driverSearchRide');
        });

        Route::group(['prefix' => 'passenger'], function() {
            Route::post('create/ride', 'RideController@passengerCreateRide');
            Route::post('subscribe/ride', 'RideController@passengerSubscribeForRide');
            Route::post('make/offer', 'RideController@passengerMakeOffer');
            Route::post('accept/offer', 'RideController@passengerAcceptOffer');
            Route::post('reject/offer', 'RideController@passengerRejectOffer');
            Route::post('trip/payment', 'RideController@passengerTripPayment');
            Route::post('trip/cancel', 'RideController@passengerCancelTrip');
            Route::post('offers', 'RideController@passengerRideOffers');
            Route::post('offer/detail/{id}', 'RideController@passengerOfferDetail');
            Route::post('past/trips', 'RideController@passengerPastTrips');
            Route::post('upcoming/trips', 'RideController@passengerUpcomingTrips');
            Route::post('ride/detail/{id}', 'RideController@passengerRideDetail');
            Route::post('book-now', 'RideController@passengerBookNow');
            // Route::post('rate/trip/{id}', 'RideController@passengerRateTrip');
            Route::post('rate/drivers', 'RideController@passengerRateDrivers');
            Route::post('add/credit-card', 'RideController@passengerInsertCreditCard');
            Route::post('get/credit-card', 'RideController@passengerGetCreditCard');
            Route::post('default/credit-card', 'RideController@passengerSetDefaultCreditCard');
            Route::post('remove/credit-card', 'RideController@passengerRemoveCreditCard');
            Route::post('payment/history', 'RideController@passengerPaymentHistory');
            Route::post('share-itenerary', 'RideController@passengerShareItinerary');
            Route::post('apply-promo', 'RideController@getCouponDetails');
        });

        Route::group(['prefix' => 'driver'], function() {
            Route::post('make/offer', 'RideController@driverMakeOffer');
            Route::post('accept/offer', 'RideController@driverAcceptOffer');
            Route::post('offers', 'RideController@driverRideOffers');
            Route::post('offer/detail/{id}', 'RideController@driverOfferDetail');
            Route::post('past/trips', 'RideController@driverPastTrips');
            Route::post('upcoming/trips', 'RideController@driverUpcomingTrips');
            Route::post('ride/detail/{id}', 'RideController@driverRideDetail');
            Route::post('ride/eliminate', 'RideController@driverDeletePassenger');
			Route::post('preschedule/ride/time', 'RideController@driverPreScheduleRideTime');
            Route::post('schedule/ride/time', 'RideController@driverScheduleRideTime');
            Route::post('schedule/ride/time/forcefully', 'RideController@driverScheduleRideTimeForcefully');
            Route::post('start/trip', 'RideController@driverStartTrip');
            Route::post('resume/trip', 'RideController@driverResumeTrip');
            Route::post('mark/pickup', 'RideController@driverMarkPickup');
            Route::post('mark/dropoff', 'RideController@driverMarkDropoff');
            Route::post('end/trip', 'RideController@driverEndTrip');
            Route::post('trip/cancel', 'RideController@driverCancelTrip');
            // Route::post('rate/trip/{id}', 'RideController@driverRateTrip');
            Route::post('rate/passengers', 'RideController@driverRateTripMembers');
            Route::post('ride/update-seats/{rideId}', 'RideController@driverUpdateSeats');
            Route::post('bank-details/read', 'RideController@driverGetBankAccounts');
            Route::post('bank-details/update', 'RideController@driverUpdateBankAccounts');
            Route::post('payment/history', 'RideController@driverPaymentHistory');
            Route::post('share-itenerary', 'RideController@driverShareItinerary');
            Route::post('list/subscribed-routes', 'RideController@driverListSubscribedRoutes');
        });
    });

});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
