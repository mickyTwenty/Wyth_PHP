<?php

namespace App\Listeners;

use App\Events\NotifySubscribedUser;
use App\Models\TripMember;
use App\Models\TripRideOffer;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifySubscriberRideHasBeenCanceled implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  NotifySubscribedUser  $event
     * @return void
     */
    public function handle(NotifySubscribedUser $event)
    {
        $trip       = $event->trip;
        $tripDriver = $trip->driver;

        $query = TripRideOffer::whereIn('trip_ride_id', $trip->rides->pluck('id'))->with(["sender", "receiver"])->where('is_roundtrip', 1);//->get();

        $offers = clone $query;
//        echo "<pre>";
//        print_r($offers->get()->toArray());
//        echo "<pre>";exit;
        foreach ($offers->get() as $offer)
        {
            if($tripDriver->id != $offer->from_user_id)
            {
                $user = $offer->sender;
            }
            else
            {
                $user = $offer->receiver;
            }

            if('local' == getenv('APP_ENV'))
            {
                logger($user);
            }

            $user->createNotification(TripMember::TYPE_PASSENGER, 'Offer has been expired', [
                'message' => 'Your offer has been expired due to cancellation of trip',
                'type'    => 'offer_expired',
            ])
                ->notActionable()
                ->customPayload(['click_action' => 'offer_expired'])
                ->throwNotificationsVia('push')->build();
        }

        $query->delete();
        //TripRideOffer::whereIn('trip_ride_id', $trip->rides->pluck('id'))->with(["sender", "receiver"])->where('is_roundtrip', 1)->delete();
    }
}
