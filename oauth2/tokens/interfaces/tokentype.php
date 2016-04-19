<?php

namespace OAuth2\Tokens\Interfaces;

    interface TokenType {
        public function getTokenType();
        public function getAccessTokenParameter(\OAuth2\Request $request, \OAuth2\Response $response);
    }