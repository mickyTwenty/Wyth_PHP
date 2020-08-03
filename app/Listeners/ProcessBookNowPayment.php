<?php

namespace App\Listeners;

use App\Events\PassengerBookNow;
use App\Events\PassengerTripPayment;
use App\Helpers\StripeHelper;
use App\Models\Coupon;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\TripMember;
use App\Models\User;

//use Illuminate\Queue\InteractsWithQueue;
//use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessBookNowPayment
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
     * @param  PassengerBookNow  $event
     * @return void
     */
    public function handle(PassengerBookNow $event)
    {
        $ride           = $event->ride;
        $passengerIds   = $event->passengerIds;
        $amountToCharge = $event->amountToCharge;
        $trip           = $ride->trip;

        $members = $ride->members()->confirmed()->whereIn('user_id', $passengerIds)->get();

        if (count($members)) {
            foreach ($members as $member) {
		$member->update(['payment_status' => 1]);

                /*$card = $member->user->creditCard;
                $chargingAmountForMember = $amountToCharge;

                if ($member->coupon_id) {
                    info('Amount to charge: ' . $chargingAmountForMember);
                    try {
                        $chargingAmountForMember = Coupon::getNetAmountToCharge($member->coupon_id, $chargingAmountForMember);
                    } catch (\Exception $e) {
                        info('Coupon not found: ' . $member->coupon_id);
                    }
                    info('Amount to charge: ' . $chargingAmountForMember);
                }

                if ($card) {
                    $payment = false;

		    $configs = Setting::extracts([
                    	'setting.application.transaction_fee',
		    	'setting.application.transaction_fee_local',
		    	'setting.application.local_max_distance',
		    ]);
		    $transactionFee = floatval($configs->get('setting.application.transaction_fee', 0.00));

		    if (floatval($trip->expected_distance) < floatval($configs->get('setting.application.local_max_distance', 0.00))) {
		        $transactionFee = floatval($configs->get('setting.application.transaction_fee_local', 0.00));
	    	    }

                    if($chargingAmountForMember !=0) {
			if ($trip->is_roundtrip) {
                            $payment = StripeHelper::payViaStripe($card->stripe_customer_id, ($chargingAmountForMember + $transactionFee), $trip->id);

                            if ($payment) {
                                foreach ($trip->rides as $tripRide) {
                                    $tripRide->members()->memberId($member->user_id)->update(['payment_status' => 1]);
                                }
                            }
                        } else {
                            $payment = StripeHelper::payViaStripe($card->stripe_customer_id, ($chargingAmountForMember + $transactionFee), $trip->id);

                            if ($payment) {
                                $member->update(['payment_status' => 1]);
                            }
                        }
		    }
		    else
		    {
			$member->update(['payment_status' => 1]);
		    }

                    if ($payment) {
                        $card->transactions()->create([
                            'user_id'          => $member->user_id,
                            'stripe_charge_id' => $payment->id,
                            'trip_ride_id'     => $ride->id,
                            'amount'           => $chargingAmountForMember,
                            'transaction_fee'  => $transactionFee,
                            'payload'          => $payment,
                        ]);

                        event(new PassengerTripPayment($ride, User::find($member->user_id), $trip->driver->id));
                    } else {
                        TripMember::intimateMemberForFailedPayment($ride, $member->user);
                        $ride->intimateDriverAboutFailedPayment($member->user);
                    }
                } else {
                    TripMember::intimateMemberForFailedPayment($ride, $member->user);
                    $ride->intimateDriverAboutFailedPayment($member->user);
                }*/
            }
        }
    }
}
