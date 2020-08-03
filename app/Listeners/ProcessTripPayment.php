<?php

namespace App\Listeners;

use App\Events\PassengerAcceptedOffer;
use App\Helpers\StripeHelper;
use App\Models\Coupon;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\TripMember;

//use Illuminate\Queue\InteractsWithQueue;
//use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessTripPayment
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
     * @param  PassengerAcceptedOffer  $event
     * @return void
     */
    public function handle(PassengerAcceptedOffer $event)
    {
        $ride           = $event->ride;
        $passenger      = $event->passenger;
        $offer          = $event->offer;
        $amountToCharge = $event->amountToCharge;
        $trip           = $ride->trip;
        $card           = $passenger->user->creditCard;
        $payment        = false;

        if ($passenger->coupon_id) {
            info('Amount to charge: ' . $amountToCharge);
            try {
                $amountToCharge = Coupon::getNetAmountToCharge($passenger->coupon_id, $amountToCharge);
            } catch (\Exception $e) {
                info('Coupon not found: ' . $passenger->coupon_id);
            }
            info('Amount to charge: ' . $amountToCharge);
        }

        if ($card) {
            if (!$ride->members()->readyToFly()->memberId($passenger->user_id)->first()) {

		$configs = Setting::extracts([
                    'setting.application.transaction_fee',
		    'setting.application.transaction_fee_local',
		    'setting.application.local_max_distance',
            	]);
            	$transactionFee = floatval($configs->get('setting.application.transaction_fee', 0.00));
	
	        if (floatval($trip->expected_distance) < floatval($configs->get('setting.application.local_max_distance', 0.00))) {
		    $transactionFee = floatval($configs->get('setting.application.transaction_fee_local', 0.00));
	    	}

                if ($offer->is_roundtrip) {
                    $payment = StripeHelper::payViaStripe($card->stripe_customer_id, ($amountToCharge + $transactionFee), $trip->id);

                    if ($payment) {
                        foreach ($trip->rides as $tripRide) {
                            $tripRide->members()->memberId($passenger->user_id)->update(['payment_status' => 1]);
                        }
                    }
                } else {
                    $payment = StripeHelper::payViaStripe($card->stripe_customer_id, ($amountToCharge + $transactionFee), $trip->id);

                    if ($payment) {
                        $passenger->update(['payment_status' => 1]);
                    }
                }

                if ($payment) {
                    $card->transactions()->create([
                        'user_id'          => $passenger->user_id,
                        'stripe_charge_id' => $payment->id,
                        'trip_ride_id'     => $offer->is_roundtrip ? $ride->trip->getGoingRideOfTrip()->id : $ride->id,
                        'amount'           => $amountToCharge,
                        'transaction_fee'  => $transactionFee,
                        'payload'          => $payment,
                    ]);
                } else {
                    TripMember::intimateMemberForFailedPayment($ride, $passenger->user);
                    $ride->intimateDriverAboutFailedPayment($passenger->user);
                }
            }
        } else {
            TripMember::intimateMemberForFailedPayment($ride, $passenger->user);
            $ride->intimateDriverAboutFailedPayment($passenger->user);
        }
    }
}
