<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFacebook extends Model
{
    public $table = 'user_facebook';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'facebook_uid', 'access_token'
    ];

    public function setUpdatedAt($value)
    {
        return $this;
    }

    public function getUpdatedAtColumn()
    {
        return null;
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
