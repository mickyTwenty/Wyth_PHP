<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RidePreference extends Model
{
    use SoftDeletes;

    private static $preferences;

    public static function getPreferences()
    {
        $preferences = self::with(['options' => function($relation) {
            $relation->addSelect(['label', 'value', 'ride_preference_id']);
        }])->select(['id', 'title', 'identifier', 'var_type'])->orderBy('title', 'ASC')->get();

        return $preferences;
    }

    public static function loadAllPreferences()
    {
        if ( null === static::$preferences ) {
            static::$preferences = self::orderBy('title', 'ASC')->get()->keyBy('identifier');
        }

        return static::$preferences;
    }

    /**
     * @Relationships
     */
    public function options()
    {
        return $this->hasMany('App\Models\RidePreferenceOption');
    }
}
