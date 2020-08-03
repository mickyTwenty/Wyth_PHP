<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PassengerCard extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'stripe_customer_id',
        'card_token',
        'last_digits',
        'active',
        'is_default',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function isDefault()
    {
        return (bool) (intval($this->attributes['is_default']) === 1);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', 1);
    }

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'passenger_card_id');
    }
}
