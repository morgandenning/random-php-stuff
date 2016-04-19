<?php

namespace OAuth2\Storage\Interfaces;

    interface Client {
        public function getClientDetails($client_id);
        public function checkRestrictedGrantType($client_id, $grant_type);
    }