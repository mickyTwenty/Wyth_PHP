<?php

namespace App\Classes\PHPFireStore;

use App\Classes\PHPFireStore\FireStoreArray;
use App\Classes\PHPFireStore\FireStoreDeleteAttribute;
use App\Classes\PHPFireStore\FireStoreReference;

class FireStoreHelper
{

    public static function decode($value)
    {
        return json_decode($value, true, 16);
    }

    public static function encode($value)
    {
        return json_encode($value);
    }

    /**
     * Filter will filter out those values which is not needed to send to server
     *
     * @param  array $value
     * @return array
     */
    public static function filter($value)
    {
        return array_filter($value, function($v) {
            return in_array(self::getType($v), ['delete']) ? false : true;
        });
    }

    public static function getType($value)
    {
        $type = gettype($value);

        if ( $type === 'object' ) {
            if ( $value instanceof FireStoreReference ) {
                return 'reference';
            }

            if ( $value instanceof FireStoreTimestamp ) {
                return 'timestamp';
            }

            if ( $value instanceof FireStoreArray ) {
                return 'array';
            }

            if ( $value instanceof FireStoreDeleteAttribute ) {
                return 'delete';
            }
        }

        return $type;
    }

}
