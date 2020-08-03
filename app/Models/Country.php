<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    const DEFAULT_COUNTRY_ID = 231;
    
    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'countries';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shortname', 'name',
    ];
}
