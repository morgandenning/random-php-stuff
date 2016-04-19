<?php

namespace OAuth2;

    class Storage {
        public $oConnection = false;

        public function __construct($storageType = 'MySQL', array $configOpts = []) {
            $sStorageClass = "\\OAuth2\\Storage\\{$storageType}";
            $this->oConnection = new $sStorageClass($configOpts);
        }

    }