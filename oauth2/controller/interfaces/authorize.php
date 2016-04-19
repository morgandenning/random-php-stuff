<?php

namespace OAuth2\Controller\Interfaces;

    use OAuth2\Request;
    use OAuth2\Response;

    interface Authorize {

        const RESPONSE_TYPE_AUTHORIZATION_CODE = 'code';
        const RESPONSE_TYPE_ACCESS_TOKEN = 'token';

        public function handleAuthorizeRequest(Request $request, Response $response, $is_authorized = false, $user_id = null);
        public function validateAuthorizeRequest(Request $request, Response $response);

    }