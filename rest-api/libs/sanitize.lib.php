<?php

namespace restAPI\Libraries;

    class globals {
        const lockGlobals = true;

        static private $storageArray = [];
        static private $allowedMethods = [
            'POST',
            'GET',
            'PUT',
            'DELETE'
        ];

        function __construct() {

            foreach (self::$allowedMethods as $allowedMethod) {
                if (isset($GLOBALS["_{$allowedMethod}"])) {
                    self::$storageArray[$allowedMethod] = $GLOBALS["_{$allowedMethod}"];

                    if (self::lockGlobals) {
                        unset($GLOBALS["_{$allowedMethod}"]);
                    }
                }
            }
        }

        static function Get($sMethod = 'POST', $xVal = null) {
            $sMethod = strtoupper($sMethod);
            if (!in_array($sMethod, self::$allowedMethods)) {
                sanitize::error("GLOBALS :: Invalid Method Call");
                return false;
            } else {
                if (!isset(self::$storageArray[$sMethod])) {
                    sanitize::error("GLOBALS :: {$sMethod} Empty");
                    return false;
                } else {
                    if (!$xVal) {
                        sanitize::error("GLOBALS :: \$var Is Null");
                        return false;
                    } else {
                        if (!isset(self::$storageArray[$sMethod][$xVal])) {
                            sanitize::setValue("bNotSet", true);
                            sanitize::error("{$xVal} Empty");
                            return false;
                        } else {
                            sanitize::setValue("xVal", self::$storageArray[$sMethod][$xVal]);
                            return true;
                        }
                    }
                }
            }
        }

        static function GetKeys($sMethod = 'POST') {
            return array_keys(self::$storageArray[strtoupper($sMethod)]);
        }
    }

    class sanitize {
        const logErrors = true;

        private static $xVal = false;
        private static $bValid = false;
        private static $bNotSet = false;
        private static $bEmpty = false;
        private static $aErrors = [];
        private static $aAllowedReturnVars = ['xVal', 'bValid', 'bNotSet', 'bEmpty', 'aErrors'];

        private $aArgs = ['sMethod', 'sVar', 'sSanitizeMethod', 'iMin', 'iMax'];
        private $aSanitizeMethods = [
            'SANITIZE_STR_ALPHA',
            'SANITIZE_STR_ALPHANUM',
            'SANITIZE_STR_NUM',
            'SANITIZE_STR_USERNAME',
            'SANITIZE_STR_PASSWORD',
            'SANITIZE_STR_EMAIL'
        ];

        /* $sMethod = 'POST', $sVar = null, $sSanitizeMethod = null, $iMin = 0, $iMax = 0 */
        public function __construct($aArgs = []) {
            if (func_num_args() > 0) {
                return $this->clean(func_get_args());
            }
        }

        /* $sMethod = 'POST', $sVar = null, $sSanitizeMethod = null, $iMin = 0, $iMax = 0 */
        public function clean($aArgs = []) {
            $this->reset();
            if (func_num_args() > 1)
                $aArgs = func_get_args();

            # Assign Arguments
                foreach ($aArgs as $iKey => $sValue) {
                    if (isset($this->aArgs[$iKey])) {
                        ${$this->aArgs[$iKey]} = $sValue;
                    }
                }

            if (!$sSanitizeMethod) {
                self::error("\$sSanitizeMethod Not Set");
            } else if (!in_array($sSanitizeMethod, $this->aSanitizeMethods)) {
                self::error("Invalid Sanitization Method");
            } else if (globals::get($sMethod, $sVar, $this)) {
                if (empty(self::$xVal))
                    self::setValue('bEmpty', true);

                switch ($sSanitizeMethod) {
                    case 'SANITIZE_STR_ALPHA' : {
                        if (preg_match('/[[:alpha:]]/', self::$xVal)) {
                            self::$bValid = true;
                        } else {
                            self::error("SANITIZE_STR_ALPHA Invalid Match");
                        }
                    } break;
                    case 'SANITIZE_STR_ALPHANUM' : {
                        if (preg_match('/[[:alnum:]]/', self::$xVal)) {
                            self::$bValid = true;
                        } else {
                            self::error("SANITIZE_STR_ALPHANUM Invalid Match");
                        }
                    } break;
                    case 'SANITIZE_STR_NUM' : {
                        if (preg_match('/[[:digit:]]/', self::$xVal)) {
                            self::$bValid = true;
                        } else {
                            self::error("SANITIZE_STR_NUM Invalid Match");
                        }
                    } break;
                    case 'SANITIZE_STR_USERNAME' : {
                        if (preg_match('/^([[:alnum:]]+)\\\\{1}([[:alpha:]]+)\.{1}([[:alpha:]]+)$/i', self::$xVal)) {
                            self::$bValid = true;
                        } else {
                            self::error("SANITIZE_STR_USERNAME Invalid Match");
                        }
                    } break;
                    case 'SANITIZE_STR_PASSWORD' : {
                        if (preg_match('/^[a-zA-Z]([\w\!\@\#\$\%\^\&\*\-\+\.]+)$/i', self::$xVal)) {
                            self::$bValid = true;
                        } else {
                            self::error("SANITIZE_STR_PASSWORD Invalid Match");
                        }
                    } break;
                    case 'SANITIZE_STR_EMAIL' : {
                        if (filter_var(self::$xVal,FILTER_VALIDATE_EMAIL)) {
                            self::$bValid = true;
                        } else {
                            self::error("SANITIZE_STR_EMAIL Invalid Match");
                        }
                    } break;
                    default : {
                        self::error("{$sSanitizeMethod} No Method Configured");
                    }
                }
            } else {
                self::error("globals::get did not return valid value");
            }

            self::$xVal = (self::$bValid === true ? self::$xVal : false);
        }

        private function reset() {
            self::$xVal = false;
            self::$bValid = false;
            self::$bNotSet = false;
            self::$bEmpty = false;
            self::$aErrors = [];
        }

        static public function setValue($sVar = null, $sVal = null) {
            if (func_num_args() == 2 && in_array($sVar, self::$aAllowedReturnVars)) {
                self::$$sVar = $sVal;
            } else {
                self::error("Unable to Set Value :: \$sVar - {$sVar}, \$sVal - {$sVal}");
                return false;
            }
        }
        static public function getValue($sVar = null) {
            if ($sVar && in_array($sVar, self::$aAllowedReturnVars)) {
                return self::$$sVar;
            } else {
                self::error("Unable to Get Value :: \$sVar - {$sVar}");
                return false;
            }
        }

        static public function error($sMessage = null) {
            if ($sMessage) {
                array_push(self::$aErrors, $sMessage);
            }
        }
    }
