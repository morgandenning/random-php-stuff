<?php

    #

namespace OAuth2\Storage;

    class MySQL implements Interfaces\AccessToken, Interfaces\AuthorizationCode, Interfaces\ClientCredentials, Interfaces\RefreshToken, Interfaces\Scope {
        private $oDB = false;
        private $aConfigOpts = [
            'tables' => [
                'clients' => 'oauth_clients',
                'access_tokens' => 'oauth_access_tokens',
                'refresh_tokens' => 'oauth_refresh_tokens',
                'authorization_codes' => 'oauth_authorization_codes',
                'scope' => 'oauth_scopes'
            ]
        ];

        public function __construct(array $aConfigOpts = []) {
            if (!isset($aConfigOpts['host'], $aConfigOpts['username'], $aConfigOpts['password'], $aConfigOpts['db'])) {
                throw new \InvalidArgumentException("OAuth2\\Storage Requires Proper Config Options");
            }

            try {
                # Load Zend AutoLoader Object
                    require_once 'Zend/Loader/Autoloader.php';
                        \Zend_Loader_Autoloader::getInstance();

                $this->oDB = \Zend_Db::factory('pdo_mysql', ['host' => $aConfigOpts['host'], 'username' => $aConfigOpts['username'], 'password' => $aConfigOpts['password'], 'dbname' => $aConfigOpts['db']]);
            } catch (\Exception $e) {
                echo $e->getMessage();
                return false;
            }
        }

        public function __destruct() {
            $this->oDB->closeConnection();
        }


        public function checkClientCredentials($client_id = false, $client_secret = false) {
            if ($client_id && $client_secret) {
                $sqlStatement = $this->oDB->query(sprintf("SELECT * FROM %s WHERE client_id = :client_id", $this->aConfigOpts['tables']['clients']), [':client_id' => $client_id]);
                return ($sqlStatement->fetch()['client_secret'] == sha1($client_secret));
            } else
                return false;
        }

        public function getClientDetails($client_id = false) {
            if ($client_id) {
                if ($sqlResult = $this->oDB->query(sprintf("SELECT * FROM %s WHERE client_id = %s", $this->aConfigOpts['tables']['clients'], $this->oDB->quote($client_id)))) {
                    return $sqlResult->fetch();
                } else {
                    //echo $this->oDB->error;
                    return false;
                }
            } else
                return false;
        }

        public function setClientDetails($client_id, array $aDetails = []) {
            //
        }

        public function checkRestrictedGrantType($client_id, $grant_type) {
            $details = $this->getClientDetails($client_id);
            if (isset($details['grant_types'])) {
                $grant_types = explode(' ', $details['grant_types']);
                return in_array($grant_type, (array)$grant_types);
            }
            return true;
        }

        public function getAccessToken($access_token) {
            if ($access_token) {
                $sqlStatement = $this->oDB->query(sprintf('SELECT %1$s.*, (SELECT CONCAT(" ", scope) FROM %2$s WHERE client_id = %1$s.client_id) as scope FROM %1$s WHERE access_token = :access_token', $this->aConfigOpts['tables']['access_tokens'], $this->aConfigOpts['tables']['scope']), [':access_token' => $access_token]);
                if ($sqlRow = $sqlStatement->fetch()) {
                    $sqlRow['expires'] = strtotime($sqlRow['expires']);
                    return $sqlRow;
                }
            } else
                return false;
        }

        public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null) {
            if ($this->getAccessToken($access_token)) {
                $sqlStatement = $this->oDB->query(sprintf("UPDATE %s SET client_id = :client_id, expires = :expires, user_id = :user_id, scope = :scope WHERE access_token = :access_token", $this->aConfigOpts['tables']['access_tokens']), [
                    ':client_id' => $client_id,
                    ':expires' => date("Y-m-d H:i:s", $expires),
                    ':user_id' => $user_id,
                    ':scope' => $scope,
                    ':access_token' => $access_token
                ]);
            } else {
                $sqlStatement = $this->oDB->query(sprintf("INSERT INTO %s (access_token, client_id, expires, user_id, scope) VALUES (:access_token, :client_id, :expires, :user_id, :scope)", $this->aConfigOpts['tables']['access_tokens']), [
                    ':client_id' => $client_id,
                    ':expires' => date("Y-m-d H:i:s", $expires),
                    ':user_id' => $user_id,
                    ':scope' => $scope,
                    ':access_token' => $access_token
                ]);
            }

            return $sqlStatement;
        }

        public function getAuthorizationCode($code) {
            $sqlStatement = $this->oDB->query(sprintf("SELECT * FROM %s WHERE authorization_code = :code", $this->aConfigOpts['tables']['authorization_codes']), [':code' => $code]);

            if ($sqlRow = $sqlStatement->fetch()) {
                $sqlRow['expires'] = strtotime($sqlRow['expires']);
                return $sqlRow;
            }

            return false;
        }

        public function setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null) {
            if ($this->getAuthorizationCode($code)) {
                $sqlStatement = $this->oDB->query(sprintf("UPDATE %s SET client_id = :client_id, user_id = :user_id, redirect_uri = :redirect_uri, expires = :expires, scope = :scope WHERE authorization_code = :code", $this->aConfigOpts['tables']['authorization_codes']), [
                    ':code' => $code,
                    ':client_id' => $client_id,
                    ':user_id' => $user_id,
                    ':redirect_uri' => $redirect_uri,
                    ':expires' => date("Y-m-d H:i:s", $expires),
                    ':scope' => $scope
                ]);
            } else {
                $sqlStatement = $this->oDB->query(sprintf("INSERT INTO %s (authorization_code, client_id, user_id, redirect_uri, expires, scope) VALUES (:code, :client_id, :user_id, :redirect_uri, :expires, :scope)", $this->aConfigOpts['tables']['authorization_codes']), [
                    ':code' => $code,
                    ':client_id' => $client_id,
                    ':user_id' => $user_id,
                    ':redirect_uri' => $redirect_uri,
                    ':expires' => date("Y-m-d H:i:s", $expires),
                    ':scope' => $scope
                ]);
            }

            return $sqlStatement;
        }

        public function expireAuthorizationCode($code) {
            return $this->oDB->query(sprintf("DELETE FROM %s WHERE authorization_code = :code", $this->aConfigOpts['tables']['authorization_codes']), [':code' => $code]);
        }


        public function getRefreshToken($refresh_token) {
            $sqlStatement = $this->oDB->query(sprintf("SELECT * FROM %s WHERE refresh_token = :refresh_token", $this->aConfigOpts['tables']['refresh_tokens']), [':refresh_token' => $refresh_token]);

            if ($sqlRow = $sqlStatement->fetch()) {
                $sqlRow['expires'] = strtotime($sqlRow['expires']);
                return $sqlRow;
            }

            return false;
        }

        public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null) {
            return $this->oDB->query(sprintf("INSERT INTO %s (refresh_token, client_id, user_id, expires, scope) VALUES (:refresh_token, :client_id, :user_id, :expires, :scope)", $this->aConfigOpts['tables']['refresh_tokens']), [
                ':client_id' => $client_id,
                ':expires' => date("Y-m-d H:i:s", $expires),
                ':user_id' => $user_id,
                ':scope' => $scope,
                ':refresh_token' => $refresh_token
            ]);
        }

        public function unsetRefreshToken($refresh_token) {
            return $this->oDB->query(sprintf("DELETE FROM %s WHERE refresh_token = :refresh_token", $this->aConfigOpts['tables']['refresh_tokens']), [':refresh_token' => $refresh_token]);
        }


        # Scopes $

        public function getDefaultScope($client_id = null) {
            $sqlStatement = $this->oDB->query(sprintf("SELECT scope FROM %s WHERE type='default' AND (client_id = :client_id OR client_id IS NULL) ORDER BY client_id IS NOT NULL DESC", $this->aConfigOpts['tables']['scope']), [':client_id' => $client_id]);
            if ($sqlResult = $sqlStatement->fetch()) {
                return $sqlResult['scope'];
            }

            return null;
        }

        public function scopeExists($scope, $client_id = null) {
            $scope = explode(' ', $scope);
            $sqlStatement = $this->oDB->query(sprintf("SELECT scope FROM %s WHERE type='supported' AND (client_id = :client_id OR client_id IS NULL) ORDER BY client_id IS NOT NULL DESC", $this->aConfigOpts['tables']['scope']), [':client_id' => $client_id]);

            if ($sqlResult = $sqlStatement->fetch()) {
                $supportedScope = explode(' ', $sqlResult['scope']);
            } else {
                $supportedScope = [];
            }

            return (count(array_diff($scope, $supportedScope)) == 0);
        }

    }