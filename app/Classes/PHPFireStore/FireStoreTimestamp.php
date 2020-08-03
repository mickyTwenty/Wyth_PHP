<?php

namespace App\Classes\PHPFireStore;

use App\Classes\FireStoreHandler;
use Carbon\Carbon;
use DateTime;

class FireStoreTimestamp
{
    private $data;

    public function __construct($data='')
    {
        if ( $data === '' || $data === 'now' ) {
            $data = Carbon::now();
        }

        return $this->data = $data;
    }

    public function setData($data='')
    {
        return $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function parseValue()
    {
        $value = $this->getData();

        if ( $value instanceof DateTime && method_exists($value, 'format') ) {
            return $value->format('Y-m-d\TG:i:s.z\Z');
        }

        return $value;
    }
}
