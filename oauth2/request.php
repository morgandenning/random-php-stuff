<?php

namespace OAuth2;

    class Request {
        public $attributes;
        public $request;
        public $query;
        public $server;
        public $files;
        public $cookies;
        public $headers;
        public $content;

        public function __construct(array $query = [], array $request =[], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null, array $headers = null) {
            $this->initialize($query, $request, $attributes, $cookies, $files, $server, $content, $headers);
        }

        public function initialize(array $query = [], array $request =[], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null, array $headers = null) {
            $this->request = $request;
            $this->query = $query;
            $this->attributes = $attributes;
            $this->cookies = $cookies;
            $this->files = $files;
            $this->server = $server;
            $this->content = $content;
            $this->headers = is_null($headers) ? $this->getHeadersFromServer($this->server) : $headers;
        }

        public function query($name, $default = null) {
            return isset($this->query[$name]) ? $this->query[$name] : $default;
        }

        public function request($name, $default = null) {
            return isset($this->request[$name]) ? $this->request[$name] : $default;
        }

        public function server($name, $default = null) {
            return isset($this->server[$name]) ? $this->server[$name] : $default;
        }

        public function headers($name, $default = null) {
            return isset($this->headers[$name]) ? $this->headers[$name] : $default;
        }

        public function getAllQueryParams() {
            return $this->query;
        }

        public function getContent($asResource = false) {
            if (false === $this->content || (true === $asResource && null !== $this->content)) {
                throw new \LogicException("");
            }

            if (true === $asResource) {
                $this->content = false;
                return fopen("php://input", "rb");
            }

            if (null === $this->content) {
                $this->content = file-get_contents("php://input");
            }

            return $this->content;
        }

        public function getHeadersFromServer($server) {
            $headers = [];

            foreach ($server as $k => $v) {
                if (0 === strpos($k, "HTTP_"))
                    $headers[substr($k, 5)] = $v;
                else if (in_array($k, array("CONTENT_LENGTH", "CONTENT_MD5", "CONTENT_TYPE")))
                    $headers[$k] = $v;
            }

            if (isset($server['PHP_AUTH_USER'])) {
                $headers['PHP_AUTH_USER'] = $server['PHP_AUTH_USER'];
                $headers['PHP_AUTH_PW'] = isset($server['PHP_AUTH_PW']) ? $server['PHP_AUTH_PW'] : '';
            } else {
                $authorizationHeader = null;

                if (isset($server['HTTP_AUTHORIZATION'])) {
                    $authorizationHeader = $server['HTTP_AUTHORIZATION'];
                } else if (isset($server['REDIRECT_HTTP_AUTHORIZATION'])) {
                    $authorizationHeader = $server['REDIRECT_HTTP_AUTHORIZATION'];
                } else if (function_exists("apache_request_headers")) {
                    $requestHeaders = apache_request_headers();
                    $requestHeaders = array_combine(array_map("ucwords", array_keys($requestHeaders)), array_values($requestHeaders));

                    if (isset($requestHeaders['Authorization'])) {
                        $authorizationHeader = trim($requestHeaders['Authorization']);
                    }
                }

                if (null !== $authorizationHeader) {
                    $headers['AUTHORIZATION'] = $authorizationHeader;
                    if (0 === stripos($authorizationHeader, "basic")) {
                        $exploded = explode(":", base64_decode(substr($authorizationHeader, 6)));
                        if (count($exploded) == 2) {
                            list($headers['PHP_AUTH_USER'], $headers['PHP_AUTH_PW']) = $exploded;
                        }
                    }
                }
            }

            if (isset($headers['PHP_AUTH_USER'])) {
                $headers['AUTHORIZATION'] = 'Basic ' . base64_encode($headers['PHP_AUTH_USER'] . ":" . $headers['PHP_AUTH_PW']);
            }

            return $headers;
        }

        public static function createFromGlobals() {
            $class = __CLASS__;
            $request = new $class($_GET, $_POST, [], $_COOKIE, $_FILES, $_SERVER);

            $contentType = $request->server("CONTENT_TYPE", "");
            $requestMethod = $request->server("REQUEST_METHOD", "GET");

            if (0 === strpos($contentType, "application/x-www-form-urlencoded") && in_array(strtoupper($requestMethod), ['PUT', 'DELETE'])) {
                parse_str($request->getContent(), $data);
                $request->request = $data;
            } else if (0 === strpos($contentType, 'application/json') && in_array(strtoupper($requestMethod, ['POST', 'PUT', 'DELETE']))) {
                $data = json_decode($request->getContent(), true);
                $request->request = $data;
            }

            return $request;
        }
    }