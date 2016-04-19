<?php

namespace OAuth2\Grants\Interfaces;

    interface GrantType {
        public function validateRequest(\OAuth2\Request $request, \OAuth2\Response $response);
        public function getClientId();
        public function getUserId();
        public function getScope();
        public function createAccessToken(\OAuth2\Responses\Interfaces\AccessToken $accessToken, $client_id, $user_id, $scope);
    }