<?php

    # Helper - DB
    /**
    * Helper Class for DB Interactions
    */

namespace restAPI\Libraries;

    class SQLException extends \Exception {}

    class db {
        const DB_QUERY_RETURN_TYPE_DEFAULT = 1;
        const DB_QUERY_RETURN_TYPE_ROW = 2;
        const DB_QUERY_RETURN_TYPE_INSERT = 4;
        const DB_QUERY_RETURN_TYPE_UPDATE = 8;
        const DB_QUERY_RETURN_TYPE_DELETE = 16;

        const DB_DATABASE_DEFAULT = 'default';
        const debugMode = true;

        static private $dbObjects = [];

        public function __construct() {}
        public function __destruct() {
            foreach (self::$dbObjects as $dbObject) {
                if ($dbObject->isConnected())
                    try {
                        $dbObject->closeConnection();
                    } catch (\Exception $e) {
                        throw new SQLException($e);
                    }
            }
        }

        private static function createInstance($dbSwitch = self::DB_DATABASE_DEFAULT) {
            if (!array_key_exists($dbSwitch, self::$dbObjects)) {
                try {

                    $dbConfig = new \Zend_Config_Ini(getcwd() . "/config/db.config.ini", $dbSwitch, array("allowModifications" => true));
                    $dbConfig->db->driver_options = array(
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                    );

                    try {
                        $dbObject = \Zend_Db::factory($dbConfig->db->adapter, $dbConfig->db->params);
                        $dbObject->getConnection();
                        $dbObject->closeConnection();
                        $dbObject->getProfiler()->setEnabled(true);

                        return $dbObject;
                    } catch (\Exception $e) {
                        throw new SQLException($e);
                    }
                } catch (\Exception $e) {
                    throw new SQLException($e);
                }
            }
        }

        private static function getInstance($dbSwitch = self::DB_DATABASE_DEFAULT, $instantiateObject = true) {
            if (!array_key_exists($dbSwitch, self::$dbObjects)) {
                if (!$instantiateObject || (!self::$dbObjects[$dbSwitch] = self::createInstance($dbSwitch)))
                    return false;
            }

            return self::$dbObjects[$dbSwitch];
        }

        public static function getSQLFromFile($sqlFile = false, $dataArray = false) {
            try {
                return ((file_exists($sqlFile) ? ($dataArray && is_array($dataArray)) ? vsprintf(file_get_contents($sqlFile), $dataArray) : file_get_contents($sqlFile) : false));
            } catch (\Exception $e) {
                throw new SQLException($e);
            }
        }

        public static function escape($string = null, $dbSwitch = self::DB_DATABASE_DEFAULT) {
            if ($sqlObject = self::getInstance($dbSwitch)) {
                try {
                    return ((preg_match("/(INET_ATON\\(|UUID\\(\\)|NOW\\(\\)|NULL)/", $string) ? $string : (is_null($string) ? "NULL" : $sqlObject->quote($string))));
                } catch (\Exception $e) {
                    throw new SQLException($e);
                }
            } else {
                error_log("no instance");
                return false;
            }
        }

        public static function startTransaction($dbSwitch = self::DB_DATABASE_DEFAULT) {
            if ($sqlObject = self::getInstance($dbSwitch)) {
                try {
                    if (!$sqlObject->isConnected())
                        $sqlObject->getConnection();

                    try {
                        $sqlObject->beginTransaction();
                        return true;
                    } catch (\Exception $e) {
                        throw new SQLException($e);
                    }
                } catch (\Exception $e) {
                    throw new SQLException($e);
                }
            } else
                return false;
        }
        public static function commitTransaction($dbSwitch = self::DB_DATABASE_DEFAULT) {
            if ($sqlObject = self::getInstance($dbSwitch, false)) {
                try {
                    $sqlObject->commit();
                    try {
                        $sqlObject->closeConnection();
                    } catch (\Exception $e) {
                        throw new SQLException($e);
                    }

                    return true;
                } catch (\Exception $e) {
                    throw new SQLException($e);
                }
            } else
                return false;
        }
        public static function revertTransaction($dbSwitch = self::DB_DATABASE_DEFAULT) {
            if ($sqlObject = self::getInstance($dbSwitch, false)) {
                try {
                    $sqlObject->rollBack();
                    try {
                        $sqlObject->closeConnection();
                    } catch (\Exception $e) {
                        throw new SQLException($e);
                    }

                    return true;
                } catch (\Exception $e) {
                    throw new SQLException($e);
                }
            } else
                return false;
        }

        public static function query($sqlQuery = false, $dbSwitch = self::DB_DATABASE_DEFAULT, $returnType = self::DB_QUERY_RETURN_TYPE_DEFAULT, $returnEmptyAsFalse = true) {
            if (!$sqlQuery)
                return false;
            else {
                if (self::debugMode) {
                    error_log($sqlQuery);
                }

                if ($sqlObject = self::getInstance($dbSwitch)) {
                    try {
                        $sqlResult = $sqlObject->query($sqlQuery);

                        switch ($returnType) {
                            case self::DB_QUERY_RETURN_TYPE_INSERT :
                                    return $sqlObject;
                                break;
                            case self::DB_QUERY_RETURN_TYPE_UPDATE :
                            case self::DB_QUERY_RETURN_TYPE_DELETE :
                                    return true;
                                break;
                            case self::DB_QUERY_RETURN_TYPE_ROW :
                                    try {
                                        if ($sqlResult->rowCount() > 0) {
                                            return $sqlResult->fetch();
                                        } else {
                                            return false;
                                        }
                                    } catch (\Exception $e) {
                                        throw new SQLException($e);
                                    }
                                break;
                            case self::DB_QUERY_RETURN_TYPE_DEFAULT :
                                    try {
                                        $resultsArray = array();
                                        while ($result = $sqlResult->fetch()) {
                                            $resultsArray[] = $result;
                                        }

                                        return ($resultsArray && count($resultsArray) > 0) ? $resultsArray : ($returnEmptyAsFalse ? false : true);
                                    } catch (\Exception $e) {
                                        throw new SQLException($e);
                                    }
                                break;
                        }

                    } catch (\Zend_Db_Statement_Exception $e) {
                        throw new SQLException($e);
                    } catch (\Exception $e) {
                        throw new SQLException($e);
                    }
                } else {
                    error_log("DB:ERROR: LINE - " . __LINE__ . " :: NO DB INSTANCE");
                    return false;
                }
            }
        }

        public static function queryRow($sqlQuery = false, $dbSwitch = self::DB_DATABASE_DEFAULT) {
            return self::query($sqlQuery, $dbSwitch, self::DB_QUERY_RETURN_TYPE_ROW);
        }

        public static function select($sqlData, $dbSwitch = self::DB_DATABASE_DEFAULT) {
            if (!$sqlData || !is_array($sqlData))
                return false;
            else {
                if (!isset($sqlData['table'], $sqlData['where']))
                    return false;

                $prepData = function($sqlData) {
                    $return = [];

                    $prepValues = function(&$item, $key) {
                        if (preg_match('#\{(.+?)\}#', $item)) {
                            preg_match('#\{(.+?)\}#', $item, $mods);
                            switch ($mods[1]) {
                                case 'lt' :
                                        $comp = '<';
                                    break;
                                case 'ltoe' :
                                        $comp = '<=';
                                    break;
                                case 'gt' :
                                        $comp = '>';
                                    break;
                                case 'gtoe' :
                                        $comp = '>=';
                                    break;
                                case 'in' :
                                        $comp = 'IN';
                                    break;
                                case 'like' :
                                        $comp = 'LIKE';
                                    break;
                            }
                            if (in_array($comp, ['IN'])) {
                                preg_match('#\((.+?)\)#', $item, $fields);



                                $item = "`{$key}` {$comp} (" . str_replace($mods[0], '', implode(",", array_map('self::escape', explode(',', $fields[1])))) . ")";
                            } else {
                                $item = "`{$key}` {$comp} " . db::escape(str_replace($mods[0], '', $item));
                            }
                        } else {
                            $item = "`{$key}` = " . db::escape($item);
                        }
                    };
                    if (isset($sqlData['where']) && is_array($sqlData['where'])) {
                        $where = $sqlData['where'];
                        array_walk($where, $prepValues);

                        $return[0] = implode(" AND ", $where);
                    }

                    return $return;
                };

                $createQuery = function() use ($prepData, $sqlData) {
                    $preppedData = $prepData($sqlData);
                    return "SELECT * FROM `{$sqlData['table']}` " . (isset($preppedData[0]) ? " WHERE " . $preppedData[0] : '');
                };

                $sqlResult = self::query($createQuery(), $dbSwitch, self::DB_QUERY_RETURN_TYPE_DEFAULT, false);
                if ($sqlResult) {
                    return $sqlResult;
                } else {
                    return false;
                }
            }
        }

        public static function update($sqlData = false, $dbSwitch = self::DB_DATABASE_DEFAULT) {
            if (!$sqlData || !is_array($sqlData))
                return false;
            else {
                if (!isset($sqlData['table'], $sqlData['data']))
                    return false;

                $prepData = function($sqlData) {
                    $return = [];

                    $prepValues = function(&$item, $key) {
                        if (preg_match('#\{(.+?)\}#', $item)) {
                            preg_match('#\{(.+?)\}#', $item, $mods);
                            switch ($mods[1]) {
                                case 'lt' :
                                        $comp = '<';
                                    break;
                                case 'ltoe' :
                                        $comp = '<=';
                                    break;
                                case 'gt' :
                                        $comp = '>';
                                    break;
                                case 'gtoe' :
                                        $comp = '=>';
                                    break;
                                case 'in' :
                                        $comp = 'IN';
                                    break;
                            }

                            $item = "`{$key}` {$comp} " . db::escape(str_replace($mods[0], '', $item));
                        } else {
                            $item = "`{$key}` = " . db::escape($item);
                        }
                    };

                    if (is_array($sqlData['data'])) {
                        $data = $sqlData['data'];
                        array_walk($data, $prepValues);

                        $return[0] = implode(",", $data);
                    }
                    if (isset($sqlData['where']) && is_array($sqlData['where'])) {
                        $where = $sqlData['where'];
                        array_walk($where, $prepValues);

                        $return[1] = implode(" AND ", $where);
                    }

                    return $return;
                };

                $createQuery = function() use ($prepData, $sqlData) {
                    $preppedData = $prepData($sqlData);
                    return "UPDATE `{$sqlData['table']}` SET {$preppedData[0]}" . (isset($preppedData[1]) ? " WHERE " . $preppedData[1] : '');
                };

                $sqlResult = self::query($createQuery(), $dbSwitch, self::DB_QUERY_RETURN_TYPE_UPDATE);
                if ($sqlResult) {
                    return true;
                } else {
                    return false;
                }
            }
        }

        public static function insert($sqlData = false, $dbSwitch = self::DB_DATABASE_DEFAULT) {
            if (!$sqlData || !is_array($sqlData))
                return false;
            else {
                if (!isset($sqlData['table'], $sqlData['data']))
                    return false;

                $prepData = function($sqlData) {
                    $cols = array_keys((array)$sqlData);
                    $vals = array_values((array)$sqlData);

                    $prepColumnsArray = function(&$item) {
                        $item = "`{$item}`";
                    };
                    $prepValuesArray = function(&$item) {
                        $item = self::escape($item);
                    };

                    array_walk($cols, $prepColumnsArray);
                    array_walk($vals, $prepValuesArray);

                    return array(implode(",", $cols), implode(",", $vals));
                };

                $createQuery = function() use ($prepData, $sqlData) {
                    $preppedData = $prepData($sqlData['data']);
                    return "INSERT INTO `{$sqlData['table']}` ({$preppedData[0]}) VALUES ({$preppedData[1]})";
                };

                $sqlResult = self::query($createQuery(), $dbSwitch, self::DB_QUERY_RETURN_TYPE_INSERT);
                if ($sqlResult) {
                    $lastInsertId = $sqlResult->lastInsertId();
                    return ($lastInsertId > 0) ? $lastInsertId : true;
                } else {
                    return false;
                }
            }
        }

        public static function delete($sqlData = false, $dbSwitch = self::DB_DATABASE_DEFAULT) {
            if ($sqlData && isset($sqlData['table'], $sqlData['where'])) {
                $prepData = function($sqlData) {
                    $return = [];

                    $prepValues = function(&$item, $key) {
                        $item = "`{$key}` = " . db::escape($item);
                    };

                    if (is_array($sqlData['where'])) {
                        $where = $sqlData['where'];
                        array_walk($where, $prepValues);

                        $return[0] = implode(" AND ", $where);
                    }

                    return $return;
                };

                $createQuery = function() use ($prepData, $sqlData) {
                    $preppedData = $prepData($sqlData);
                    return "DELETE FROM `{$sqlData['table']}` WHERE {$preppedData[0]}";
                };

                $sqlResult = self::query($createQuery(), $dbSwitch, self::DB_QUERY_RETURN_TYPE_DELETE);
                if ($sqlResult) {
                    return true;
                } else {
                    return false;
                }
            }
        }

    }