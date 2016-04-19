<?php

namespace OAuth2;

    class Scope {
        protected $storage;

        public function __construct($storage = null) {
            if (is_null($storage) || is_array($storage))
                $storage = new Memory((array)$storage);

            if (!$storage instanceof Storage\Interfaces\Scope) {
                throw new \InvalidArgumentException("");
            }

            $this->storage = $storage;
        }

        public function checkScope($required_scope, $available_scope) {
            $required_scope = explode(" ", trim($required_scope));
            $available_scope = explode(" ", trim($available_scope));

            return (count(array_diff($required_scope, $available_scope)) == 0);
        }

        public function scopeExists($scope, $client_id = null) {
            return $this->storage->scopeExists($scope, $client_id);
        }

        public function getScopeFromRequest(Request $request) {
            return $request->request("scope", $request->query("scope"));
        }

        public function getDefaultScope($client_id = null) {
            return $this->storage->getDefaultScope($client_id);
        }
    }