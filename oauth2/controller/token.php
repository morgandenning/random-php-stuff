<?php

namespace OAuth2\Controller;

    class Token implements Interfaces\Token {

        protected $accessToken;
        protected $grantTypes;
        protected $clientAssertionType;
        protected $scopeUtil;

        public function __construct($accessToken, array $grantTypes = [], $clientAssertionType = null, $scopeUtil = null) {
            if (is_null($clientAssertionType)) {
                foreach ($grantTypes as $grantType) {
                    if (!$grantType instanceof ClientAssertionTypeInterface) {
                        throw new \InvalidArgumentException("");
                    }
                }
            }

            $this->clientAssertionType = $clientAssertionType;
            $this->accessToken = $accessToken;

            foreach ($grantTypes as $grantType) {
                $this->addGrantType($grantType);
            }

            $this->scopeUtil = (is_null($scopeUtil) ? new \OAuth2\Scope : $scopeUtil);
        }

        public function handleTokenRequest(\OAuth2\Request $request, \OAuth2\Response $response) {
            if ($token = $this->grantAccessToken($request, $response)) {
                $response->setStatusCode(200);
                $response->addParams($token);
                $response->addHttpHeaders(['Cache-Control' => 'no-store', 'Pragma' => 'no-cache']);
            }
        }

        public function grantAccessToken(\OAuth2\Request $request, \OAuth2\Response $response) {
            if (strtolower($request->server("REQUEST_METHOD")) != 'post') {
                $response->setError(405, 'invalid_request', 'Access Token Request requires POST');
                $response->addHttpHeaders(['Allow' => 'POST']);

                return null;
            }

            if (!$grantTypeIdentifier = $request->request("grant_type")) {
                $response->setError(400, 'invalid_request', '2');
                return null;
            }

            if (!isset($this->grantTypes[$grantTypeIdentifier])) {
                $response->setError(400, 'unsupported_grant_type', sprintf('Grant Type "%s" is not Supported', $grantTypeIdentifier));
                return null;
            }

            $grantType = $this->grantTypes[$grantTypeIdentifier];

            if (!$grantType instanceof ClientAssertionTypeInterface) {
                if (!$this->clientAssertionType->validateRequest($request, $response))
                    return null;
                $clientId = $this->clientAssertionType->getClientId();
            }

            if (!$grantType->validateRequest($request, $response))
                return null;

            if ($grantType instanceof ClientAssertionTypeInterface)
                $clientId = $grantType->getClientId();
            else {
                if (!is_null($storedClientId = $grantType->getClientId()) && $storedClientId != $clientId) {
                    $response->setError(400, 'invalid_grant', 'Invalid Grant');
                    return null;
                }
            }

            $availableScope = $grantType->getScope();
            if (!$requestedScope = $this->scopeUtil->getScopeFromRequest($request)) {
                if (!$availableScope) {
                    if (false === $defaultScope = $this->scopeUtil->getDefaultScope($clientId)) {
                        $response->setError(400, 'invalid_scope', 'Missing Scope Parameter');
                        return null;
                    }
                }
                $requestedScope = $availableScope ? $availableScope : $defaultScope;
            }

            if (($requestedScope && !$this->scopeUtil->scopeExists($requestedScope, $clientId)) || ($availableScope && !$this->scopeUtil->checkScope($requestedScope, $availableScope))) {
                $response->setError(400, 'invalid_scope', 'Scope Invalid');
                return null;
            }

            return $grantType->createAccessToken($this->accessToken, $clientId, $grantType->getUserId(), $requestedScope);
        }

        public function addGrantType($grantType, $identifier = null) {
            if (is_null($identifier) || is_numeric($identifier))
                $identifier = $grantType->getQuerystringIdentifier();

            $this->grantTypes[$identifier] = $grantType;
        }

    }