<?php

namespace OAuth2\Controller;

    class Resource implements Interfaces\Resource {

        private $token;

        protected $tokenType;
        protected $tokenStorage;
        protected $config;
        protected $scopeUtil;

        public function __construct(\OAuth2\Tokens\Interfaces\TokenType $tokenType, \OAuth2\Storage\Interfaces\AccessToken $tokenStorage, $config = [], $scopeUtil = null) {
            $this->tokenType = $tokenType;
            $this->tokenStorage = $tokenStorage;

            $this->config = array_merge([
                'www_realm' => 'Service'
            ], $config);

            $this->scopeUtil = (is_null($scopeUtil) ? new \OAuth2\Scope : $scopeUtil);
        }

        public function verifyResourceRequest(\OAuth2\Request $request, \OAuth2\Response $response, $scope = null) {
            $token = $this->getAccessTokenData($request, $response);

            if (is_null($token))
                return false;

            if ($scope && (!isset($token['scope']) || !$token['scope'] || !$this->scopeUtil->checkScope($scope, $token['scope']))) {
                $response->setError(403, 'insufficient_scope', 'Insufficient Privileges for this Request');
                $response->addHttpHeaders([
                    'WWW-Authenticate' => sprintf('%s realm="%s", scope="%s", error="%s", error_description="%s"', $this->tokenType->getTokenType(), $this->config['www_realm'], $scope, $response->getParam("error"), $response->getParam("error_description"))
                ]);

                return false;
            }

            $this->token = $token;

            return (bool)$token;
        }

        public function getAccessTokenData(\OAuth2\Request $request, \OAuth2\Response $response) {
            if ($token_param = $this->tokenType->getAccessTokenParameter($request, $response)) {
                if (!$token = $this->tokenStorage->getAccessToken($token_param)) {
                    $response->setError(401, 'invalid_access_token', 'Invalid Access Token');
                } else if (!isset($token['expires']) || !isset($token['client_id'])) {
                    $response->setError(401, 'malformed_token', 'Malformed Token');
                } else if (time() > $token['expires']) {
                    $response->setError(401, 'access_token_expired', 'Access Token Expired');
                } else {
                    return $token;
                }
            }

            $authHeader = sprintf('%s realm="%s"', $this->tokenType->getTokenType(), $this->config['www_realm']);

            if ($error = $response->getParam("error")) {
                $authHeader = sprintf('%s, error="%s"', $authHeader, $error);
                if ($error_description = $response->getParam("error_description")) {
                    $authHeader = sprintf('%s, error_description="%s"', $authHeader, $error_description);
                }
            }

            $response->addHttpHeaders(['WWW-Authenticate' => $authHeader]);

            return null;
        }

        public function getToken() {
            return $this->token;
        }
    }