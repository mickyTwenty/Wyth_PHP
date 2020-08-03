<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class City extends Model
{
    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'cities';

    protected $fillable = ['name', 'state_id'];

    public $timestamps = false;

    public static function listCities($state_id)
    {
        return Cache::remember('cities' . $state_id, 1440, function () use ($state_id) {
            return self::whereStateId($state_id)->pluck('name', 'id');
        });
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
