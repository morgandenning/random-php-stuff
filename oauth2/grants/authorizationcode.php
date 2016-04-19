<?php

namespace OAuth2\Grants;

    class AuthorizationCode implements Interfaces\GrantType {

        protected $storage;
        protected $authCode;

        public function __construct(\OAuth2\Storage\Interfaces\AuthorizationCode $storage) {
            $this->storage = $storage;
        }

        public function getQuerystringIdentifier() {
            return 'authorization_code';
        }

        public function validateRequest(\OAuth2\Request $request, \OAuth2\Response $response) {
            if (!$request->request("code")) {
                $response->setError(400, 'invalid_request', 'Missing parameter: "code" is required');
                return false;
            }

            $code = $request->request("code");

            if (!$authCode = $this->storage->getAuthorizationCode($code)) {
                $response->setError(400, 'invalid_grant', "Authorization code doesn't exist or is invalid for the client");
                return false;
            }

            if (isset($authCode['redirect_uri']) && $authCode['redirect_uri']) {
                if (!$request->request("redirect_uri") || urldecode($request->request("redirect_uri")) != $authCode['redirect_uri']) {
                    $response->setError(400, 'redirect_uri_mismatch', 'The redirect URI is missing or do not match', "#section-4.1.3");
                    return false;
                }
            }

            if (!isset($authCode['expires']))
                throw new \Exception('Storage must return authcode with a value for "expires"');

            if ($authCode['expires'] < time()) {
                $response->setError(400, 'invalid_grant', 'The authorization code has expired');
                return false;
            }

            if (!isset($authCode['code']))
                $authCode['code'] = $code;

            $this->authCode = $authCode;

            return true;
        }

        public function getClientId() {
            return $this->authCode['client_id'];
        }

        public function getScope() {
            return (isset($this->authCode['scope']) ? $this->authCode['scope'] : null);
        }

        public function getUserId() {
            return (isset($this->authCode['user_id']) ? $this->authCode['user_id'] : null);
        }

        public function createAccessToken(\OAuth2\Responses\Interfaces\AccessToken $accessToken, $client_id, $user_id, $scope) {
            $token = $accessToken->createAccessToken($client_id, $user_id, $scope);
            $this->storage->expireAuthorizationCode($this->authCode['code']);

            return $token;
        }

    }