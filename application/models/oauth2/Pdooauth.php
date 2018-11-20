<?php

/**
 * @file
 * Sample OAuth2 Library PDO DB Implementation.
 */

//require LIB . 'OAuth2.php';

/**
 * OAuth2 Library PDO DB Implementation.
 */
class PDOOAuth extends CI_Model {


    public $table_name;
    public $db;
    public $queue;
	/**
	 * Release DB connection during destruct.
	 */
	function __destruct() {
		$this->db = NULL; // Release db connection
	}

    public function __construct($config = array())
    {
        parent::__construct();

        $this->db = $this->load->database('oauth2', true);

    }
	
	/**
	 * Handle PDO exceptional cases.
	 */
	private function handleException($e) {
		echo "Database error: " . $e->getMessage ();
		exit ();
	}

	/**
	 * Implements OAuth2::checkClientCredentials().
	 *
	 * Do NOT use this in production! This sample code stores the secret
	 * in plaintext!
	 */
	protected function checkClientCredentials($appid, $client_secret = NULL) {
		try {
			$sql = "SELECT secret FROM application WHERE apikey = :appid";
			$stmt = $this->db->prepare ( $sql );
			$stmt->bindParam ( ":appid", $appid, PDO::PARAM_STR );
			$stmt->execute ();
			
			$result = $stmt->fetch ( PDO::FETCH_ASSOC );
			
			if ($client_secret === NULL)
				return $result !== FALSE;
			
			return $result ["secret"] == $client_secret;
		} catch ( PDOException $e ) {
			$this->handleException ( $e );
		}
	}

	/**
	 * Implements OAuth2::getRedirectUri().
	 */
	protected function getRedirectUri($appid) {
		try {
			$sql = "SELECT callback_url FROM application WHERE apikey = :appid";
			$stmt = $this->db->prepare ( $sql );
			$stmt->bindParam ( ":appid", $appid, PDO::PARAM_STR );
			$stmt->execute ();
			
			$result = $stmt->fetch ( PDO::FETCH_ASSOC );
			
			if ($result === FALSE)
				return FALSE;
			
			return isset ( $result ["callback_url"] ) && $result ["callback_url"] ? $result ["callback_url"] : NULL;
		} catch ( PDOException $e ) {
			$this->handleException ( $e );
		}
	}

	/**
	 * Implements OAuth2::getRefreshToken().
	 */
	protected function getRefreshToken($refresh_token) {
		try {
			$sql = "SELECT * FROM oauth2_server_refresh_tokens WHERE token = :refresh_token";
			$stmt = $this->db->prepare ( $sql );
			$stmt->bindParam ( ":refresh_token", $refresh_token, PDO::PARAM_STR );
			$stmt->execute ();

			$result = $stmt->fetch ( PDO::FETCH_ASSOC );

			return $result !== FALSE ? $result : NULL;
		} catch ( PDOException $e ) {
			$this->handleException ( $e );
		}
	}

	/**
	 * Implements OAuth2::setRefreshToken().
	 */
	protected function setRefreshToken($refresh_token, $appid, $user_id = NULL) {
		try {
			$token = array ();

			$sql = "SELECT token FROM oauth2_server_refresh_tokens WHERE appid = :appid AND uid = :user_id";
			$stmt = $this->db->prepare ( $sql );
			$stmt->bindParam ( ":appid", $appid, PDO::PARAM_STR );
			$stmt->bindParam ( ":user_id", $user_id, PDO::PARAM_STR );
			$stmt->execute ();
			$result = $stmt->fetch ( PDO::FETCH_ASSOC );

			if (! empty ( $result ['token'] )) {
				return $result ['token'];
			}

			$sql = "INSERT INTO oauth2_server_refresh_tokens (token, appid, uid) VALUES (:refresh_token, :appid, :user_id)";
			$stmt = $this->db->prepare ( $sql );
			$expires_in = $expires + time();
			$stmt->bindParam ( ":refresh_token", $refresh_token, PDO::PARAM_STR );
			$stmt->bindParam ( ":appid", $appid, PDO::PARAM_STR );
			$stmt->bindParam ( ":user_id", $user_id, PDO::PARAM_STR );
			$stmt->execute ();

			return $refresh_token;
		} catch ( PDOException $e ) {
			$this->handleException ( $e );
		}
	}

	/**
	 * Implements OAuth2::getAccessToken().
	 */
	protected function getAccessToken($oauth_token) {
		try {
			
			$sql = "SELECT appid, expires, scope FROM oauth2_server_tokens WHERE oauth_token = :oauth_token";
			$stmt = $this->db->prepare ( $sql );
			$stmt->bindParam ( ":oauth_token", $oauth_token, PDO::PARAM_STR );
			$stmt->execute ();
			
			$result = $stmt->fetch ( PDO::FETCH_ASSOC );
			
			return $result !== FALSE ? $result : NULL;
		} catch ( PDOException $e ) {
			$this->handleException ( $e );
		}
	}

	/**
	 * Implements OAuth2::setAccessToken().
	 */
	protected function setAccessToken($oauth_token, $appid, $expires, $scope = NULL, $user_id = NULL, $grant_type='code') {
		try {
			$token = array ();
			//刷新token，更新access_token、exprise
			if($grant_type == 'refresh_token') {
				$sql = "DELETE FROM oauth2_server_tokens WHERE appid=:appid AND uid=:user_id";
				$stmt = $this->db->prepare ( $sql );
				$stmt->bindParam ( ":appid", $appid, PDO::PARAM_STR );
				$stmt->bindParam ( ":user_id", $user_id, PDO::PARAM_STR );
				$stmt->execute ();
			} else {
				$sql = "SELECT oauth_token,expires,scope FROM oauth2_server_tokens WHERE appid = :appid AND uid = :user_id";
				$stmt = $this->db->prepare ( $sql );
				$stmt->bindParam ( ":appid", $appid, PDO::PARAM_STR );
				$stmt->bindParam ( ":user_id", $user_id, PDO::PARAM_STR );
				$stmt->execute ();
				$result = $stmt->fetch ( PDO::FETCH_ASSOC );
	
				if (! empty ( $result ['oauth_token'] )) {
					$token = array (
							'access_token' => $result ['oauth_token'], 
							'expires_in' => $result ['expires'] - time () > 0 ? $result ['expires'] - time () : 0, 
							'scope' => $result ['scope'],
					);
					return $token;
				}
			}

			$sql = "INSERT INTO oauth2_server_tokens (oauth_token, appid, uid, expires, scope) VALUES (:oauth_token, :appid, :user_id, :expires, :scope)";
			$stmt = $this->db->prepare ( $sql );
			$expires_in = $expires + time();
			$stmt->bindParam ( ":oauth_token", $oauth_token, PDO::PARAM_STR );
			$stmt->bindParam ( ":appid", $appid, PDO::PARAM_STR );
			$stmt->bindParam ( ":user_id", $user_id, PDO::PARAM_STR );
			$stmt->bindParam ( ":expires", $expires_in, PDO::PARAM_INT );
			$stmt->bindParam ( ":scope", $scope, PDO::PARAM_STR );
			$stmt->execute ();

			$token = array (
				'access_token' => $oauth_token, 
				'expires_in' => $expires, 
				'scope' => $scope,
			);
			return $token;
		} catch ( PDOException $e ) {
			$this->handleException ( $e );
		}
	}
	
	/**
	 * Overrides OAuth2::getSupportedGrantTypes().
	 */
	protected function getSupportedGrantTypes() {
		return array (OAUTH2_GRANT_TYPE_AUTH_CODE, OAUTH2_GRANT_TYPE_USER_CREDENTIALS, OAUTH2_GRANT_TYPE_ASSERTION, OAUTH2_GRANT_TYPE_REFRESH_TOKEN, OAUTH2_GRANT_TYPE_NONE );
	}
	
	/**
	 * Overrides OAuth2::getAuthCode().
	 */
	protected function getAuthCode($code) {
		try {
			$sql = "SELECT code, appid, uid, redirect_uri, expires, scope FROM oauth2_server_codes WHERE code = :code";
			$stmt = $this->db->prepare ( $sql );
			$stmt->bindParam ( ":code", $code, PDO::PARAM_STR );
			$stmt->execute ();
			
			$result = $stmt->fetch ( PDO::FETCH_ASSOC );
			
			return $result !== FALSE ? $result : NULL;
		} catch ( PDOException $e ) {
			$this->handleException ( $e );
		}
	}
	
	/**
	 * Overrides OAuth2::setAuthCode().
	 */
	protected function setAuthCode($code, $appid, $redirect_uri, $expires, $scope = NULL, $user_id = NULL) {
		try {
			$sql = "INSERT INTO oauth2_server_codes (code, appid, uid, redirect_uri, expires, scope) VALUES (:code, :appid, :user_id, :redirect_uri, :expires, :scope)";
			$stmt = $this->db->prepare ( $sql );
			$stmt->bindParam ( ":code", $code, PDO::PARAM_STR );
			$stmt->bindParam ( ":appid", $appid, PDO::PARAM_STR );
			$stmt->bindParam ( ":user_id", $user_id, PDO::PARAM_STR );
			$stmt->bindParam ( ":redirect_uri", $redirect_uri, PDO::PARAM_STR );
			$stmt->bindParam ( ":expires", $expires, PDO::PARAM_INT );
			$stmt->bindParam ( ":scope", $scope, PDO::PARAM_STR );
			
			$stmt->execute ();
		} catch ( PDOException $e) {
      $this->handleException($e);
    }
  }
}
