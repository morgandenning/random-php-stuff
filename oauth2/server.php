<?php

    ##

namespace OAuth2;

    use OAuth2\Controller\Interfaces\Authorize;
    use OAuth2\Controller\Interfaces\Resource;
    use OAuth2\Controller\Interfaces\Token;

    class Server implements Controller\Interfaces\Resource, Controller\Interfaces\Authorize, Controller\Interfaces\Token {

        protected $response;
        protected $config = [];
        protected $storages = [];

        protected $authorizeController;
        protected $tokenController;
        protected $resourceController;

        protected $grantTypes;
        protected $responseTypes;
        protected $tokenType;
        protected $scopeUtil;
        protected $clientAssertionType;

        protected $storageMap = [
            'access_token' => 'OAuth2\Storage\Interfaces\AccessToken',
            'authorization_code' => 'OAuth2\Storage\Interfaces\AuthorizationCode',
            'client_credentials' => 'OAuth2\Storage\Interfaces\ClientCredentials',
            'client' => 'OAuth2\Storage\Interfaces\Client',
            'refresh_token' => 'OAuth2\Storage\Interfaces\RefreshToken',
            'scope' => 'OAuth2\Storage\Interfaces\Scope'
        ];

        protected $responseTypeMap = [
            'token' => 'OAuth2\ResponseType\Interfaces\AccessToken',
            'code' => 'OAuth2\ResponseType\Interfaces\AuthorizationCode'
        ];

        public function __construct($oStorage = [], array $aConfig = [], array $grantTypes = [], array $responseTypes = [], TokenType $tokenType = null, Scope $scopeUtil = null, ClientAssertion $clientAssertionType = null) {
            $storage = is_array($oStorage) ? $oStorage : array($oStorage);
            $this->storages = [];

            foreach ($storage as $k => $v) {
                $this->addStorage($v, $k);
            }

            $this->config = array_merge([
                'access_lifetime' => 3600,
                'www_realm' => 'Service',
                'token_param_name' => 'access_token',
                'token_bearer_header_name' => 'Bearer',
                'enforce_state' => true,
                'require_exact_redirect_uri' => true,
                'allow_implicit' => false,
                'allow_credentials_in_request_body' => true
            ], $aConfig);

            foreach ($grantTypes as $k => $v) {
                $this->addGrantType($v, $k);
            }
            foreach ($responseTypes as $k => $v) {
                $this->addResponseType($v, $k);
            }

            $this->tokenType = $tokenType;
            $this->scopeUtil = $scopeUtil;
            $this->clientAssertionType = $clientAssertionType;
        }

        public function getAuthorizeController() {
            if (is_null($this->authorizeController))
                $this->authorizeController = $this->createDefaultAuthorizeController();

            return $this->authorizeController;
        }

        public function getTokenController() {
            if (is_null($this->tokenController))
                $this->tokenController = $this->createDefaultTokenController();

            return $this->tokenController;
        }

        public function getResourceController() {
            if (is_null($this->resourceController))
                $this->resourceController = $this->createDefaultResourceController();

            return $this->resourceController;
        }

        public function setAuthorizeController(Controller\Authorize $authorizeController) {
            $this->authorizeController = $authorizeController;
        }

        public function setTokenController(Controller\Token $tokenController) {
            $this->tokenController = $tokenController;
        }

        public function setResourceController(Controller\Resource $resourceController) {
            $this->resourceController = $resourceController;
        }


        public function handleTokenRequest(Request $request, Response $response = null) {
            $this->response = is_null($response) ? new Response() : $response;
            $this->getTokenController()->handleTokenRequest($request, $this->response);

            return $this->response;
        }

        public function grantAccessToken(Request $request, Response $response = null) {
            $this->response = is_null($response) ? new Response() : $ersponse;
            return $this->getTokenController->grantAccessToken($request, $this->response);
        }

        public function handleAuthorizeRequest(Request $request, Response $response, $is_authorized = false, $user_id = null) {
            $this->response = $response;
            $this->getAuthorizeController()->handleAuthorizeRequest($request, $this->response, $is_authorized, $user_id);

            return $this->response;
        }

        public function validateAuthorizeRequest(Request $request, Response $response = null) {
            $this->response = is_null($response) ? new Response() : $response;
            return $this->getAuthorizeController()->validateAuthorizeRequest($request, $this->response);
        }

        public function verifyResourceRequest(Request $request, Response $response = null, $scope = null) {
            $this->response = is_null($response) ? new Response() : $response;
            return $this->getResourceController()->verifyResourceRequest($request, $this->response, $scope);
        }

        public function getAccessTokenData(Request $request, Response $response = null) {
            $this->response = is_null($response) ? new Response() : $response;
            return $this->getResourceController()->getAccessTokenData($request, $this->response);
        }

        public function addGrantType(Grants\Interfaces\GrantType $grantType, $key = null) {
            if (is_string($key))
                $this->grantTypes[$key] = $grantType;
            else
                $this->grantTypes[] = $grantType;

            if (!is_null($this->tokenController))
                $this->getTokenController()->addGrantType($grantType);
        }

        public function addStorage($storage, $key = null) {
            if (isset($this->storageMap[$key])) {
                if (!$sstorage instanceof $this->storageMap[$key]) {
                    throw new \InvalidArgumentException("");
                }
                $this->storages[$key] = $storage;
            } else if (!is_null($key) && !is_numeric($key)) {
                throw new \InvalidArgumentException("");
            } else {
                $set = false;
                foreach ($this->storageMap as $type => $interface) {
                    if ($storage instanceof $interface) {
                        $this->storages[$type] = $storage;
                        $set = true;
                    }
                }

                if (!$set)
                    throw new \InvalidArgumentException(sprintf('storage of class "%s" must implement one of [%s]', get_class($storage), implode(', ', $this->storageMap)));
            }
        }

        public function addResponseType(ResponseType $responseType, $key = null) {
            if (isset($this->responseTypeMap[$key])) {
                if (!$responseType instanceof $this->responseTypeMap[$key])
                    throw new \InvalidArgumentException("");

                $this->responseTypes[$key] = $responseType;
            } else if (!is_null($key) && !is_numeric($key)) {
                throw new \InvalidArgumentException("");
            } else {
                $set = false;
                foreach ($this->responseTypeMap as $type => $interface) {
                    if ($responseType instanceof $interface) {
                        $this->responseTypes[$type] = $responseType;
                        $set = true;
                    }
                }

                if (!$set)
                    throw new \InvalidArgumentException("");
            }
        }

        public function getScopeUtil() {
            if (!$this->scopeUtil) {
                $storage = isset($this->storages['scope']) ? $this->storages['scope'] : null;
                $this->scopeUtil = new Scope($storage);
            }

            return $this->scopeUtil;
        }

        public function setScopeUtil($scopeUtil) {
            $this->scopeUtil = $scopeUtil;
        }


        protected function createDefaultAuthorizeController() {
            if (!isset($this->storages['client'])) {
                throw new \LogicException("You must supply a storage object implementing OAuth2\Storage\Interfaces\Client to use the authorize server");
            }

            if (0 == count($this->responseTypes))
                $this->responseTypes = $this->getDefaultResponseTypes();

            $config = array_intersect_key($this->config, array_flip(explode(" ", "allow_implicit enforce_state require_exact_redirect_uri")));

            return new Controller\Authorize($this->storages['client'], $this->responseTypes, $config, $this->getScopeUtil());
        }

        protected function createDefaultTokenController() {
            if (0 == count($this->grantTypes))
                $this->grantTypes = $this->getDefaultGrantTypes();

            if (is_null($this->clientAssertionType)) {
                foreach ($this->grantTypes as $grantType) {
                    if (!$grantType instanceof Assertions\Interfaces\ClientAssertionType) {
                        if (!isset($this->storages['client_credentials'])) {
                            throw new \LogicException("");
                        }

                        $config = array_intersect_key($this->config, array("allow_credentials_in_request_body" => ""));
                        $this->clientAssertionType = new Assertions\HttpBasic($this->storages['client_credentials'], $config);
                        break;
                    }
                }
            }

            return new Controller\Token($this->getAccessTokenResponseType(), $this->grantTypes, $this->clientAssertionType, $this->getScopeUtil());
        }

        protected function createDefaultResourceController() {
            if (!isset($this->storages['access_token']))
                throw new \LogicException("");

            if (!$this->tokenType)
                $this->tokenType = $this->getDefaultTokenType();

            $config = array_intersect_key($this->config, ['www_realm' => '']);

            return new Controller\Resource($this->tokenType, $this->storages['access_token'], $config, $this->getScopeUtil());
        }

        protected function getDefaultTokenType() {
            return new Tokens\Bearer(array_intersect_key($this->config, array_flip(explode(' ', 'token_param_name token_bearer_header_name'))));
        }

        protected function getDefaultResponseTypes() {
            $responseTypes = [];

            if (isset($this->storages['access_token']))
                $responseTypes['token'] = $this->getAccessTokenResponseType();

            if (isset($this->storages['authorization_code'])) {
                $config = array_intersect_key($this->config, array_flip(explode(' ', 'enforce_redirect auth_code_lifetime')));
                $responseTypes['code'] = new Responses\AuthorizationCode($this->storages['authorization_code'], $config);
            }

            if (count($responseTypes) == 0)
                throw new \LogicException("");

            return $responseTypes;
        }

        protected function getDefaultGrantTypes() {
            $grantTypes = [];

            if (isset($this->storages['user_credentials']))
                $grantTypes['password'] = new UserCredentials($this->storages['user_credentials']);

            if (isset($this->storages['client_credentials']))
                $grantTypes['client_credentials'] = new ClientCredentials($this->storages['client_credentials']);

            if (isset($this->storages['refresh_token']))
                $grantTypes['refresh_token'] = new RefreshToken($this->storages['refresh_token']);

            if (isset($this->storages['authorization_code']))
                $grantTypes['authorization_code'] = new AuthorizationCode($this->storages['authorization_code']);

            if (count($grantTypes) == 0)
                throw new \LogicException("");

            return $grantTypes;
        }

        protected function getAccessTokenResponseType() {
            if (isset($this->responseTypes['token']))
                return $this->responseTypes['token'];

            if (!isset($this->storages['access_token']))
                throw new \LogicException("");

            $refreshStorage = null;

            if (isset($this->storages['refresh_token']))
                $refreshStorage = $this->storages['refresh_token'];

            $config = array_intersect_key($this->config, array_flip(explode(' ', 'access_lifetime refresh_token_lifetime')));
            $config['token_type'] = $this->tokenType ? $this->tokenType->getTokenType() : $this->getDefaultTokenType()->getTokenType();

            return new Responses\AccessToken($this->storages['access_token'], $refreshStorage, $config);
        }

        public function getResponse() {
            return $this->response;
        }

        public function getStorages() {
            return $this->storages;
        }

        public function getStorage($name) {
            return (isset($this->storages[$name]) ? $this->storages[$name] : null);
        }

        public function getGrantTypes() {
            return $this->grantTypes;
        }

        public function getGrantType($name) {
            return (isset($this->grantTypes[$name]) ? $this->grantTypes[$name] : null);
        }

        public function getResponseTypes() {
            return $this->responseTypes;
        }

        public function getResponseType($name) {
            return (isset($this->responseTypes[$name]) ? $this->responseTypes[$name] : false);
        }

        public function getTokenType() {
            return $this->tokenType;
        }

        public function getClientAssertionType() {
            return $this->clientAssertionType;
        }

        public function setConfig($name, $value) {
            $this->config[$name] = $value;
        }

        public function getConfig($name, $default = null) {
            return (isset($this->config[$name]) ? $this->config[$name] : $default);
        }
    }