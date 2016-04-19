<?php

namespace OAuth2\Controller\Interfaces;

    use OAuth2\Request;
    use OAuth2\Response;

    interface Token {

        public function handleTokenRequest(Request $request, Response $response);

        public function grantAccessToken(Request $request, Response $resonse);

    }