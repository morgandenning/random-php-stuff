<?php

    # REST API Wrapper {}

    try {
        # Load Zend AutoLoader Object
            require_once 'Zend/Loader/Autoloader.php';
                \Zend_Loader_Autoloader::getInstance();

        require './utils.php';
        require './libs/mysql.lib.php';
        require './libs/sanitize.lib.php';

        #
            new \restAPI\Libraries\globals();

        # Load Request
            new \restAPI\Request;

        # Send Response
            \restAPI\Response::SendResponse();

    } catch (Exception $e) {
        error_log('wtf');
        header("HTTP/1.1 500 Internal Server Error");
    }