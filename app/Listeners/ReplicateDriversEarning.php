<?php

namespace App\Listeners;

use App\Events\TripEnded;
use App\Models\TripRide;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ReplicateDriversEarning
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
     * @param  TripEnded $event
     * @return void
     */
    public function handle(TripEnded $event)
    {
        $tripRide = $event->tripRide;
        $driver   = $event->driver;

        $ride = TripRide::with(['trip.driver' => function ($query) {
            return $query;
            // return $query->withTrashed();
        }])->find($tripRide->id);

        $totalFare = $ride->members()->readyToFly()->sum('fare');

        if ($totalFare <= 0) {
            return;
        }

        // Client permission
        $commissionPercentage = (100 - constants('global.ride.driver_earning'));
        $commission           = calculatePercentage($totalFare, $commissionPercentage);
        $earningAmount        = $totalFare - $commission;
        $payoutType           = $ride->trip->payout_type;
        $payoutCharges        = $payoutType == 'standard' ? 0 : calculatePercentage($earningAmount, constants('global.ride.payout_charges'));

        $earning = $ride->earning()->getRelated()->fill([
            'gross_amount'          => $totalFare,
            'commission'            => $commission,
            'commission_percentage' => $commissionPercentage,
            'payout_charges'        => $payoutCharges,
            'payout_percentage'     => constants('global.ride.payout_charges'),
            'payout_type'           => $payoutType,
            'earning'               => ($earningAmount - $payoutCharges),
        ]);

        $earning->driver()->associate($ride->trip->driver);
        $earning->ride()->associate($ride);

        $earning->save();

        if ('expedited' == $payoutType) {
            dispatch(new \App\Jobs\TransferPaymentToDriver($earning));
        }
    }
}
