<?php

namespace OAuth2;

    class Response {
        public $version = '1.1';

        protected $statusCode = 200;
        protected $statusText = 'OK';
        protected $httpHeaders = [];
        protected $params = [];

        public static $statusTexts = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            418 => 'I\'m a teapot',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported'
        ];

        public function __construct($params = [], $statusCode = 200, $headers = [], $version = '1.1') {
            $this->setParams($params);
            $this->setStatusCode($statusCode);
            $this->setHttpHeaders($headers);
            $this->setVersion($version);
        }

        public function __toString() {
            $headers = [];
            foreach ($this->httpHeaders as $k => $v) {
                $headers[$k] = (array)$v;
            }
            return sprintf("HTTP/%s %s %s", $this->version, $this->statusCode, $this->statusText) . PHP_EOL . $this->getHttpHeadersAsString($headers) . PHP_EOL . $this->getResponseBody();
        }

        public function buildHeader($name, $value) {
            return sprintf("%s: %s\n", $name, $value);
        }

        public function getStatusCode() {
            return $this->statusCode;
        }
        public function setStatusCode($statusCode, $text = null) {
            $this->statusCode = (int)$statusCode;
            if ($this->isInvalid()) {
                throw new \InvalidArgumentException("Invalid Status Code");
            }

            $this->statusText = false === $text ? "" : (null === $text ? self::$statusTexts[$this->statusCode] : $text);
        }

        public function getStatusText() {
            return $this->statusText;
        }
        public function setStatusText() {
            //
        }

        public function setVersion($version) {
            $this->version = $version;
        }

        public function getVersion() {
            return $this->version;
        }

        public function getParam($name, $default = null) {
            return (isset($this->params[$name]) ? $this->params[$name] : $default);
        }
        public function getParams() {
            return $this->params;
        }
        public function setParams(array $params = []) {
            $this->params = $params;
        }
        public function addParams(array $params = []) {
            $this->params += $params;
        }
        public function setParam($name = false, $value = false) {
            if ($name)
                $this->params[$name] = $value;
        }

        public function getHttpHeaders() {
            return $this->httpHeaders;
        }
        public function getHttpHeader($name, $default = null) {
            return (isset($this->httpHeaders[$name]) ? $this->httpHeaders[$name] : $default);
        }
        public function setHttpHeaders(array $httpHeaders = []) {
            $this->httpHeaders = $httpHeaders;
        }
        public function addHttpHeaders(array $httpHeaders = []) {
            $this->httpHeaders += $httpHeaders;
        }
        public function setHttpHeader($name = false, $value = false) {
            if ($name)
                $this->httpHeaders[$name] = $value;
        }

        public function getResponseBody($format = 'json') {
            switch ($format) {
                case "json" : {
                    return json_encode($this->params);
                } break;
                case "xml" : {
                    $xml = new \SimpleXMLElement("<response/>");
                        array_walk($this->params, array($xml, 'addChild'));

                    return $xml->asXML();
                } break;
                default : {
                    return $this->params;
                }
            }
        }

        public function send($format = 'json') {
            if (headers_sent())
                return;

            switch ($format) {
                case "json" : {
                    $this->setHttpHeader('Content-Type', 'application/json');
                } break;
                case "xml" : {
                    $this->setHttpHeader('Content-Type', 'text/xml');
                } break;
            }

            header(sprintf("HTTP/%s %s %s", $this->version, $this->statusCode, $this->statusText));

            foreach ($this->getHttpHeaders() as $k => $v) {
                header(sprintf("%s: %s", $k, $v));
            }

            echo $this->getResponseBody($format);
        }

        public function setError($statusCode, $error, $errorDescription = null, $errorUri = null) {
            $params = [
                'error' => $error,
                'error_description' => $errorDescription
            ];

            if (!is_null($errorUri)) {
                if (strlen($errorUri) > 0 && $errorUri[0] == "#")
                    $errorUri = "http://tools.ietf.org/html/rfc6749{$errorUri}";
                $params['error_uri'] = $errorUri;
            }

            $httpHeaders = [
                'Cache-Control' => 'no-store'
            ];

            $this->setStatusCode($statusCode);
            $this->addParams($params);
            $this->addHttpHeaders($httpHeaders);

            if (!$this->isClientError() && !$this->isServerError()) {
                throw new \InvalidArgumentException("");
            }
        }

        public function setRedirect($statusCode = 302, $url = false, $state = null, $error = null, $errorDescription = null, $errorUri = null) {
            if (empty($url))
                throw new \InvalidArgumentException("");

            $params = [];

            if (!is_null($state))
                $params['state'] == $state;

            if (!is_null($error))
                $this->setError(400, $error, $errorDescription, $errorUri);

            $this->setStatusCode($statusCode);
            $this->addParams($params);

            if (count($this->params) > 0) {
                $parts = parse_url($url);
                $sep = isset($parts['query']) && count($parts['query']) > 0 ? "&" : "?";
                $url .= $sep . http_build_query($this->params);
            }

            $this->addHttpHeaders(array("Location" => $url));

            if (!$this->isRedirection())
                throw new \InvalidArgumentException("");
        }

        public function isInvalid() {
            return $this->statusCode < 100 || $this->statusCode >= 600;
        }
        public function isInformational() {
            return $this->statusCode >= 100 && $this->statusCode < 200;
        }
        public function isSuccessful() {
            return $this->statusCode >= 200 && $this->statusCode < 300;
        }
        public function isRedirection() {
            return $this->statusCode >= 300 && $this->statusCode < 400;
        }
        public function isClientError() {
            return $this->statusCode >= 400 && $this->statusCode < 500;
        }
        public function isServerError() {
            return $this->statusCode >= 500 && $this->statusCode < 600;
        }

        public function getHttpHeadersAsString($headers = false) {
            if (count($headers) == 0)
                return;

            $max = max(array_map('strlen', array_keys($headers))) + 1;
            $content = '';

            ksort($headers);
            foreach ($headers as $k => $v) {
                foreach ($v as $val) {
                    $content .= sprintf("%-{$max}s %s\r\n", $this->beautifyHeaderName($k) . ":", $val);
                }
            }

            return $content;
        }

        public function beautifyHeaderName($name = false) {
            return preg_replace_callback('/\-(.)/', array($this, 'beautifyCallback'), ucfirst($name));
        }

        public function beautifyCallback($match) {
            return '-' . strtoupper($match[1]);
        }

    }