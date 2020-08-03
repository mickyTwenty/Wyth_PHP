<?php
namespace App\Classes;

use App\Classes\PHPFireStore\FireStoreApiClient;
use App\Classes\PHPFireStore\FireStoreDocument;
use App\Classes\PHPFireStore\FireStoreErrorCodes;
use App\Classes\PHPFireStore\FireStoreHelper;
use Exception;

class FireStoreHandler {

    private static $instance;

    private static $overwriteCollection = false;

    /**
     * Initializer
     */
    public static function init()
    {
        if ( null === self::$instance ) {
            self::$instance = new FireStoreApiClient(env('FIRESTORE_PROJECT_ID'), env('FIRESTORE_API_KEY'), [
                'database' => env('FIRESTORE_DATABASE', '(default)'),
            ]);
        }

        return new static;
    }

    public static function getApiClient()
    {
        return self::$instance;
    }

    public static function addDocument($collectionName, $newDocumentId=null, $payload, $params=[])
    {
        self::init();

        if ( !($payload instanceof FireStoreDocument) ) {
            $document = new FireStoreDocument();
        } else {
            $document = $payload;
        }

        // Set document id
        if ( $newDocumentId ) {
            $params['documentId'] = $newDocumentId;
        }

        if ( is_array($payload) ) {
            foreach ($payload as $key => $value) {
                call_user_func_array([$document, 'set'.ucfirst(FireStoreHelper::getType($value))], [$key, $value]);
            }
        }

        return call_user_func_array(array(self::$instance, __FUNCTION__), [
            $collectionName,
            $document,
            $params
        ]);
    }

    public static function updateDocument($collectionName, $documentId, $payload, $documentExists=null, array $params=[])
    {
        self::init();

        /*if ( false === self::$overwriteCollection ) {
            try {
                $document = self::getDocument($collectionName, $documentId);
            } catch (Exception $e) {
                if ( $e->getCode() == FireStoreErrorCodes::DOCUMENT_NOT_FOUND ) {
                    $document = new FireStoreDocument();
                } else {
                    throw $e;
                }
            }
        } else {
            if ( !($payload instanceof FireStoreDocument) ) {
                $document = new FireStoreDocument();
            } else {
                $document = $payload;
            }
        }*/

        $document = new FireStoreDocument();

        if ( is_array($payload) ) {
            foreach ($payload as $key => $value) {
                call_user_func_array([$document, 'set'.ucfirst(FireStoreHelper::getType($value))], [$key, $value]);
            }
        }

        return call_user_func_array(array(self::$instance, __FUNCTION__), [
            $collectionName,
            $documentId,
            $document,
            $documentExists,
            $params
        ]);
    }

    public static function setDocument($collectionName, $documentId, $payload, array $params=[])
    {
        self::init();

        $document = new FireStoreDocument();

        if ( is_array($payload) ) {
            foreach ($payload as $key => $value) {
                call_user_func_array([$document, 'set'.ucfirst(FireStoreHelper::getType($value))], [$key, $value]);
            }
        }

        return call_user_func_array(array(self::$instance, __FUNCTION__), [
            $collectionName,
            $documentId,
            $document,
            $params
        ]);
    }

    public static function overwriteCollection($overwrite)
    {
        if ( !is_bool($overwrite) ) {
            throw new Exception('Expecting boolean value.');
        }

        self::$overwriteCollection = $overwrite;

        return new static;
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
