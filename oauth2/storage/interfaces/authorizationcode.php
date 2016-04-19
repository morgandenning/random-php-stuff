<?php

namespace OAuth2\Storage\Interfaces;

    interface AuthorizationCode {
        const RESPONSE_TYPE_CODE = "code";

        public function getAuthorizationCode($code);
        public function setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null);
        public function expireAuthorizationCode($code);
    }