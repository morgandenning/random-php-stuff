<?php

namespace OAuth2\Controller\Interfaces;

    use OAuth2\Request;
    use OAuth2\Response;

    interface Resource {

        public function verifyResourceRequest(Request $request, Response $response, $scope = null);
        public function getAccessTokenData(Request $request, Response $response);

    }