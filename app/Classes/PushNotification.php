<?php

namespace App\Classes;

use GuzzleHttp\Client;
use Benwilkins\FCM\FcmMessage;

/**
 * @author Ahsaan Muhammad Yousuf <ahsankhatri1992@gmail.com>
 *
 * A wrapper to send two push notification to condition based to handle data push
 * because fcm don't allow us to send different payload on each platform, that's
 * why i had to handle separately.
 */

class PushNotification
{
    // Default attributes will be used explicited only.
    const DEFAULT_PRIORITY  = 'normal';
    const CONTENT_AVAILABLE = false;

    /**
     * @const The API URL for Firebase
     */
    const API_URI = 'https://fcm.googleapis.com/fcm/send';

    private $_config;
    private $_payload;
    private $_attributes;

    /**
     * @var Client
     */
    private $client;

    public function __construct()
    {
        $this->bootstrap();
    }

    private function bootstrap()
    {
        $this->_attributes = [
            'topic_platforms'   => [
                'ios'     => 'ios1',
                'android' => 'android1',
            ],
            'priority'          => self::DEFAULT_PRIORITY,
            'content_available' => self::CONTENT_AVAILABLE,
        ];
    }

    public function generateCondition($userId, $platform, $topic)
    {
        // Default condition
        $condition = "'user_".$userId."' in topics && '".$topic."' in topics";

        $conditionMethod = 'generateConditionFor' . ucfirst(strtolower($platform));

        if ( method_exists($this, $conditionMethod) ) {
            $condition = $this->$conditionMethod($userId, $topic);
        }

        return $condition;
    }

    /**
     * Customized condition only for Android
     *
     * @param  array $payload
     * @return array
     */
    private function generatePayloadForAndroid($payload)
    {
        if ( array_key_exists('content', $payload) ) {
            foreach ($payload['content'] as $key => $value) {
                $payload['data']['data_'.$key] = $value;
            }
        }

        unset($payload['content']);

        return $payload;
    }

    /**
     * Generate payload if desired platform is not defined via method
     *
     * @param  array $payload
     * @return array
     */
    public function generatePayload($payload, $platform, $topic)
    {
        $payloadMethod = 'generatePayloadFor' . ucfirst(strtolower($platform));

        if ( method_exists($this, $payloadMethod) ) {
            $payload = $this->$payloadMethod($payload);
        }

        return $payload;
    }

    public static function sendToUserConditionally($userId, array $payload)
    {
        $static = (new static);

        foreach ($static->getAttribute('topic_platforms') as $platform => $topic) {
            $condition = $static->generateCondition($userId, $platform, $topic);
            $payload   = $static->generatePayload($payload, $platform, $topic);

            $static->sendToCondition( $condition, $payload );
        }
    }

    public static function sendToDevice($device, array $payload)
    {
        $static = new static;

        $fcm = (new FcmMessage())
            ->to( $device );

        if ( array_key_exists('content', $payload) ) {
            $fcm->content( $payload['content'] );
        }

        if ( array_key_exists('data', $payload) ) {
            $fcm->data( $payload['data'] );
        }

        $body = $fcm->formatData();

        return $static->send($body);
    }

    public function sendToCondition($condition, array $payload)
    {
        $fcm = (new FcmMessage())
            ->condition( $condition );

        if ( array_key_exists('content', $payload) ) {
            $fcm->content( $payload['content'] );
        }

        if ( array_key_exists('data', $payload) ) {
            $fcm->data( $payload['data'] );
        }

        $body = $fcm->formatData();

        return $this->send($body);
    }

    public function send($body)
    {
        $payload = [
            'headers' => [
                'Authorization' => 'key=' . $this->getApiKey(),
                'Content-Type'  => 'application/json',
            ],
            'body' => $body,
        ];

        return (new Client)->post(self::API_URI, $payload);
    }

    private function getAttribute($key)
    {
        if ( !array_key_exists($key, $this->_attributes) ) {
            throw new Exception('Container does not have ' . $key . ' attribute');
        }

        return $this->_attributes[$key];
    }

    public function setPayload()
    {
        $this->$_payload;
    }

    public function getPayload()
    {
        return $this->_payload;
    }

    /**
     * @return string
     */
    private function getApiKey()
    {
        return config('services.fcm.key');
    }
}
