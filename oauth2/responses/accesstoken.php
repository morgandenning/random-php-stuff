<?php

namespace OAuth2\Responses;

    class AccessToken implements Interfaces\AccessToken {
        protected $tokenStorage;
        protected $refreshStorage;
        protected $config;

        public function __construct(\OAuth2\Storage\Interfaces\AccessToken $tokenStorage, \OAuth2\Storage\Interfaces\RefreshToken $refreshStorage = null, array $config = []) {
            $this->tokenStorage = $tokenStorage;
            $this->refreshStorage = $refreshStorage;

            $this->config = array_merge([
                'token_type' => 'bearer',
                'access_lifetime' => 3600,
                'refresh_token_lifetime' => 1209600
            ], $config);
        }

        public function getAuthorizeResponse($params, $user_id = null) {
            $result = ['query' => []];
            $params += ['scope' => null, 'state' => null];

            $result['fragment'] = $this->createAccessToken($params['client_id'], $user_id, $params['scope'], false);

            if (isset($params['state']))
                $result['fragment']['state'] = $params['state'];

            return [$params['redirect_uri'], $result];
        }

        public function createAccessToken($client_id, $user_id, $scope = null, $includeRefreshToken = true) {
            $token = [
                'access_token' => $this->generateAccessToken(),
                'expires_in' => $this->config['access_lifetime'],
                'token_type' => $this->config['token_type'],
                'scope' => $scope
            ];

            $this->tokenStorage->setAccessToken($token['access_token'], $client_id, $user_id, $this->config['access_lifetime'] ? time() + $this->config['access_lifetime'] : null, $scope);

            if ($includeRefreshToken && $this->refreshStorage) {
                $token['refresh_token'] = $this->generateRefreshToken();
                $this->refreshStorage->setRefreshToken($token['refresh_token'], $client_id, $user_id, time() + $this->config['refresh_token_lifetime'], $scope);
            }

            return $token;
        }

        protected function generateAccessToken() {
            $tokenLen = 40;
            if (file_exists("/dev/urandom")) {
                $randomData = file_get_contents("/dev/urandom", false, null, 0, 100) . uniqid(mt_rand(), true);
            } else {
                $randomData = mt_rand() . mt_rand() . mt_rand() . mt_rand() . microtime(true) . uniqid(mt_rand(), true);
            }

            return substr(hash("sha512", $randomData), 0, $tokenLen);
        }

        protected function generateRefreshToken() {
            return $this->generateAccessToken();
        }
    }