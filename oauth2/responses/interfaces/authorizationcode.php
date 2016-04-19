<?php

namespace OAuth2\Responses\Interfaces;

    interface AuthorizationCode extends ResponseType {
        public function enforceRedirect();
        public function createAuthorizationCode($client_id, $user_id, $redirect_url, $scope = null);
    }