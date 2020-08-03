<?php

namespace App\Classes;

use GuzzleHttp\Client;

class Facebook {

    private static $errorMessage;

    public static function resolveByToken($access_token)
    {
        $client = new Client([
            'base_uri' => "https://graph.facebook.com",
            'verify' => false
        ]);

        try {
            $result = $client->request('GET', 'me?access_token='.$access_token);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            self::setError($e->getResponse()->getBody()->getContents());
            return false;
        }

        $body = $result->getBody()->getContents();

        return json_decode($body);
    }

    public static function getError()
    {
        return self::$errorMessage;
    }

    public static function setError($message)
    {
        return self::$errorMessage = $message;
    }
}
