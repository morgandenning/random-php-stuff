<?php

namespace restAPI\Services;

    class Reseller {

        private $validFields = [
            'fullname',
            'email',
            'phone',
            'address',
            'password'
        ];

        private $requiredFields = [
            'fullname',
            'email'
        ];

        private $sanitizeMethodForField = [
            'fullname' => 'SANITIZE_STR_ALPHA',
            'email' => 'SANITIZE_STR_EMAIL',
            'phone' => 'SANITIZE_STR_NUM',
            'address' => 'SANITIZE_STR_ALPHANUM',
            'password' => 'SANITIZE_STR_PASSWORD'
        ];

        public function __construct() {
            #
        }

        public function __invoke() {
            \restAPI\Response::SetHeaderStatusCode(\restAPI\Utils::HTTP_NOT_IMPLEMENTED);
        }


        /* # Service Methods # */

        /**
        * Reseller Service - DELETE
        *
        */
        public function DELETE() {
            #
        }

        /**
        * Reseller Service - GET
        *
        */
        public function GET() {
            try {
                if ($sqlResult = \restAPI\Libraries\db::query("SELECT * FROM users")) {
					//while ($sqlResult = $sqlRow) {
						\restAPI\Response::SetResponseData([
                            'Successful' => true,
                            'Message' => $sqlResult
                        ]);
					//}
				}

                //

                /*try {
                    $oMySQL->Query("SELE")
                }*/

                /*if (true == true) {
                    \restAPI\Response::SetHeaderStatusCode(\restAPI\Utils::HTTP_OK);

                    \restAPI\Response::SetResponseData([
                        'Result' => 'Successful',
                        'Data' => [
                            'ID' => rand()
                        ]
                    ]);

                } else {
                    \restAPI\Response::SetHeaderStatusCode(\restAPI\Utils::HTTP_BAD_REQUEST);
                }*/
            } catch (\restAPI\Libraries\SQLException $e) {
                \restAPI\Response::SetHeaderStatusCode(\restAPI\Utils::HTTP_INTERNAL_SERVER_ERROR);
				error_log($e->getMessage());
            }
        }

        /**
        * Reseller Service - POST
        *
        */
        public function POST() {

            # Sanitize Incoming Values
                $oSanitize = new \restAPI\Libraries\sanitize();

                $incomingValues = [];

                foreach ($this->validFields as $fieldValue) {
                    /* $sMethod = 'POST', $sVar = null, $sSanitizeMethod = null, $iMin = 0, $iMax = 0 */
                    $oSanitize->clean('POST', $fieldValue, (isset($this->sanitizeMethodForField[$fieldValue]) ? $this->sanitizeMethodForField[$fieldValue] : 'SANITIZE_STR_ALPHANUM'));
                        if ($oSanitize::getValue('bValid')) {
                            $incomingValues[$fieldValue] = $oSanitize::getValue("xVal");
                        }
                }

                if (count(array_intersect(array_keys($incomingValues), $this->requiredFields)) == 0) {
                    \restAPI\Response::SetHeaderStatusCode(\restAPI\Utils::HTTP_BAD_REQUEST);
                    \restAPI\Response::SetResponseData([
                        'Successful' => false,
                        'Message' => 'Missing Required Fields'
                    ]);
                } else {
                    # Check if Email Account already Exists
                    try {
                        if ($sqlResult = \restAPI\Libraries\db::queryRow("SELECT email FROM users WHERE email=".\restAPI\Libraries\db::escape($incomingValues['email'])." LIMIT 1")) {
                            \restAPI\Response::SetHeaderStatusCode(\restAPI\Utils::HTTP_BAD_REQUEST);
                            \restAPI\Response::SetResponseData([
                                'Successful' => false,
                                'Message' => 'An Account with this Email Address already Exists'
                            ]);
                            return false;
                        }
                    } catch (\restAPI\Libraries\SQLException $e) {
                        error_log($e->getMessage());
                        \restAPI\Response::SetHeaderStatusCode(\restAPI\Utils::HTTP_INTERNAL_SERVER_ERROR);
                        \restAPI\Response::SetResponseData([
                            'Successful' => false,
                            'Message' => 'A Database Error Occurred'
                        ]);
                        return false;
                    }
                    # Create Reseller Data
                        try {

                            # Create Temporary Password
                                #$tempPassword = base_convert(uniqid("temporary-password", true), 10, 36);

                            if ($sqlResult = \restAPI\Libraries\db::insert([
                                'table' => 'users',
                                'data' => [
                                    'uuid' => 'UUID()',
                                    'email' => $incomingValues['email'],
                                    //'password' => crypt($tempPassword, sprintf("$2y$%02d$%s", 14, substr(str_replace('+', '.', base64_encode(pack('N4', mt_rand(), mt_rand(), mt_rand(), mt_rand()))), 0, 22))),
                                    'first_name' => isset(explode(' ', $incomingValues['fullname'])[0]) ? explode(' ', $incomingValues['fullname'])[0] : "",
                                    'last_name' => isset(explode(' ', $incomingValues['fullname'])[1]) ? explode(' ', $incomingValues['fullname'])[1] : "",
                                    'status' => 'pending',
                                    'flags' => '5'
                                ]
                            ])) {
                                # Send Email
                                    try {
                                        $mailObject = new \Zend_Mail();
                                            $mailObject->addTo($incomingValues['email'], $incomingValues['fullname']);
                                            $mailObject->addBcc("morgan.denning@netbiz.com", "Reseller Sign-Up");
                                            $mailObject->setFrom("morgan.denning@netbiz.com", "Netbiz Reseller Program");
                                            $mailObject->setSubject("Thank You for applying to the Netbiz Reseller Program");
                                            $mailObject->setBodyText("Your application has been received. It will be reviewed by our staff and you will be notified shortly.");

                                        $mailObject->send((new \Zend_Mail_Transport_Smtp('mail.netbiz.com', ['auth' => 'login', 'username' => 'morgan.denning@netbiz.com', 'password' => 'gype!defy'])));
                                    } catch (\Zend_Exception $e) {
                                        error_log($e->getMessage());
                                    }

                                \restAPI\Response::SetResponseData([
                                    'Successful' => true,
                                    'Message' => "Account Created"
                                ]);
                            }

                        } catch (\restAPI\Libraries\SQLException $e) {
                            error_log($e->getMessage());
                            \restAPI\Response::SetHeaderStatusCode(\restAPI\Utils::HTTP_INTERNAL_SERVER_ERROR);
                            \restAPI\Response::SetResponseData([
                                'Successful' => false,
                                'Message' => 'Error Creating Data'
                            ]);
                        }
                }
        }

    }