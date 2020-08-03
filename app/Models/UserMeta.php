<?php

namespace App\Models;

use App\Models\MetaData;

class UserMeta extends MetaData
{
    const GROUPING_DRIVER  = 'driver';
    const GROUPING_PROFILE = 'profile';

    // Possible meta values for user object:
    // canceled_trips, gender, school_name, student_organization, graduation_year, postal_code, birth_date, rating, sync_friends, has_facebook_integrated, driving_license_no, vehicle_id_number, vehicle_type, vehicle_make, vehicle_model, vehicle_year, unread_notifications

    /**
     * @var array
     */
    protected $fillable = ['key', 'value', 'grouping'];

    protected $table = 'user_meta';
}
