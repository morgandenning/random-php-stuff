<?php

namespace OAuth2\Grants;

    class RefreshToken implements Interfaces\GrantType {
        private $refreshToken;

        protected $storage;
        protected $config;

        public function __construct(\OAuth2\Storage\Interfaces\RefreshToken $storage, array $config = []) {
            $this->config = array_merge(['always_issue_new_refresh_token' => false], $config);
            $this->storage = $storage;
        }

        public function getQuerystringIdentifier() {
            return 'refresh_token';
        }

        public function validateRequest(\OAuth2\Request $request, \OAuth2\Response $response) {
            if (!$request->request("refresh_token")) {
                $response->setError(400, 'invalid_request', 'validateRequest');
                return null;
            }

            if (!$refreshToken = $this->storage->getRefreshToken($request->request("refresh_token"))) {
                $response->setError(400, 'invalid_grant', 'Invalid refresh token');
                return null;
            }

            if ($refreshToken['expires'] < time()) {
                $response->setError(400, 'refresh_token_expired', 'Refresh token has expired');
                return null;
            }

            $this->refreshToken = $refreshToken;

            return true;
        }

        public function getClientId() {
            return $this->refreshToken['client_id'];
        }

        public function getScope() {
            return (isset($this->refreshToken['scope']) ? $this->refreshToken['scope'] : null);
        }

        public function getUserId() {
            return (isset($this->refreshToken['user_id']) ? $this->refreshToken['user_id'] : null);
        }

        public function createAccessToken(\OAuth2\Responses\Interfaces\AccessToken $accessToken, $client_id, $user_id, $scope) {
            $issueNewRefreshToken = $this->config['always_issue_new_refresh_token'];
            $token = $accessToken->createAccessToken($client_id, $user_id, $scope, $issueNewRefreshToken);

            if ($issueNewRefreshToken)
                $this->storage->unsetRefreshToken($this->refreshToken['refresh_token']);

            return $token;
        }

    }