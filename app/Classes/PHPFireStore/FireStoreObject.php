<?php

namespace App\Classes\PHPFireStore;

class FireStoreObject
{
    private $data = [];

    public function __construct($data='')
    {
        if ( !empty($data) ) {
            return $this->data = (array) $data;
        }
    }

    public function add($data)
    {
        array_push($this->data, $data);

        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function parseValue()
    {
        $payload = [
            'fields' => [],
        ];

        foreach ($this->data as $key => $data) {
            $document = new FireStoreDocument;
            call_user_func_array([$document, 'set'.ucfirst(FireStoreHelper::getType($data))], ['string', $data]);
            $payload['fields'][$key] = $document->get('string');
        }

        return $payload;
    }
}
