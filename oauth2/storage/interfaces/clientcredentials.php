<?php

namespace OAuth2\Storage\Interfaces;

    interface ClientCredentials extends Client {
        public function checkClientCredentials($client_id, $client_secret = null);
    }