<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserVerification extends Model
{
    public $table = 'user_verification';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'facebook_url', 'twitter_url', 'instagram_url', 'website_url', 'information',
    ];

    public function getIsApprovedTextAttribute()
    {
        switch ($this->attributes['is_approved']) {
            case 1:
                return 'Approved';
                break;
            case -1:
                return 'Rejected';
                break;
            default:
                return 'Pending';
                break;
        }
    }

    public function getIsApprovedTextFormattedAttribute()
    {
        switch ($this->attributes['is_approved']) {
            case 1:
                return '<span class="label label-success">'.$this->is_approved_text.'</span>';
                break;
            case -1:
                return '<span class="label label-danger">'.$this->is_approved_text.'</span>';
                break;
            default:
                return '<span class="label label-default">'.$this->is_approved_text.'</span>';
                break;
        }
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
