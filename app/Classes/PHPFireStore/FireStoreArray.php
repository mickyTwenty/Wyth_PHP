<?php

namespace App\Classes\PHPFireStore;

class FireStoreArray
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
            'values' => [],
        ];

        foreach ($this->data as $data) {
            $document = new FireStoreDocument;
            call_user_func_array([$document, 'set'.ucfirst(FireStoreHelper::getType($data))], ['string', $data]);
            $payload['values'][] = $document->get('string');
        }

        return $payload;
    }
}
