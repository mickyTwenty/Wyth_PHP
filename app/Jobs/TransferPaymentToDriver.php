<?php

namespace App\Jobs;

use App\Models\TripEarning;
use App\Models\TripRide;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TransferPaymentToDriver implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tripEarning;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(TripEarning $tripEarning)
    {
        $this->tripEarning = $tripEarning->fresh();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {

            $bankAccount = $this->tripEarning->driver->bankAccount;
            $trip        = TripRide::find($this->tripEarning->trip_ride_id);

            $transfer = \App\Helpers\StripeHelper::transferAmount($bankAccount->account_id, $this->tripEarning->earning, $trip->id);
            logger('Earning Transferred Instantly - $' . $this->tripEarning->earning . ' to #' . $this->tripEarning->user_id . ' at ' . $bankAccount->account_id);

            if ($transfer) {
                $this->tripEarning->is_paid = 1;
                $this->tripEarning->save();

                // Send Command for Instant Payout
                \App\Helpers\StripeHelper::requestPayout($bankAccount->account_id, $this->tripEarning->earning);
            }

        } catch (\Exception $e) {
            logger('TransferCommissionJob: ' . $e->getMessage() . ' File: ' . $e->getFile() . ' Line: ' . $e->getLine());
        }
    }
}
