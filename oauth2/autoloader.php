<?php

    ## OAuth2 Autoloader

namespace OAuth2;

    class Autoloader {
        private $libDir = __DIR__;

        public static function Register() {
            ini_set('unserialize_callback_func', 'spl_autoload_call');
            spl_autoload_register(array((new self), 'Autoload'));
        }

        public function Autoload($class) {
            if (strpos($class, 'OAuth2') !== 0) {
                return;
            }
            if (file_exists($file = $this->libDir . "/" . strtolower(substr(str_replace('\\', '/', $class), strpos($class, "\\") + 1)) . ".php")) {
                require $file;
            }
        }
    }