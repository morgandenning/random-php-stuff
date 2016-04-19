<?php

namespace OAuth2\Storage\Interfaces;

    interface AccessToken {
        public function getAccessToken($access_token);
        public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null);
    }