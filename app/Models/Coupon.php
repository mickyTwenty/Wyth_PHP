<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;

class Coupon extends Model
{
    use SoftDeletes;

    const TYPE_AMOUNT     = 1;
    const TYPE_PERCENTAGE = 2;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'discount_type',
        'value',
        'available_from',
        'available_till',
    ];

    protected $dates = ['available_from', 'available_till'];

    public function setAvailableFromAttribute($value)
    {
        $this->attributes['available_from'] = Carbon::parse($value);
    }

    public function setAvailableTillAttribute($value)
    {
        $this->attributes['available_till'] = Carbon::parse($value);
    }

    public static function getNetAmountToCharge($coupon, $amount)
    {
        if (!$coupon instanceof Coupon) {
            $coupon = self::find($coupon);
        }

        if (!$coupon) {
            throw new Exception('Coupon not found.');
        }

        if ( $coupon->discount_type == self::TYPE_PERCENTAGE ) {
            return ($amount - calculatePercentage($amount, $coupon->value));
        }

        return max(0.00, ($amount - $coupon->value));
    }

    /**
     * Scopes
     */
    public function scopeFixed($query)
    {
        return $query->where('discount_type', self::TYPE_AMOUNT);
    }

    public function scopePercentage($query)
    {
        return $query->where('discount_type', self::TYPE_PERCENTAGE);
    }

    public function setCodeAttribute($value)
    {
        $this->attributes['code'] = strtoupper($value);
    }

    public function getDiscountTypeTextAttribute()
    {
        return ($this->attributes['discount_type'] == self::TYPE_AMOUNT) ? 'Amount' : 'Percentage';
    }

    public function getAvailableFromAttribute()
    {
        return Carbon::parse($this->attributes['available_from'])->format(config('constants.api.global.formats.date'));
    }

    public function getAvailableTillAttribute()
    {
        return Carbon::parse($this->attributes['available_till'])->format(config('constants.api.global.formats.date'));
    }

    public static function validateCoupon($code)
    {
        return self::where('code', strtoupper($code))
            ->whereDate('available_from', '<=', Carbon::now()->toDateString())
            ->whereDate('available_till', '>=', Carbon::now()->toDateString())->first();
    }
}
