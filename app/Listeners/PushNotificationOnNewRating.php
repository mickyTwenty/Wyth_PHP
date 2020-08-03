<?php

namespace App\Listeners;

use App\Events\TripRated;
use App\Models\TripMember;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PushNotificationOnNewRating implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  TripRated  $event
     * @return void
     */
    public function handle(TripRated $event)
    {
        $tripRide   = $event->tripRide;
        $tripRating = $event->tripRating;
        $attributes = $event->attributes;

        // Dont send push notification if attributes found.
        if (array_key_exists('sendNotification', $attributes) && false === $attributes['sendNotification']) {
            return;
        }

        if ($tripRating->rater_type = TripMember::TYPE_PASSENGER || $tripRating->rater_type = TripMember::TYPE_DRIVER) {
            $tripRating->ratee->createNotification($tripRating->ratee_type, 'You\'ve been rated for a trip', [
                'message' => "You have been rated ".rtrim($tripRating->rating, '.0')." ".str_plural('star', $tripRating->rating)." for a Trip #" . $tripRide->id,
                'type'    => 'new_rating_received',
            ])->notActionable()
            ->customPayload([
                'click_action'     => 'new_rating_received',
                'trip_id'          => $tripRide->id,
            ])->throwNotificationsVia('push')->build();
        }
    }
}
