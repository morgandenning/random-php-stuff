<?php

namespace OAuth2\Responses\Interfaces;

    interface ResponseType {
        public function getAuthorizeResponse($params, $user_id = null);
    }