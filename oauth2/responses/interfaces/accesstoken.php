<?php

namespace OAuth2\Responses\Interfaces;

    interface AccessToken extends ResponseType {
        public function createAccessToken($client_id, $user_id, $scope = null, $includeRefreshToken = true);
    }