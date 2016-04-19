<?php

namespace OAuth2\Tokens;

    class Bearer implements Interfaces\TokenType {
        private $config;

        public function __construct(array $config = []) {
            $this->config = array_merge([
                'token_param_name' => 'access_token',
                'token_bearer_header_name' => 'Bearer'
            ], $config);
        }

        public function getTokenType() {
            return 'Bearer';
        }

        public function getAccessTokenParameter(\OAuth2\Request $request, \OAuth2\Response $response) {
            $headers = $request->headers("AUTHORIZATION");

            $methodsUsed = !empty($headers) + !is_null($request->query($this->config['token_param_name'])) + !is_null($request->request($this->config['token_param_name']));
            if ($methodsUsed > 1) {
                $response->setError(400, 'invalid_request', 'Only one method may be used to authenticate at a time (Auth header, GET or POST)');
                return null;
            }

            if ($methodsUsed == 0) {
                $response->setStatusCode(401);
                return null;
            }

            if (!empty($headers)) {
                if (!preg_match("/" . $this->config['token_bearer_header_name'] . '\s(\S+)/', $headers, $matches)) {
                    $response->setError(400, 'invalid_request', 'Malformed auth header');
                    return null;
                }
                return $matches[1];
            }

            if ($request->request($this->config['token_param_name'])) {
                if (strtolower($request->server("REQUEST_METHOD")) != 'post') {
                    $response->setError(400, 'invalid_request', 'When putting the token in the body, the method must be POST');
                    return null;
                }

                $contentType = $request->server("CONTENT_TYPE");
                if (false !== $pos = strpos($contentType, ";")) {
                    $contentType = substr($contentType, 0, $pos);
                }

                if ($contentType !== null && $contentType != "application/x-www-form-urlencoded") {
                    $response->setError(400, 'invalid_request', 'The content type for POST requests must be "application/x-www-form-urlencoded');
                    return null;
                }

                return $request->request($this->config['token_param_name']);
            }

            return $request->query($this->config['token_param_name']);
        }
    }