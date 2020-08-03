<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripActivity extends Model
{
    public $table = 'trip_activity';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status',
    ];

    public function setUpdatedAt($value)
    {
        return $this;
    }

    public function getUpdatedAtColumn()
    {
        return null;
    }
}
