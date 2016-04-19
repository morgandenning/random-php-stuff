<?php

namespace OAuth2\Assertions;

    class HttpBasic implements Interfaces\ClientAssertionType {
        private $clientData;

        protected $storage;
        protected $config;

        public function __construct(\OAuth2\Storage\Interfaces\ClientCredentials $storage, array $config = []) {
            $this->storage = $storage;
            $this->config = array_merge([
                'allow_credentials_in_request_body' => true
            ], $config);
        }

        public function validateRequest(\OAuth2\Request $request, \OAuth2\Response $response) {
            if (!$clientData = $this->getClientCredentials($request, $response)) {
                return false;
            }

            if (!isset($clientData['client_id']) || !isset($clientData['client_secret']))
                throw new \LogicException("");

            if ($this->storage->checkClientCredentials($clientData['client_id'], $clientData['client_secret']) === false) {
                $response->setError(400, 'invalid_client', '1');
                return false;
            }

            if (!$this->storage->checkRestrictedGrantType($clientData['client_id'], $request->request("grant_type"))) {
                $response->setError(400, 'unauthorized_client', '');
                return false;
            }

            $this->clientData = $clientData;
            return true;
        }

        public function getClientId() {
            return $this->clientData['client_id'];
        }

        public function getClientCredentials(\OAuth2\Request $request, \OAuth2\Response $response) {
            if (!is_null($request->headers("PHP_AUTH_USER")) && !is_null($request->headers("PHP_AUTH_PW"))) {
                return array("client_id" => $request->headers("PHP_AUTH_USER"), 'client_secret' => $request->headers("PHP_AUTH_PW"));
            }

            if ($this->config['allow_credentials_in_request_body']) {
                if (!is_null($request->request("client_id"))) {
                    return array("client_id" => $request->request("client_id"), "client_secret" => $request->request("client_secret", ""));
                }
            }

            if ($response)
                $response->setError(400, "invalid_client", '2');

            return null;
        }
    }