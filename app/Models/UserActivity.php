<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{
    public $table = 'user_activity';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'event_key', 'event_data', 'is_encoded'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_encoded' => 'boolean',
    ];

    public function setUpdatedAt($value)
    {
        return $this;
    }

    public function getUpdatedAtColumn()
    {
        return null;
    }

    public function exists()
    {
        try {
            return $this->{$this->getKeyName()} ? true : false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getValue($default = null)
    {
        return $this->exists() ?
            ($this->is_encoded ? json_decode($this->event_data) : $this->event_data) :
            $default;
    }

    /**
     * Helper to check if activity has that certain value
     *
     * @param  mixed  $value
     *
     * @return boolean
     */
    public function hasValue($value)
    {
        return $this->exists() ?
            (
                $this->is_encoded ?
                (count(array_diff($value, json_decode($this->event_data))) === 0) :
                (strcasecmp($value, $this->event_data) === 0)
            ) :
            false;
    }
}
