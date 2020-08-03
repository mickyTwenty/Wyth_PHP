<?php

namespace App\Classes\PHPFireStore;

use App\Classes\FireStoreHandler;

class FireStoreReference
{
    private $data;

    public function __construct($data='')
    {
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
        $value =
            'projects/' .
            FireStoreHandler::getApiClient()->getConfig('project') .
            '/databases/' .
            FireStoreHandler::getApiClient()->getConfig('database') .
            '/documents/' .
            $this->getData();

        return $value;
    }
}
