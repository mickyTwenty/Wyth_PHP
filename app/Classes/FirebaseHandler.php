<?php
namespace App\Classes;

class FirebaseHandler {

    private static $instance;

    /**
     * Initializer
     */
    public static function init()
    {
        if ( null === self::$instance ) {
            self::$instance = new \Firebase\FirebaseLib(env('FIREBASE_DATABASE_URL'), env('FIREBASE_SECRET_TOKEN'));
        }

        return new static;
    }

    public static function get()
    {
        self::init();

        $payload = call_user_func_array(array(self::$instance, 'get'), func_get_args());

        return $payload === 'null' ? null : json_decode($payload);
    }

    /**
     * Proxy method to call parent firebase class method dependently
     *
     * @usage: FirebaseHandler::set('/user/name', 'firebase');
     *
     * @param  string $method    Firebase parent class method
     * @param  string $arguments Firebase parent class method's argument
     *
     * @return mixed
     */
    public static function __callStatic($method, $arguments)
    {
        self::init();

        return call_user_func_array(array(self::$instance, $method), $arguments);
    }
}
