<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripEarning extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'trip_ride_id',
        'user_id',
        'gross_amount',
        'commission',
        'commission_percentage',
        'payout_charges',
        'payout_percentage',
        'payout_type',
        'earning',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_paid'        => 'boolean',
        'gross_amount'   => 'float',
        'commission'     => 'float',
        'payout_charges' => 'float',
        'earning'        => 'float',
    ];

    public static function disburseEarning()
    {
        $records = self::with('driver.bankAccount')->unpaid()
            ->payoutType('standard')
            ->get();

        $drivers = $records->groupBy('user_id');

        foreach ($drivers as $driverEarnings) {

            $driver      = $driverEarnings->first()->driver;
            $bankAccount = $driver->bankAccount;

            if (!$bankAccount->account_id) {
                info('Weekly disbursal error, connected account id not found for user #' . $driver->id);
                continue;
            }

            $totalEarning = $driverEarnings->sum('earning');

            $transfer = \App\Helpers\StripeHelper::transferAmount($bankAccount->account_id, $totalEarning, null);
            logger('Earning Transferred - $' . $totalEarning . ' to #' . $driver->id . ' at ' . $bankAccount->account_id);

            if ($transfer) {
                self::where('user_id', $driver->id)
                    ->unpaid()
                    ->whereIn('id', $driverEarnings->pluck('id'))
                    ->update(['is_paid' => 1]);

                // Send Command for Instant Payout
                \App\Helpers\StripeHelper::requestPayout($bankAccount->account_id, $totalEarning);
            }
        }

        return $records;
    }

    /**
     * Scopes
     */
    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', 0);
    }

    public function scopePaid($query)
    {
        return $query->where('is_paid', 1);
    }

    public function scopePayoutType($query, $type)
    {
        return $query->where('payout_type', $type);
    }

    /**
     * Relations
     */
    public function driver()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ride()
    {
        return $this->belongsTo(TripRide::class, 'trip_ride_id');
    }
}
