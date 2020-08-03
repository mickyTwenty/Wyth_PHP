<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverBankDetail extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'bank_name',
        'account_title',
        'account_number',
        'routing_number',
        'personal_id_number',
        'ssn_last_4',
        'period',
        // 'checking_account',
        // 'swift_code',
        // 'bank_address',
        'active',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Accessors
     */
    function getPeriodTextAttribute()
    {
        switch ($this->attributes['period']) {
            case 'daily':
                return 'Expedited Payment within 24 hours';
                break;
            case 'standard':
                return 'Standard Payment';
                break;
            default:
                return title_case($this->attributes['period']);
                break;
        }
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
