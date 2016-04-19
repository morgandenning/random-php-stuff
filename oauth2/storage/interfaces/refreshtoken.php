<?php

namespace OAuth2\Storage\Interfaces;

    interface RefreshToken {
        public function getRefreshToken($refresh_token);
        public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null);
        public function unsetRefreshToken($refresh_token);
    }