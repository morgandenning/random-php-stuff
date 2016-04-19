<?php

namespace OAuth2\Controller;

    class Authorize implements Interfaces\Authorize {

        private $scope;
        private $state;
        private $client_id;
        private $redirect_uri;
        private $response_type;

        protected $clientStorage;
        protected $responseTypes;
        protected $config;
        protected $scopeUtil;

        public function __construct($clientStorage, array $responseTypes = [], array $config = [], $scopeUtil = null) {
            $this->clientStorage = $clientStorage;
            $this->responseTypes = $responseTypes;
            $this->config = array_merge([
                'allow_implicit' => false,
                'enforce_state' => true,
                'require_exact_redirect_uri' => true,
                'redirect_status_code' => 302
            ], $config);

            $this->scopeUtil = (is_null($scopeUtil) ? new \OAuth2\Scope : $scopeUtil);
        }

        public function handleAuthorizeRequest(\OAuth2\Request $request, \OAuth2\Response $response, $is_authorized = false, $user_id = null) {
            if (!is_bool($is_authorized))
                throw new \InvalidArgumentException("");

            if (!$this->validateAuthorizeRequest($request, $response))
                return;

            if (empty($this->redirect_uri)) {
                $clientData = $this->clientStorage->getClientDetails($this->client_id);
                $registered_redirect_uri = $clientData['redirect_uri'];
            }

            if ($is_authorized === false) {
                $redirect_uri = $this->redirect_uri ?: $registered_redirect_uri;
                $response->setRedirect($this->config['redirect_status_code'], $redirect_uri, $this->state, 'access_denied', "The user denied access to your application");

                return;
            }

            $params = [
                'scope' => $this->scope,
                'state' => $this->state,
                'client_id' => $this->client_id,
                'redirect_uri' => $this->redirect_uri,
                'response_type' => $this->response_type
            ];

            $authResult = $this->responseTypes[$this->response_type]->getAuthorizeResponse($params, $user_id);

            list($redirect_uri, $uri_params) = $authResult;

            if (empty($redirect_uri) && !empty($registered_redirect_uri))
                $redirect_uri = $registered_redirect_uri;

            $uri = $this->buildUri($redirect_uri, $uri_params);

            $response->setRedirect($this->config['redirect_status_code'], $uri);
        }

        public function validateAuthorizeRequest(\OAuth2\Request $request, \OAuth2\Response $response) {
            if (!$client_id = $request->request("client_id")) {
                $response->setError(400, 'invalid_client', 'Missing Client ID');
                return false;
            }

            if (!$clientData = $this->clientStorage->getClientDetails($client_id)) {
                $response->setError(400, 'invalid_client', 'Invalid Client ID');
                return false;
            }

            $registered_redirect_uri = isset($clientData['redirect_uri']) ? $clientData['redirect_uri'] : '';

            if ($supplied_redirect_uri = $request->query("redirect_uri")) {
                $parts = parse_url($supplied_redirect_uri);
                if (isset($parts['fragment']) && $parts['fragment']) {
                    $response->setError(400, 'invalid_uri', 'Wrong URI Structure');
                    return false;
                }

                if ($registered_redirect_uri && !$this->validateRedirectUri($supplied_redirect_uri, $registered_redirect_uri)) {
                    $response->setError(400, 'redirect_uri_mismatch', 'Redirect URI Mismatch');
                    return false;
                }

                $redirect_uri = $supplied_redirect_uri;
            } else {
                if (!$registered_redirect_uri) {
                    $response->setError(400, 'invalid_uri');
                    return false;
                }

                if (count(explode(' ', $registered_redirect_uri)) > 1) {
                    $response->setError(400, 'invalid_uri');
                    return false;
                }

                $redirect_uri = $registered_redirect_uri;
            }

            $response_type = $request->request("response_type");
            $state = $request->request("state");

            if (!$scope = $this->scopeUtil->getScopeFromRequest($request)) {
                $scope = $this->scopeUtil->getDefaultScope($client_id);
            }

            if (!$response_type || !in_array($response_type, [self::RESPONSE_TYPE_AUTHORIZATION_CODE, self::RESPONSE_TYPE_ACCESS_TOKEN])) {
                $response->setRedirect($this->config['redirect_status_code'], $redirect_uri, $state, 'invalid_request', '', null);
                return false;
            }

            if ($response_type == self::RESPONSE_TYPE_AUTHORIZATION_CODE) {
                if (!isset($this->responseTypes['code'])) {
                    $response->setRedirect($this->config['redirect_status_code'], $redirect_uri, $state, 'unsupported_response_type', '', null);
                    return false;
                }

                if (!$this->clientStorage->checkRestrictedGrantType($client_id, 'authorization_code')) {
                    $response->setRedirect($this->config['redirect_status_code'], $redirect_uri, $state, 'unauthorized_client', '', null);
                    return false;
                }

                if ($this->responseTypes['code']->enforceRedirect() && !$redirect_uri) {
                    $response->setError(400, 'redirect_uri_mismatch', '');
                    return false;
                }
            }

            if ($response_type == self::RESPONSE_TYPE_ACCESS_TOKEN) {
                if (!$this->config['allow_implicit']) {
                    $response->setRedirect($this->config['redirect_status_code'], $redirect_uri, $state, 'unsupported_response_type', '', null);
                    return false;
                }

                if (!$this->clientStorage->checkRestrictedGrantType($client_id, 'implicit')) {
                    $response->setRedirect($this->config['redirect_status_code'], $redirect_uri, $state, 'unauthorized_client', '', null);
                    return false;
                }
            }

            if (false === $scope) {
                $response->setRedirect($this->config['redirect_status_code'], $redirect_uri, $state, 'invalid_client', '', null);
                return false;
            }

            if (!is_null($scope) && !$this->scopeUtil->scopeExists($scope, $client_id)) {
                $response->setRedirect($this->config['redirect_status_code'], $redirect_uri, $state, 'invalid_scope', '', null);
                return false;
            }

            if ($this->config['enforce_state'] && !$state) {
                $response->setRedirect($this->config['redirect_status_code'], $redirect_uri, null, 'invalid_request', '');
                return false;
            }

            $this->scope = $scope;
            $this->state = $state;
            $this->client_id = $client_id;
            $this->redirect_uri = $supplied_redirect_uri;
            $this->response_type = $response_type;

            return true;
        }

        private function buildUri($uri, $params) {
            $parse_url = parse_url($uri);

            foreach ($params as $k => $v) {
                if (isset($parse_url[$k])) {
                    $parse_url[$k] .= "&" . http_build_query($v);
                } else {
                    $parse_url[$k] = http_build_query($v);
                }
            }

            return
                ((isset($parse_url["scheme"])) ? $parse_url["scheme"] . "://" : "")
                . ((isset($parse_url["user"])) ? $parse_url["user"]
                . ((isset($parse_url["pass"])) ? ":" . $parse_url["pass"] : "") . "@" : "")
                . ((isset($parse_url["host"])) ? $parse_url["host"] : "")
                . ((isset($parse_url["port"])) ? ":" . $parse_url["port"] : "")
                . ((isset($parse_url["path"])) ? $parse_url["path"] : "")
                . ((isset($parse_url["query"]) && !empty($parse_url['query'])) ? "?" . $parse_url["query"] : "")
                . ((isset($parse_url["fragment"])) ? "#" . $parse_url["fragment"] : "");
        }

        private function validateRedirectUri($inputUri, $registeredUriString) {
            if (!$inputUri || !$registeredUriString) {
                return false;
            }

            $registered_uris = explode(' ', $registeredUriString);

            foreach ($registered_uris as $registered_uri) {
                if ($this->config['require_exact_redirect_uri']) {
                    if (strcmp($inputUri, $registered_uri) === 0)
                        return true;
                } else {
                    if (strcasecmp(substr($inputUri, 0, strlen($registered_uri)), $registered_uri) === 0)
                        return true;
                }
            }
            return false;
        }

        public function getScope() {
            return $this->scope;
        }

        public function getState() {
            return $this->state;
        }

        public function getClientId() {
            return $this->client_id;
        }

        public function getRedirectUri() {
            return $this->redirect_uri;
        }

        public function getResponseType() {
            return $this->response_type;
        }
    }