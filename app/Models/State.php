<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class State extends Model
{
    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'states';

    public static function listStates($country_id)
    {
        return Cache::remember('states' . $country_id, 1440, function () use ($country_id) {
            return self::whereCountryId($country_id)->pluck('name', 'id');
        });
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
