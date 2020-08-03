<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

class School extends Model
{
    use Eloquence;

    protected $fillable          = ['name', 'city_id'];
    protected $searchableColumns = ['name'];

    public static function returnSchoolsForBootMeUp()
    {
        return self::with('city.state')->get()->pluckMultiple([
            'name',
            'city.name',
            'city.state.name',
            // 'city.state.country.name',
        ], [
            'name'            => 'school',
            'city.name'       => 'city',
            'city.state.name' => 'state',
            // 'city.state.country.name' => 'country',
        ]);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
