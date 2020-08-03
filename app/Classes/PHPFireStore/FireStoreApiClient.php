<?php

namespace App\Classes\PHPFireStore;

use App\Classes\PHPFireStore\FireStoreDocument;
use App\Classes\PHPFireStore\FireStoreErrorCodes;
use App\Classes\PHPFireStore\FireStoreHelper;

class FireStoreApiClient {

    private $apiRoot = 'https://firestore.googleapis.com/v1beta1/';
    private $project;
    private $apiKey;
    private $config;

    function __construct($project, $apiKey, $config=array()) {
        $this->project = $project;
        $this->apiKey = $apiKey;
        $this->config = array_merge($config, [
            'database' => '(default)',
            'project' => $project,
        ]);
    }

    public function getConfig($key)
    {
        return array_key_exists($key, $this->config) ? $this->config[$key] : null;
    }

    private function constructUrl($method, $params=null) {
        $params     = is_array($params) ? $params : [];
        $builtQuery = ( count($params) ? '&' . http_build_query($params) : '');

        if ( array_key_exists('updateMask.fieldPaths', $params) ) {
            $builtQuery = preg_replace('/%5B\d%5D/', '', $builtQuery);
        }

        return (
            $this->apiRoot . 'projects/' . $this->project . '/' .
            'databases/(default)/' . $method . '?key=' . $this->apiKey . $builtQuery
        );
    }

    private function get($method, $params=null) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $this->constructUrl($method, $params),
            CURLOPT_USERAGENT => 'cURL'
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    private function post($method, $params, $postBody) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $this->constructUrl($method, $params),
            CURLOPT_HTTPHEADER => array('Content-Type: application/json','Content-Length: ' . strlen($postBody)),
            CURLOPT_USERAGENT => 'cURL',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postBody
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    private function put($method, $params, $postBody) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_HTTPHEADER => array('Content-Type: application/json','Content-Length: ' . strlen($postBody)),
            CURLOPT_URL => $this->constructUrl($method, $params),
            CURLOPT_USERAGENT => 'cURL',
            CURLOPT_POSTFIELDS => $postBody
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    private function patch($method, $params, $postBody) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_HTTPHEADER => array('Content-Type: application/json','Content-Length: ' . strlen($postBody)),
            CURLOPT_URL => $this->constructUrl($method, $params),
            CURLOPT_USERAGENT => 'cURL',
            CURLOPT_POSTFIELDS => $postBody
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    private function delete($method, $params) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_URL => $this->constructUrl($method, $params),
            CURLOPT_USERAGENT => 'cURL'
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function getDocument($collectionName, $documentId) {
        if ($response = $this->get("documents/$collectionName/$documentId")) {
            $object = FireStoreHelper::decode($response);

            if ( FireStoreDocument::isValidDocument($object) ) {
                return new FireStoreDocument($object);
            } else {
                throw new \Exception('Document does not exist.', FireStoreErrorCodes::DOCUMENT_NOT_FOUND);
            }
        }

        throw new \Exception('Error while parsing response from FireStore', FireStoreErrorCodes::UNABLE_TO_RESOLVE_REQUEST);
    }

    public function batchGet(array $documents, $params=[])
    {
        $payload = [];
        foreach ($documents as $document) {
            $payload[] = 'projects/' . $this->project . '/' . 'databases/(default)/documents/' . $document;
        }

        $response = $this->post(
            'documents:batchGet',
            $params,
            FireStoreHelper::encode([
                'documents' => $payload
            ])
        );

        $result = [];
        foreach (FireStoreHelper::decode($response) as $document) {
            $result[] = new FireStoreDocument($document['found']);
        }

        return $result;
    }

    public function setDocument($collectionName, $documentId, $document, array $params=[]) {
        return $this->patch(
            "documents/$collectionName/$documentId",
            $params,
            $document->toJson()
        );
    }

    public function updateDocument($collectionName, $documentId, $document, $documentExists=null, array $params=[]) {
        if ($documentExists !== null) {
            $params['currentDocument.exists'] = !!$documentExists;
        }
        $params['updateMask.fieldPaths'] = array_unique(array_keys($document->toArray()));

        return $this->patch(
            "documents/$collectionName/$documentId",
            $params,
            $document->toJson()
        );
    }

    public function deleteDocument($collectionName, $documentId) {
        return $this->delete(
            "documents/$collectionName/$documentId", []
        );
    }

    public function addDocument($collectionName, $document, $params=[]) {
        return $this->post(
            "documents/$collectionName",
            $params,
            $document->toJson()
        );
    }

}
