<?php

namespace OAuth2\Responses;

    class AuthorizationCode implements Interfaces\AuthorizationCode {
        protected $storage;
        protected $config;

        public function __construct(\OAuth2\Storage\Interfaces\AuthorizationCode $storage, array $config = []) {
            $this->storage = $storage;
            $this->config = array_merge([
                'enforce_redirect' => false,
                'auth_code_lifetime' => 30
            ], $config);
        }

        public function getAuthorizeResponse($params, $user_id = null) {
            $result = ['query' => []];
            $params += ['scope' => null, 'state' => null];

            $result['query']['code'] = $this->createAuthorizationCode($params['client_id'], $user_id, $params['redirect_uri'], $params['scope']);

            if (isset($params['state']))
                $result['query']['state'] = $params['state'];

            return [$params['redirect_uri'], $result];
        }

        public function createAuthorizationCode($client_id, $user_id, $redirect_uri, $scope = null) {
            $code = $this->generateAuthorizationCode();
            $this->storage->setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, time() + $this->config['auth_code_lifetime'], $scope);

            return $code;
        }

        public function enforceRedirect() {
            return $this->config['enforce_redirect'];
        }

        protected function generateAuthorizationCode() {
            $tokenLen = 40;
            if (file_exists("/dev/urandom")) {
                $randomData = file_get_contents("/dev/urandom", false, null, 0, 100) . uniqid(mt_rand(), true);
            } else {
                $randomData = mt_rand() . mt_rand() . mt_rand() . mt_rand() . microtime(true) . uniqid(mt_rand(), true);
            }

            return substr(hash("sha512", $randomData), 0, $tokenLen);
        }
    }