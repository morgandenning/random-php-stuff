<?php

namespace OAuth2\Assertions\Interfaces;

    interface ClientAssertionType {
        public function validateRequest(\OAuth2\Request $request, \OAuth2\Response $response);
        public function getClientId();
    }