<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // Webservice Events & Listeners
        'App\Events\Api\JWTUserLogin' => [
            'App\Listeners\Api\JWTUserLoginSyncWithFirestore',
        ],
        'App\Events\Api\JWTUserUpdate' => [
            'App\Listeners\Api\JWTUserUpdateSyncWithFirestore',
        ],
        'App\Events\Api\JWTUserRegistration' => [
            // 'App\Listeners\SendWelcomeEmail',
            'App\Listeners\SendVerificationEmail',
            'App\Listeners\Api\JWTUserRegistrationSyncWithFirestore',
        ],
        'App\Events\CreateUserFromBackend' => [
            'App\Listeners\SendPasswordEmail',
            'App\Listeners\Api\JWTUserRegistrationSyncWithFirestore',
        ],
        'App\Events\Api\JWTUserLogout' => [
            'App\Listeners\Api\JWTUserLogout',
        ],
        'App\Events\AccountDeleted' => [
            'App\Listeners\CancelUserTripUponDeletion',
        ],
        'App\Events\NewFollowingEvent' => [
            // 'App\Listeners\NewFollowingListener',
            // 'App\Listeners\IncreaseFollowingCounterInApp',
        ],
        'App\Events\NewUnfollowingEvent' => [
            // 'App\Listeners\NewUnfollowingListener',
            // 'App\Listeners\DecreaseFollowingCounterInApp',
        ],
        'App\Events\BlockEvent' => [
            // 'App\Listeners\BlockSyncWithFirebase',
        ],
        'App\Events\UnblockEvent' => [
            // 'App\Listeners\UnblockSyncWithFirebase',
        ],
        'App\Events\UserDeleted' => [
            'App\Listeners\Api\JWTUserLogout',
            'App\Listeners\SyncUserStatusWithFirebase',
            'App\Listeners\DeleteUserData',
        ],
        'App\Events\UserDeactivated' => [
            // 'App\Listeners\Api\JWTUserLogout',
            // 'App\Listeners\SyncUserStatusWithFirebase',
        ],
        'App\Events\UserActivated' => [
            // 'App\Listeners\SyncUserStatusWithFirebase',
        ],
        'App\Events\UserFacebookAccountSynced' => [
            'App\Listeners\SetFlagFacebookAccountSynced',
        ],
        'App\Events\UserEmailAccountVerified' => [
            //
        ],
        'App\Events\TripRideCreated' => [
            'App\Listeners\AddTripRideActivity',
            'App\Listeners\PostRideDetailsToFirestore',
        ],
        'App\Events\TripRideRouteCreated' => [
            'App\Listeners\CreateRidePolygons',
        ],
        'App\Events\RideSearches' => [
            'App\Listeners\SaveRideSearches',
        ],
        'App\Events\TripDeleted' => [
            'App\Listeners\DeleteTripRoutePolygons',
            'App\Listeners\NotifyPassengerTripHasBeenDeleted',
        ],
        'App\Events\TripCanceledByDriver' => [
            'App\Listeners\DeleteTripRoutePolygons',
            'App\Listeners\NotifyPassengerTripHasBeenCanceled',
        ],
        'App\Events\RideCanceledByDriver' => [
            'App\Listeners\DeleteRideRoutePolygons',
            'App\Listeners\NotifyPassengerRideHasBeenCanceled',
        ],
        'App\Events\NotifySubscribedUser' => [
//            'App\Listeners\DeleteRideRoutePolygons',
            'App\Listeners\NotifySubscriberRideHasBeenCanceled',
        ],
        'App\Events\TripMembersAdded' => [
            'App\Listeners\UpdateSeatsAvailable',
            'App\Listeners\RemovePendingOffers',
            // 'App\Listeners\NotifyUsersOnAddedToTrip', // NOTE: Functionality done but commented explicitly
        ],
        'App\Events\TripMembersUpdated' => [
            'App\Listeners\UpdateSeatsAvailable',
        ],
        'App\Events\OfferMadeByDriver' => [
            'App\Listeners\PostDriverOfferToFireStore',
        ],
        'App\Events\OfferAcceptedByDriver' => [
            'App\Listeners\NotifyPassengerForOfferAcceptance',
        ],
        'App\Events\OfferMadeByPassenger' => [
            'App\Listeners\PostPassengerOfferToFireStore',
            'App\Listeners\SavePassengersPickUpDropOffLocations',
        ],
        'App\Events\OfferAcceptedByPassenger' => [
            // 'App\Listeners\MarkPassengerAsConfirm', // NOTE: Already doing in TripRide @makeConfirmRideByPassenger
            // 'App\Listeners\IntimateDriverAboutTimeSelection', // NOTE: This is to check both rides availability zero
            'App\Listeners\IntimateDriverAboutTimeSelectionIndividualLeg', // NOTE: This is for individual leg of trip.
            'App\Listeners\NotifyDriverForOfferAcceptance',
            'App\Listeners\ExpireDriverOffer',
        ],
        'App\Events\OfferRejectedByPassenger' => [
            'App\Listeners\NotifyDriverForOfferRejection',
        ],
        'App\Events\PassengerTripPayment' => [
            // 'App\Listeners\IntimateDriverAboutTimeSelection',
            'App\Listeners\IntimateDriverAboutTimeSelectionIndividualLeg',
        ],
        'App\Events\TripCreatedByDriver' => [
            'App\Listeners\SendNotificationToInvitees',
            'App\Listeners\SendNotificationToRouteSubscribers',
        ],
        'App\Events\TripCreatedByPassenger' => [
            // 'App\Listeners\SendNotificationToInvitees',
        ],
        'App\Events\PassengerBookNow' => [
            'App\Listeners\ProcessBookNowPayment',
        ],
        'App\Events\PassengerAddedToTrip' => [
            'App\Listeners\SyncMemberWithFirestore',
        ],
        'App\Events\PassengerAcceptedOffer' => [
            'App\Listeners\ProcessTripPayment',
        ],
        'App\Events\TerminateOfferUponTimeChange' => [
            'App\Listeners\RemoveOfferFromDatabase'
        ],
        'App\Events\PassengerRemovedFromTrip' => [
            'App\Listeners\SendNotificationToPassengerForRemovalByDriver',
            'App\Listeners\RemovePassengerOffersUponKick',
            'App\Listeners\SyncMemberWithFirestore'
        ],
        'App\Events\PassengerCanceledTrip' => [
            'App\Listeners\SendNotificationToDriverAboutPassengerRemoval',
            'App\Listeners\RemovePassengerOffersUponKick',
            'App\Listeners\SyncMemberWithFirestore'
        ],
        'App\Events\TripPickupTimeUpdated' => [
            'App\Listeners\SendTripTimeNotificationToMembers',
            'App\Listeners\UpdateRideStatusUponTimeSelection',
        ],
        'App\Events\PassengerPickupMarked' => [
            'App\Listeners\SendPickupNotificationToPassenger'
        ],
        'App\Events\PassengerDropoffMarked' => [
            'App\Listeners\SendDropoffNotificationToPassenger',
            'App\Listeners\SyncDroppedPassengerWithFirestore',
            'App\Listeners\SetPassengersPendingRatingFlag',
        ],
        'App\Events\TripStarted' => [
            'App\Listeners\SendTripStartNotificationToPassengers',
            'App\Listeners\SendEmailToSharedItineraryPeople'
        ],
        'App\Events\TripEnded' => [
            // 'App\Listeners\SendTripEndNotificationToDriverIfRoundTrip',
            'App\Listeners\SetUserPendingRatingFlag',
            'App\Listeners\ReplicateDriversEarning',
        ],
        'App\Events\TripRated' => [
            'App\Listeners\UpdateUserAverageRating',
            'App\Listeners\PushNotificationOnNewRating',
        ],
        'App\Events\Api\NotificationsListed' => [
            'App\Listeners\Api\UpdateUserUnreadNotificationsMeta',
            'App\Listeners\Api\UpdateNotificationsCounterOnFireStore',
        ],
        'App\Events\NewNotificationAdded' => [
            'App\Listeners\IncreaseUserUnreadCount',
            'App\Listeners\SyncUserUnreadCountWithFirestore',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
