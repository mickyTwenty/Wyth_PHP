<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cache;

class CarMake extends Model
{
    protected $fillable = ['label'];

    public static function generateBootMeUpDataCached()
    {
        return Cache::remember('cars', 1440, function (){
            return self::generateBootMeUpData();
        });
    }

    public static function generateBootMeUpData()
    {
        $makes = self::all();
        $result = [];

        foreach ($makes as $make) {
            $result[] = [
                'name' => $make->label,
                'models' => $make->carmodel->pluck('label')->toArray()
            ];
        }

        return $result;
    }

    /**
     * Relationship
     */
    public function carmodel()
    {
        return $this->hasMany(CarModel::class);
    }
}
