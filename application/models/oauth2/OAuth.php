<?php
/**
 * OAuth2.0 draft v10 server-side implementation.
 *
 * @author Originally written by Tim Ridgely <tim.ridgely@gmail.com>.
 * @author Updated to draft v10 by Aaron Parecki <aaron@parecki.com>.
 * @author Debug, coding style clean up and documented by Edison Wong <hswong3i@pantarei-design.com>.
 */

//access token 默认有效期
define("OAUTH2_DEFAULT_ACCESS_TOKEN_LIFETIME", 7200); //default: 30 days

//授权过程中临时生成的code有时间
define("OAUTH2_DEFAULT_AUTH_CODE_LIFETIME", 6000); //default:30s

//REFRESH_TOKEN 的默认有效期
define("OAUTH2_DEFAULT_REFRESH_TOKEN_LIFETIME", 3600); //default: 1209600s 15 days

define("OAUTH2_CLIENT_ID_REGEXP", "/^[a-z0-9-_]{3,32}$/i");

/**
 * Denotes "token" authorization response type.
 */
define("OAUTH2_AUTH_RESPONSE_TYPE_ACCESS_TOKEN", "token");

/**
 * Denotes "code" authorization response type.
 */
define("OAUTH2_AUTH_RESPONSE_TYPE_AUTH_CODE", "code");

/**
 * Denotes "code-and-token" authorization response type.
 */
define("OAUTH2_AUTH_RESPONSE_TYPE_CODE_AND_TOKEN", "code-and-token");

/**
 * Regex to filter out the authorization response type.
 */
define("OAUTH2_AUTH_RESPONSE_TYPE_REGEXP", "/^(token|code|code-and-token)$/");

/**
 * Denotes "authorization_code" grant types (for token obtaining).
 */
define("OAUTH2_GRANT_TYPE_AUTH_CODE", "authorization_code");

/**
 * Denotes "password" grant types (for token obtaining).
 */
define("OAUTH2_GRANT_TYPE_USER_CREDENTIALS", "password");

/**
 * Denotes "assertion" grant types (for token obtaining).
 */
define("OAUTH2_GRANT_TYPE_ASSERTION", "assertion");


define("OAUTH2_GRANT_TYPE_APP", "app");

/**
 * Denotes "refresh_token" grant types (for token obtaining).
 */
define("OAUTH2_GRANT_TYPE_REFRESH_TOKEN", "refresh_token");

/**
 * Denotes "none" grant types (for token obtaining).
 */
define("OAUTH2_GRANT_TYPE_NONE", "none");

/**
 * Regex to filter out the grant type.
 */
define("OAUTH2_GRANT_TYPE_REGEXP", "/^(authorization_code|password|assertion|refresh_token|none)$/");

define("OAUTH2_TOKEN_PARAM_NAME", "oauth_token");

define("OAUTH2_HTTP_FOUND", "302 Found");

define("OAUTH2_HTTP_BAD_REQUEST", "400 Bad Request");

define("OAUTH2_HTTP_UNAUTHORIZED", "401 Unauthorized");

define("OAUTH2_HTTP_FORBIDDEN", "403 Forbidden");

define("OAUTH2_ERROR_INVALID_REQUEST", "invalid_request");

define("OAUTH2_ERROR_INVALID_CLIENT", "invalid_client");

define("OAUTH2_ERROR_UNAUTHORIZED_CLIENT", "unauthorized_client");

define("OAUTH2_ERROR_REDIRECT_URI_MISMATCH", "redirect_uri_mismatch");

define("OAUTH2_ERROR_USER_DENIED", "access_denied");

define("OAUTH2_ERROR_UNSUPPORTED_RESPONSE_TYPE", "unsupported_response_type");

define("OAUTH2_ERROR_INVALID_SCOPE", "invalid_scope");

define("OAUTH2_ERROR_INVALID_GRANT", "invalid_grant");

define("OAUTH2_ERROR_UNSUPPORTED_GRANT_TYPE", "unsupported_grant_type");

define("OAUTH2_ERROR_INVALID_TOKEN", "invalid_token");

define("OAUTH2_ERROR_EXPIRED_TOKEN", "expired_token");

define("OAUTH2_ERROR_INSUFFICIENT_SCOPE", "insufficient_scope");

class OAuth extends CI_Model {

	/**
	 * Array of persistent variables stored.
	 */
	protected $conf = array();

	/**
	 * Returns a persistent variable.
	 *
	 * To avoid problems, always use lower case for persistent variable names.
	 *
	 * @param $name
	 * The name of the variable to return.
	 * @param $default
	 * The default value to use if this variable has never been set.
	 *
	 * @return
	 * The value of the variable.
	 */
	public function getVariable($name, $default = NULL) {
		return isset($this->conf [$name]) ? $this->conf [$name] : $default;
	}

	/**
	 * Sets a persistent variable.
	 *
	 * To avoid problems, always use lower case for persistent variable names.
	 *
	 * @param $name
	 * The name of the variable to set.
	 * @param $value
	 * The value to set.
	 */
	public function setVariable($name, $value) {
		$this->conf [$name] = $value;
		return $this;
	}

	/**
	 * Return supported authorization response types.
	 *
	 * You should override this function with your supported response types.
	 *
	 * @return
	 * A list as below. If you support all authorization response types,
	 * then you'd do:
	 * @code
	 * return array(
	 * OAUTH2_AUTH_RESPONSE_TYPE_AUTH_CODE,
	 * OAUTH2_AUTH_RESPONSE_TYPE_ACCESS_TOKEN,
	 * OAUTH2_AUTH_RESPONSE_TYPE_CODE_AND_TOKEN,
	 * );
	 * @endcode
	 *
	 * @ingroup oauth2_section_3
	 */
	protected function getSupportedAuthResponseTypes() {
		return array(OAUTH2_AUTH_RESPONSE_TYPE_AUTH_CODE, OAUTH2_AUTH_RESPONSE_TYPE_ACCESS_TOKEN, OAUTH2_AUTH_RESPONSE_TYPE_CODE_AND_TOKEN);
	}

	/**
	 * Return supported scopes.
	 *
	 * If you want to support scope use, then have this function return a list
	 * of all acceptable scopes (used to throw the invalid-scope error).
	 *
	 * @return
	 * A list as below, for example:
	 * @code
	 * return array(
	 * 'my-friends',
	 * 'photos',
	 * 'whatever-else',
	 * );
	 * @endcode
	 *
	 * @ingroup oauth2_section_3
	 */
	protected function getSupportedScopes() {
		return array();
	}

	/**
	 * Check restricted authorization response types of corresponding Client
	 * identifier.
	 *
	 * If you want to restrict clients to certain authorization response types,
	 * override this function.
	 *
	 * @param $appid
	 * Client identifier to be check with.
	 * @param $response_type
	 * Authorization response type to be check with, would be one of the
	 * values contained in OAUTH2_AUTH_RESPONSE_TYPE_REGEXP.
	 *
	 * @return
	 * TRUE if the authorization response type is supported by this
	 * client identifier, and FALSE if it isn't.
	 *
	 * @ingroup oauth2_section_3
	 */
	protected function checkRestrictedAuthResponseType($appid, $response_type) {
		return TRUE;
	}

	/**
	 * Check restricted grant types of corresponding client identifier.
	 *
	 * If you want to restrict clients to certain grant types, override this
	 * function.
	 *
	 * @param $appid
	 * Client identifier to be check with.
	 * @param $grant_type
	 * Grant type to be check with, would be one of the values contained in
	 * OAUTH2_GRANT_TYPE_REGEXP.
	 *
	 * @return
	 * TRUE if the grant type is supported by this client identifier, and
	 * FALSE if it isn't.
	 *
	 * @ingroup oauth2_section_4
	 */
	protected function checkRestrictedGrantType($appid, $grant_type) {
		return TRUE;
	}

	// Functions that help grant access tokens for various grant types.

	/**
	 * Grant access tokens for basic user credentials.
	 *
	 * Check the supplied username and password for validity.
	 *
	 * You can also use the $appid param to do any checks required based
	 * on a client, if you need that.
	 *
	 * Required for OAUTH2_GRANT_TYPE_USER_CREDENTIALS.
	 *
	 * @param $appid
	 * Client identifier to be check with.
	 * @param $username
	 * Username to be check with.
	 * @param $password
	 * Password to be check with.
	 *
	 * @return
	 * TRUE if the username and password are valid, and FALSE if it isn't.
	 * Moreover, if the username and password are valid, and you want to
	 * verify the scope of a user's access, return an associative array
	 * with the scope values as below. We'll check the scope you provide
	 * against the requested scope before providing an access token:
	 * @code
	 * return array(
	 * 'scope' => <stored scope values (space-separated string)>,
	 * );
	 * @endcode
	 *
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4.1.2
	 *
	 * @ingroup oauth2_section_4
	 */
	protected function checkUserCredentials($appid, $username, $password){

	    $password = MD5($appid.'_'.$password);

	    $sql = "SELECT user_id,scope FROM oauth2_users WHERE app_key='{$appid}' and lower(username)='{$username}' and password = '{$password}'" ;

        $q = $this->db->query($sql);

		if ( empty($q) ) return false;
		$r = $q->row_array();
		if ( empty($r) ) return false;

		return $r;

	}

	protected function checkClientAuthorized($appid, $username, $password){

        $username = strtolower($username);
        $password = MD5($appid.'_'.$password);

        $sql = "SELECT user_id, openid FROM oauth2_users WHERE app_key='{$appid}' and lower(username)='{$username}' and password = '{$password}'" ;

        $q = $this->db->query($sql);

        if ( empty($q) ) return false;
        $r = $q->row_array();
        if ( empty($r) ) return false;

        return $r;
    }

    protected function checkAppCredentials($appid, $openid){

        $sql = "SELECT user_id,scope FROM oauth2_users WHERE app_key='{$appid}' and openid='{$openid}' " ;

        $q = $this->db->query($sql);

        if ( empty($q) ) return false;
        $r = $q->row_array();
        if ( empty($r) ) return false;

        return $r;

    }
	/**
	 * Grant access tokens for assertions.
	 *
	 * Check the supplied assertion for validity.
	 *
	 * You can also use the $appid param to do any checks required based
	 * on a client, if you need that.
	 *
	 * Required for OAUTH2_GRANT_TYPE_ASSERTION.
	 *
	 * @param $appid
	 * Client identifier to be check with.
	 * @param $assertion_type
	 * The format of the assertion as defined by the authorization server.
	 * @param $assertion
	 * The assertion.
	 *
	 * @return
	 * TRUE if the assertion is valid, and FALSE if it isn't. Moreover, if
	 * the assertion is valid, and you want to verify the scope of an access
	 * request, return an associative array with the scope values as below.
	 * We'll check the scope you provide against the requested scope before
	 * providing an access token:
	 * @code
	 * return array(
	 * 'scope' => <stored scope values (space-separated string)>,
	 * );
	 * @endcode
	 *
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4.1.3
	 *
	 * @ingroup oauth2_section_4
	 */
	protected function checkAssertion($appid, $assertion_type, $assertion) {
		return FALSE;
	}

	/**
	 * Expire a used refresh token.
	 *
	 * This is not explicitly required in the spec, but is almost implied.
	 * After granting a new refresh token, the old one is no longer useful and
	 * so should be forcibly expired in the data store so it can't be used again.
	 *
	 * If storage fails for some reason, we're not currently checking for
	 * any sort of success/failure, so you should bail out of the script
	 * and provide a descriptive fail message.
	 *
	 * @param $refresh_token
	 * Refresh token to be expirse.
	 *
	 * @ingroup oauth2_section_4
	 */
	protected function unsetRefreshToken($refresh_token) {
		return;
	}

	/**
	 * Grant access tokens for the "none" grant type.
	 *
	 * Not really described in the IETF Draft, so I just left a method
	 * stub... Do whatever you want!
	 *
	 * Required for OAUTH2_GRANT_TYPE_NONE.
	 *
	 * @ingroup oauth2_section_4
	 */
	protected function checkNoneAccess($appid) {
		return FALSE;
	}

	/**
	 * Get default authentication realm for WWW-Authenticate header.
	 *
	 * Change this to whatever authentication realm you want to send in a
	 * WWW-Authenticate header.
	 *
	 * @return
	 * A string that you want to send in a WWW-Authenticate header.
	 *
	 * @ingroup oauth2_error
	 */
	protected function getDefaultAuthenticationRealm() {
		return "Service";
	}

	// End stuff that should get overridden.

    public $table_name;
    public $db;
    public $queue;

	/**
	 * Creates an OAuth2.0 server-side instance.
	 *
	 * @param $config
	 * An associative array as below:
	 * - access_token_lifetime: (optional) The lifetime of access token in
	 * seconds.
	 * - auth_code_lifetime: (optional) The lifetime of authorization code in
	 * seconds.
	 * - refresh_token_lifetime: (optional) The lifetime of refresh token in
	 * seconds.
	 * - display_error: (optional) Whether to show verbose error messages in
	 * the response.
	 */
	public function __construct($config = array()) {
		parent::__construct();

        $this->db = $this->load->database('oauth2', true);

		foreach ($config as $name => $value) {
			$this->setVariable($name, $value);
		}
	}

	// Resource protecting (Section 5).

	/**
	 * Check that a valid access token has been provided.
	 *
	 * The scope parameter defines any required scope that the token must have.
	 * If a scope param is provided and the token does not have the required
	 * scope, we bounce the request.
	 *
	 * Some implementations may choose to return a subset of the protected
	 * resource (i.e. "public" data) if the user has not provided an access
	 * token or if the access token is invalid or expired.
	 *
	 * The IETF spec says that we should send a 401 Unauthorized header and
	 * bail immediately so that's what the defaults are set to.
	 *
	 * @param $scope
	 * A space-separated string of required scope(s), if you want to check
	 * for scope.
	 * @param $exit_not_present
	 * If TRUE and no access token is provided, send a 401 header and exit,
	 * otherwise return FALSE.
	 * @param $exit_invalid
	 * If TRUE and the implementation of getAccessToken() returns NULL, exit,
	 * otherwise return FALSE.
	 * @param $exit_expired
	 * If TRUE and the access token has expired, exit, otherwise return FALSE.
	 * @param $exit_scope
	 * If TRUE the access token does not have the required scope(s), exit,
	 * otherwise return FALSE.
	 * @param $realm
	 * If you want to specify a particular realm for the WWW-Authenticate
	 * header, supply it here.
	 *
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-5
	 *
	 * @ingroup oauth2_section_5
	 */
	public function verifyAccessToken($scope = NULL, $exit_not_present = TRUE, $exit_invalid = TRUE, $exit_expired = TRUE, $exit_scope = TRUE, $realm = NULL) {
		$token_param = $this->getAccessTokenParams();
		if ($token_param === FALSE) // Access token was not provided
			return $exit_not_present ? $this->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_BAD_REQUEST, $realm, OAUTH2_ERROR_INVALID_REQUEST, 'The request is missing a required parameter, includes an unsupported parameter or parameter value, repeats the same parameter, uses more than one method for including an access token, or is otherwise malformed.', NULL, $scope) : FALSE;

		// Get the stored token data (from the implementing subclass)
		$token = $this->getAccessToken($token_param);
		if ($token === NULL)
			return $exit_invalid ? $this->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_UNAUTHORIZED, $realm, OAUTH2_ERROR_INVALID_TOKEN, 'The access token provided is invalid.', NULL, $scope) : FALSE;

		// Check token expiration (I'm leaving this check separated, later we'll fill in better error messages)
		if (isset($token ["expires"]) && time() > $token ["expires"])
			return $exit_expired ? $this->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_UNAUTHORIZED, $realm, OAUTH2_ERROR_EXPIRED_TOKEN, 'The access token provided has expired.', NULL, $scope) : FALSE;

		// Check scope, if provided
		// If token doesn't have a scope, it's NULL/empty, or it's insufficient, then throw an error
		if ($scope && (!isset($token ["scope"]) || !$token ["scope"] || !$this->checkScope($scope, $token ["scope"])))
			return $exit_scope ? $this->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_FORBIDDEN, $realm, OAUTH2_ERROR_INSUFFICIENT_SCOPE, 'The request requires higher privileges than provided by the access token.', NULL, $scope) : FALSE;

		return TRUE;
	}

	/**
	 * Check if everything in required scope is contained in available scope.
	 *
	 * @param $required_scope
	 * Required scope to be check with.
	 * @param $available_scope
	 * Available scope to be compare with.
	 *
	 * @return
	 * TRUE if everything in required scope is contained in available scope,
	 * and False if it isn't.
	 *
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-5
	 *
	 * @ingroup oauth2_section_5
	 */
	private function checkScope($required_scope, $available_scope) {
		// The required scope should match or be a subset of the available scope
		if (!is_array($required_scope))
			$required_scope = explode(" ", $required_scope);

		if (!is_array($available_scope))
			$available_scope = explode(" ", $available_scope);

		return (count(array_diff($required_scope, $available_scope)) == 0);
	}

	/**
	 * Pulls the access token out of the HTTP request.
	 *
	 * Either from the Authorization header or GET/POST/etc.
	 *
	 * @return
	 * Access token value if present, and FALSE if it isn't.
	 *
	 * @todo Support PUT or DELETE.
	 *
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-5.1
	 *
	 * @ingroup oauth2_section_5
	 */
	private function getAccessTokenParams() {
		$auth_header = $this->getAuthorizationHeader();

		if ($auth_header !== FALSE) {
			// Make sure only the auth header is set
			if (isset($_GET [OAUTH2_TOKEN_PARAM_NAME]) || isset($_REQUEST [OAUTH2_TOKEN_PARAM_NAME]))
				$this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'Auth token found in GET or POST when token present in header');

			$auth_header = trim($auth_header);

			// Make sure it's Token authorization
			if (strcmp(substr($auth_header, 0, 5), "OAuth ") !== 0)
				$this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'Auth header found that doesn\'t start with "OAuth"');

			// Parse the rest of the header
			if (preg_match('/\s*OAuth\s*="(.+)"/', substr($auth_header, 5), $matches) == 0 || count($matches) < 2)
				$this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'Malformed auth header');

			return $matches [1];
		}

		if (isset($_GET [OAUTH2_TOKEN_PARAM_NAME])) {
			if (isset($_REQUEST [OAUTH2_TOKEN_PARAM_NAME])) // Both GET and POST are not allowed
				$this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'Only send the token in GET or POST, not both');

			return $_GET [OAUTH2_TOKEN_PARAM_NAME];
		}

		if (isset($_REQUEST [OAUTH2_TOKEN_PARAM_NAME]))
			return $_REQUEST [OAUTH2_TOKEN_PARAM_NAME];

		return FALSE;
	}

	// Access token granting (Section 4).

	/**
	 * code换取access_token，刷新token
	 * Grant or deny a requested access token.
	 *
	 * This would be called from the "/token" endpoint as defined in the spec.
	 * Obviously, you can call your endpoint whatever you want.
	 *
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4
	 *
	 * @ingroup oauth2_section_4
	 */
	public function grantAccessToken() {


		$filters = array("grant_type" => array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => OAUTH2_GRANT_TYPE_REGEXP), "flags" => FILTER_REQUIRE_SCALAR), "scope" => array("flags" => FILTER_REQUIRE_SCALAR), "code" => array("flags" => FILTER_REQUIRE_SCALAR), "redirect_uri" => array("filter" => FILTER_SANITIZE_URL), "username" => array("flags" => FILTER_REQUIRE_SCALAR), "password" => array("flags" => FILTER_REQUIRE_SCALAR), "assertion_type" => array("flags" => FILTER_REQUIRE_SCALAR), "assertion" => array("flags" => FILTER_REQUIRE_SCALAR), "refresh_token" => array("flags" => FILTER_REQUIRE_SCALAR));

		$input = filter_input_array(INPUT_GET, $filters);

        $input["grant_type"] = $_REQUEST["grant_type"];
        $input["appid"] = $_REQUEST["appid"];
	    $input["secret"] = $_REQUEST["secret"];

        if( $input["grant_type"] === OAUTH2_GRANT_TYPE_APP)
            $input["openid"] = $_REQUEST["openid"];



		// Grant Type must be specified.
		if (!$input["grant_type"])
			$this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'Invalid grant_type parameter or parameter missing');

        // Make sure we've implemented the requested grant type
		if (!in_array($input["grant_type"], $this->getSupportedGrantTypes()))
			$this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_UNSUPPORTED_GRANT_TYPE);

		// Authorize the client
		$client = $this->getClientCredentials();

		if ($this->checkClientCredentials($client [0], $client [1]) === FALSE)
			$this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_CLIENT);

        //判断 app_key 是否允许 grant_type 登录
		if (!$this->checkRestrictedGrantType($client [0], $input["grant_type"]))
			$this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_UNAUTHORIZED_CLIENT);

		// Do the granting
		switch ($input["grant_type"]) {
			case OAUTH2_GRANT_TYPE_AUTH_CODE ://code换取access_token
				if (!$input ["code"] || !$input ["redirect_uri"])
					//$this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST);

				$stored = $this->getAuthCode($input ["code"]);

				// Ensure that the input uri starts with the stored uri
				if ($stored === NULL || $client [0] != $stored ["app_key"])
					$this->errorJsonResponse(OAUTH2_HTTP_UNAUTHORIZED, OAUTH2_ERROR_INVALID_GRANT);

				if ($stored ["expires"] < time())
					$this->errorJsonResponse(OAUTH2_ERROR_EXPIRED_TOKEN, OAUTH2_ERROR_EXPIRED_TOKEN);

				break;

			case OAUTH2_GRANT_TYPE_USER_CREDENTIALS :

				if (!$input ["username"] || !$input ["password"])
					$this->errorJsonResponse(OAUTH2_HTTP_UNAUTHORIZED, OAUTH2_ERROR_INVALID_REQUEST, 'Missing parameters. "username" and "password" required');

				$stored = $this->checkUserCredentials($client [0], $input ["username"], $input ["password"]);

				if ($stored === FALSE)
					$this->errorJsonResponse(OAUTH2_HTTP_UNAUTHORIZED, OAUTH2_ERROR_INVALID_GRANT,' "username" or "password" is invalid');

				break;

            case OAUTH2_GRANT_TYPE_APP:

                if(!$input['openid'])
                    $this->errorJsonResponse(OAUTH2_HTTP_UNAUTHORIZED, OAUTH2_ERROR_INVALID_REQUEST, 'Missing parameters. "openid" of app  required');

                $stored = $this->checkAppCredentials($client [0], $input ["openid"]);

                if ($stored === FALSE)
                    $this->errorJsonResponse(OAUTH2_HTTP_UNAUTHORIZED, OAUTH2_ERROR_INVALID_GRANT,' "openid" non-authorized');

                break;

			case OAUTH2_GRANT_TYPE_ASSERTION :
				if (!$input ["assertion_type"] || !$input ["assertion"])
					$this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST);

				$stored = $this->checkAssertion($client [0], $input ["assertion_type"], $input ["assertion"]);

				if ($stored === FALSE)
					$this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_GRANT);

				break;
			case OAUTH2_GRANT_TYPE_REFRESH_TOKEN : //刷新token
				if (!$input ["refresh_token"])
					$this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'No "refresh_token" parameter found');

				$stored = $this->getRefreshToken($input ["refresh_token"]);

				if ($stored === NULL || $client [0] != $stored ["appkey"])//比对appkey
					$this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_GRANT);

				//refresh token永远不过期，跳过检查
				//if ($stored ["expires"] < time ())//检查是否过期
					//$this->errorJsonResponse ( OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_EXPIRED_TOKEN );

				// store the refresh token locally so we can delete it when a new refresh token is generated
				$this->setVariable ( '_old_refresh_token', $stored ["refresh_token"] );

				break;
			case OAUTH2_GRANT_TYPE_NONE :
				$stored = $this->checkNoneAccess($client [0]);

				if ($stored === FALSE)
					$this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST);
		}

		// Check scope, if provided
		if ($input ["scope"] && (!is_array($stored) || !isset($stored ["scope"]) || !$this->checkScope($input ["scope"], $stored ["scope"])))
			$this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_SCOPE);

		if (!$input ["scope"])
			$input ["scope"] = NULL;

		if (!isset($stored["scope"]))
			$stored["scope"] = '';

		$token = $this->createAccessToken($client[0], $stored["scope"], $stored['user_id'], $input["grant_type"]);
		return $token;
	}

	/**
	 * Internal function used to get the client credentials from HTTP basic
	 * auth or POST data.
	 *
	 * @return
	 * A list containing the client identifier and password, for example
	 * @code
	 * return array(
	 * $_REQUEST["appid"],
	 * $_REQUEST["client_secret"],
	 * );
	 * @endcode
	 *
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-2
	 *
	 * @ingroup oauth2_section_2
	 */
	protected function getClientCredentials() {
		if (isset($_SERVER ["PHP_AUTH_USER"]) && $_REQUEST && isset($_REQUEST ["appid"]))
			$this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_CLIENT);

		// Try basic auth
		if (isset($_SERVER ["PHP_AUTH_USER"]))
			return array($_SERVER ["PHP_AUTH_USER"], $_SERVER ["PHP_AUTH_PW"]);

		// Try POST
		if ($_REQUEST && isset($_REQUEST ["appid"])) {
			if (isset($_REQUEST ["secret"]))
				return array($_REQUEST ["appid"], $_REQUEST ["secret"]);

			return array($_REQUEST ["appid"], NULL);
		}

		// No credentials were specified
		$this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_CLIENT);
	}

	// End-user/client Authorization (Section 3 of IETF Draft).

	/**
	 * Pull the authorization request data out of the HTTP request.
	 *
	 * @return
	 * The authorization parameters so the authorization server can prompt
	 * the user for approval if valid.
	 *
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-3
	 *
	 * @ingroup oauth2_section_3
	 */
	public function getAuthorizeParams() {
		$filters = array("appid" => array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => OAUTH2_CLIENT_ID_REGEXP), "flags" => FILTER_REQUIRE_SCALAR), "response_type" => array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => OAUTH2_AUTH_RESPONSE_TYPE_REGEXP), "flags" => FILTER_REQUIRE_SCALAR), "redirect_uri" => array("filter" => FILTER_SANITIZE_URL), "state" => array("flags" => FILTER_REQUIRE_SCALAR), "scope" => array("flags" => FILTER_REQUIRE_SCALAR));

		$input = filter_input_array(INPUT_GET, $filters);

		// Make sure a valid client id was supplied
		if (!$input ["appid"]) {
			if ($input ["redirect_uri"])
				$this->errorDoRedirectUriCallback($input ["redirect_uri"], OAUTH2_ERROR_INVALID_CLIENT, NULL, NULL, $input ["state"]);

			$this->errorJsonResponse(OAUTH2_HTTP_FOUND, OAUTH2_ERROR_INVALID_CLIENT); // We don't have a good URI to use
		}

		// redirect_uri is not required if already established via other channels
		// check an existing redirect URI against the one supplied
		$redirect_uri = $this->getRedirectUri($input ["appid"]);

		// At least one of: existing redirect URI or input redirect URI must be specified
		if (!$redirect_uri && !$input ["redirect_uri"])
			$this->errorJsonResponse(OAUTH2_HTTP_FOUND, OAUTH2_ERROR_INVALID_REQUEST);

		// getRedirectUri() should return FALSE if the given client ID is invalid
		// this probably saves us from making a separate db call, and simplifies the method set
		if ($redirect_uri === FALSE)
			$this->errorDoRedirectUriCallback($input ["redirect_uri"], OAUTH2_ERROR_INVALID_CLIENT, NULL, NULL, $input ["state"]);

		// If there's an existing uri and one from input, verify that they match
		if ($redirect_uri && $input ["redirect_uri"]) {
			// Ensure that the input uri starts with the stored uri
			if (strcasecmp(substr($input ["redirect_uri"], 0, strlen($redirect_uri)), $redirect_uri) !== 0)
				$this->errorDoRedirectUriCallback($input ["redirect_uri"], OAUTH2_ERROR_REDIRECT_URI_MISMATCH, NULL, NULL, $input ["state"]);
		} elseif ($redirect_uri) { // They did not provide a uri from input, so use the stored one
			$input ["redirect_uri"] = $redirect_uri;
		}

		// type and appid are required
		if (!$input ["response_type"])
			$this->errorDoRedirectUriCallback($input ["redirect_uri"], OAUTH2_ERROR_INVALID_REQUEST, 'Invalid response type.', NULL, $input ["state"]);

		// Check requested auth response type against the list of supported types
		if (array_search($input ["response_type"], $this->getSupportedAuthResponseTypes()) === FALSE)
			$this->errorDoRedirectUriCallback($input ["redirect_uri"], OAUTH2_ERROR_UNSUPPORTED_RESPONSE_TYPE, NULL, NULL, $input ["state"]);

		// Restrict clients to certain authorization response types
		if ($this->checkRestrictedAuthResponseType($input ["appid"], $input ["response_type"]) === FALSE)
			$this->errorDoRedirectUriCallback($input ["redirect_uri"], OAUTH2_ERROR_UNAUTHORIZED_CLIENT, NULL, NULL, $input ["state"]);

		// Validate that the requested scope is supported
		if ($input ["scope"] && !$this->checkScope($input ["scope"], $this->getSupportedScopes()))
			$this->errorDoRedirectUriCallback($input ["redirect_uri"], OAUTH2_ERROR_INVALID_SCOPE, NULL, NULL, $input ["state"]);

		return $input;
	}

	/**
	 * Redirect the user appropriately after approval.
	 *
	 * After the user has approved or denied the access request the
	 * authorization server should call this function to redirect the user
	 * appropriately.
	 *
	 * @param $is_authorized
	 * TRUE or FALSE depending on whether the user authorized the access.
	 * @param $params
	 * An associative array as below:
	 * - response_type: The requested response: an access token, an
	 * authorization code, or both.
	 * - appid: The client identifier as described in Section 2.
	 * - redirect_uri: An absolute URI to which the authorization server
	 * will redirect the user-agent to when the end-user authorization
	 * step is completed.
	 * - scope: (optional) The scope of the access request expressed as a
	 * list of space-delimited strings.
	 * - state: (optional) An opaque value used by the client to maintain
	 * state between the request and callback.
	 *
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-3
	 *
	 * @ingroup oauth2_section_3
	 */
	public function finishClientAuthorization($is_authorized, $params = array(), $user_id) {
		$params += array('scope' => NULL, 'state' => NULL);
		extract($params);

		if ($is_authorized === FALSE) {

            $result ["query"] ["code"] = -1;
			$result ["query"] ["msg"] = OAUTH2_ERROR_USER_DENIED;

		} else {
			if ($response_type == OAUTH2_AUTH_RESPONSE_TYPE_AUTH_CODE || $response_type == OAUTH2_AUTH_RESPONSE_TYPE_CODE_AND_TOKEN)
				$result ["query"] ["code"] = $this->createAuthCode($appid, $redirect_uri, $scope, $user_id);

			if ($response_type == OAUTH2_AUTH_RESPONSE_TYPE_ACCESS_TOKEN || $response_type == OAUTH2_AUTH_RESPONSE_TYPE_CODE_AND_TOKEN)
				$result ["fragment"] = $this->createAccessToken($appid, $scope);
		}

		return $result;
	}

    public function finishAppAuthorization($username, $password, $appid, $secret, $openid) {

        if ($this->checkClientCredentials($appid, $secret) === FALSE)
            $this->errorJsonResponse(OAUTH2_HTTP_UNAUTHORIZED, OAUTH2_ERROR_INVALID_CLIENT);

        $user = $this->checkClientAuthorized($appid, $username, $password);

        if(!isset($user) || $user === false)
            $this->errorJsonResponse(OAUTH2_HTTP_UNAUTHORIZED, OAUTH2_ERROR_UNAUTHORIZED_CLIENT);

        if( $user["openid"] === $openid)
            output(array("code" => 0 , "msg" => "OpenId had binded."));


        return $this->setClientOpenId($appid, $user["user_id"], $openid);

    }

	// Other/utility functions.

	/**
	 * Redirect the user agent.
	 *
	 * Handle both redirect for success or error response.
	 *
	 * @param $redirect_uri
	 * An absolute URI to which the authorization server will redirect
	 * the user-agent to when the end-user authorization step is completed.
	 * @param $params
	 * Parameters to be pass though buildUri().
	 *
	 * @ingroup oauth2_section_3
	 */
	private function doRedirectUriCallback($redirect_uri, $params) {
		header("HTTP/1.1 " . OAUTH2_HTTP_FOUND);
		header("Location: " . $this->buildUri($redirect_uri, $params));
		exit();
	}

	/**
	 * Build the absolute URI based on supplied URI and parameters.
	 *
	 * @param $uri
	 * An absolute URI.
	 * @param $params
	 * Parameters to be append as GET.
	 *
	 * @return
	 * An absolute URI with supplied parameters.
	 *
	 * @ingroup oauth2_section_3
	 */
	private function buildUri($uri, $params) {
		$parse_url = parse_url($uri);

		// Add our params to the parsed uri
		foreach ($params as $k => $v) {
			if (isset($parse_url [$k]))
				$parse_url [$k] .= "&" . http_build_query($v);
			else
				$parse_url [$k] = http_build_query($v);
		}

		// Put humpty dumpty back together
		return ((isset($parse_url ["scheme"])) ? $parse_url ["scheme"] . "://" : "") . ((isset($parse_url ["user"])) ? $parse_url ["user"] . ((isset($parse_url ["pass"])) ? ":" . $parse_url ["pass"] : "") . "@" : "") . ((isset($parse_url ["host"])) ? $parse_url ["host"] : "") . ((isset($parse_url ["port"])) ? ":" . $parse_url ["port"] : "") . ((isset($parse_url ["path"])) ? $parse_url ["path"] : "") . ((isset($parse_url ["query"])) ? "?" . $parse_url ["query"] : "") . ((isset($parse_url ["fragment"])) ? "#" . $parse_url ["fragment"] : "");
	}

	/**
	 * Handle the creation of access token, also issue refresh token if support.
	 *
	 * This belongs in a separate factory, but to keep it simple, I'm just
	 * keeping it here.
	 *
	 * @param $appid
	 * Client identifier related to the access token.
	 * @param $scope
	 * (optional) Scopes to be stored in space-separated string.
	 *
	 * @ingroup oauth2_section_4
	 */
	protected function createAccessToken($appid, $scope = NULL, $user_id = NULL, $grant_type = 'code') {

		$expires_in = $this->getVariable('access_token_lifetime', OAUTH2_DEFAULT_ACCESS_TOKEN_LIFETIME);

		$access_token = $this->genAccessToken();
		$openid = $this->genOpenid();

		$token = $this->setAccessToken($access_token, $openid, $appid, $expires_in, $scope, $user_id, $grant_type);

		// Issue a refresh token also, if we support them
		if (in_array(OAUTH2_GRANT_TYPE_REFRESH_TOKEN, $this->getSupportedGrantTypes())) {
			$token ['refresh_token'] = $this->setRefreshToken($this->genAccessToken(),$openid, $appid, $user_id);
			// If we've granted a new refresh token, expire the old one
			if ($this->getVariable ( '_old_refresh_token' ))
				$this->unsetRefreshToken ( $this->getVariable ( '_old_refresh_token' ) );
		}

		return $token;
	}

	/**
	 * Handle the creation of auth code.
	 *
	 * This belongs in a separate factory, but to keep it simple, I'm just
	 * keeping it here.
	 *
	 * @param $appid
	 * Client identifier related to the access token.
	 * @param $redirect_uri
	 * An absolute URI to which the authorization server will redirect the
	 * user-agent to when the end-user authorization step is completed.
	 * @param $scope
	 * (optional) Scopes to be stored in space-separated string.
	 *
	 * @ingroup oauth2_section_3
	 */
	private function createAuthCode($appid, $redirect_uri, $scope = NULL, $user_id = NULL) {
		$code = $this->genAuthCode();
		$expires = time() + $this->getVariable('auth_code_lifetime', OAUTH2_DEFAULT_AUTH_CODE_LIFETIME);
		$this->setAuthCode($code, $appid, $redirect_uri, $expires, $scope, $user_id);
		return $code;
	}

	/**
	 * Generate unique access token.
	 *
	 * Implementing classes may want to override these function to implement
	 * other access token or auth code generation schemes.
	 *
	 * @return
	 * An unique access token.
	 *
	 * @ingroup oauth2_section_4
	 */
	protected function genAccessToken() {
		return md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
	}

	/**
	 * 生成openid
	 */
	protected function genOpenid() {
		return md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
	}

	/**
	 * Generate unique auth code.
	 *
	 * Implementing classes may want to override these function to implement
	 * other access token or auth code generation schemes.
	 *
	 * @return
	 * An unique auth code.
	 *
	 * @ingroup oauth2_section_3
	 */
	protected function genAuthCode() {
		return md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
	}

	/**
	 * Pull out the Authorization HTTP header and return it.
	 *
	 * Implementing classes may need to override this function for use on
	 * non-Apache web servers.
	 *
	 * @return
	 * The Authorization HTTP header, and FALSE if does not exist.
	 *
	 * @todo Handle Authorization HTTP header for non-Apache web servers.
	 *
	 * @ingroup oauth2_section_5
	 */
	private function getAuthorizationHeader() {
		if (array_key_exists("HTTP_AUTHORIZATION", $_SERVER))
			return $_SERVER ["HTTP_AUTHORIZATION"];

		if (function_exists("apache_request_headers")) {
			$headers = apache_request_headers();

			if (array_key_exists("Authorization", $headers))
				return $headers ["Authorization"];
		}

		return FALSE;
	}

	/**
	 * Send out HTTP headers for JSON.
	 *
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4.2
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4.3
	 *
	 * @ingroup oauth2_section_4
	 */
	private function sendJsonHeaders() {
		header("Content-Type: application/json");
		header("Cache-Control: no-store");
	}

	/**
	 * Redirect the end-user's user agent with error message.
	 *
	 * @param $redirect_uri
	 * An absolute URI to which the authorization server will redirect the
	 * user-agent to when the end-user authorization step is completed.
	 * @param $error
	 * A single error code as described in Section 3.2.1.
	 * @param $error_description
	 * (optional) A human-readable text providing additional information,
	 * used to assist in the understanding and resolution of the error
	 * occurred.
	 * @param $error_uri
	 * (optional) A URI identifying a human-readable web page with
	 * information about the error, used to provide the end-user with
	 * additional information about the error.
	 * @param $state
	 * (optional) REQUIRED if the "state" parameter was present in the client
	 * authorization request. Set to the exact value received from the client.
	 *
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-3.2
	 *
	 * @ingroup oauth2_error
	 */
	private function errorDoRedirectUriCallback($redirect_uri, $error, $error_description = NULL, $error_uri = NULL, $state = NULL) {
		$result ["query"] ["error"] = $error;

		if ($state)
			$result ["query"] ["state"] = $state;

		if ($this->getVariable('display_error') && $error_description)
			$result ["query"] ["error_description"] = $error_description;

		if ($this->getVariable('display_error') && $error_uri)
			$result ["query"] ["error_uri"] = $error_uri;

		$this->doRedirectUriCallback($redirect_uri, $result);
	}

	/**
	 * Send out error message in JSON.
	 *
	 * @param $http_status_code
	 * HTTP status code message as predefined.
	 * @param $error
	 * A single error code.
	 * @param $error_description
	 * (optional) A human-readable text providing additional information,
	 * used to assist in the understanding and resolution of the error
	 * occurred.
	 * @param $error_uri
	 * (optional) A URI identifying a human-readable web page with
	 * information about the error, used to provide the end-user with
	 * additional information about the error.
	 *
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4.3
	 *
	 * @ingroup oauth2_error
	 */
	private function errorJsonResponse($http_status_code, $error, $error_description = NULL, $error_uri = NULL) {

	    $result['code'] = -1;
	    $result ['msg'] = $error;

		if ($this->getVariable('display_error') && $error_description)
			$result ["error_description"] = $error_description;

		if ($this->getVariable('display_error') && $error_uri)
			$result ["error_uri"] = $error_uri;

		header("HTTP/1.1 " . $http_status_code);
		$this->sendJsonHeaders();

		echo json_encode($result);

		exit();
	}

	/**
	 * Send a 401 unauthorized header with the given realm and an error, if
	 * provided.
	 *
	 * @param $http_status_code
	 *   HTTP status code message as predefined.
	 * @param $realm
	 *   The "realm" attribute is used to provide the protected resources
	 *   partition as defined by [RFC2617].
	 * @param $scope
	 *   A space-delimited list of scope values indicating the required scope
	 *   of the access token for accessing the requested resource.
	 * @param $error
	 *   The "error" attribute is used to provide the client with the reason
	 *   why the access request was declined.
	 * @param $error_description
	 *   (optional) The "error_description" attribute provides a human-readable text
	 *   containing additional information, used to assist in the understanding
	 *   and resolution of the error occurred.
	 * @param $error_uri
	 *   (optional) The "error_uri" attribute provides a URI identifying a human-readable
	 *   web page with information about the error, used to offer the end-user
	 *   with additional information about the error. If the value is not an
	 *   absolute URI, it is relative to the URI of the requested protected
	 *   resource.
	 *
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-5.2
	 *
	 * @ingroup oauth2_error
	 */
	private function errorWWWAuthenticateResponseHeader($http_status_code, $realm, $error, $error_description = NULL, $error_uri = NULL, $scope = NULL) {
		$realm = $realm === NULL ? $this->getDefaultAuthenticationRealm() : $realm;

		$result = "WWW-Authenticate: OAuth realm='" . $realm . "'";

		if ($error)
			$result .= ", error='" . $error . "'";

		if ($this->getVariable('display_error') && $error_description)
			$result .= ", error_description='" . $error_description . "'";

		if ($this->getVariable('display_error') && $error_uri)
			$result .= ", error_uri='" . $error_uri . "'";

		if ($scope)
			$result .= ", scope='" . $scope . "'";

		header("HTTP/1.1 " . $http_status_code);
		header($result);

		exit;
	}

	/**
	 * Release DB connection during destruct.
	 */
	function __destruct() {
		$this->db = NULL; // Release db connection
	}

	/**
	 * Handle PDO exceptional cases.
	 */
	private function handleException($e) {
		echo "Database error: " . $e->getMessage();
		exit();
	}

	/**
	 * Implements OAuth2::checkClientCredentials().
	 *
	 * Do NOT use this in production! This sample code stores the secret
	 * in plaintext!
	 */
	protected function checkClientCredentials($appid, $client_secret = NULL) {
		try {
			$q = $this->db->query("SELECT app_secret FROM app WHERE app_key = ?", array($appid));

			if (!$q) return false;
			$result = $q->row_array();

			if ($client_secret === NULL)
				return $result !== FALSE;

			return $result ["app_secret"] == $client_secret;
		} catch (PDOException $e) {
			$this->handleException($e);
		}
	}

	/**
	 * Implements OAuth2::getRedirectUri().
	 */
	protected function getRedirectUri($appid) {
		try {
			$sql = "SELECT callback_url FROM application WHERE apikey = :appid";
			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(":appid", $appid, PDO::PARAM_STR);
			$stmt->execute();

			$result = $stmt->fetch(PDO::FETCH_ASSOC);

			if ($result === FALSE)
				return FALSE;

			return isset($result ["callback_url"]) && $result ["callback_url"] ? $result ["callback_url"] : NULL;
		} catch (PDOException $e) {
			$this->handleException($e);
		}
	}

	/**
	 * Grant refresh access tokens.
	 *
	 * Retrieve the stored data for the given refresh token.
	 *
	 * Required for OAUTH2_GRANT_TYPE_REFRESH_TOKEN.
	 *
	 * @param $refresh_token
	 * Refresh token to be check with.
	 *
	 * @return
	 * An associative array as below, and NULL if the refresh_token is
	 * invalid:
	 * - appid: Stored client identifier.
	 * - expires: Stored expiration unix timestamp.
	 * - scope: (optional) Stored scope values in space-separated string.
	 *
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4.1.4
	 *
	 * @ingroup oauth2_section_4
	 */
	protected function getRefreshToken($refresh_token) {


	    $sql = "SELECT u.scope,r.* 
              FROM oauth2_refresh_tokens r
              INNER JOIN oauth2_users u ON u.app_key = r.appkey and u.user_id = r.user_id
              WHERE r.refresh_token = '{$refresh_token}'" ;

		$q = $this->db->query($sql);

		if ( empty($q) ) return false;
		$r = $q->row_array();
		if ( empty($r) ) return false;
		
		return $r;
	}

	/**
	 * Take the provided refresh token values and store them somewhere.
	 *
	 * This function should be the storage counterpart to getRefreshToken().
	 *
	 * If storage fails for some reason, we're not currently checking for
	 * any sort of success/failure, so you should bail out of the script
	 * and provide a descriptive fail message.
	 *
	 * Required for OAUTH2_GRANT_TYPE_REFRESH_TOKEN.
	 *
	 * @param $refresh_token
	 * Refresh token to be stored.
	 * @param $appid
	 * Client identifier to be stored.
	 * @param $expires
	 * expires to be stored.
     * @param $scope
	 * (optional) Scopes to be stored in space-separated string.
	 *
	 * @ingroup oauth2_section_4
	 */
	protected function setRefreshToken($refresh_token, $openid, $appid, $user_id = NULL) {

	    $q = $this->db->query("SELECT * FROM oauth2_refresh_tokens WHERE appkey = ? AND user_id = ?", array($appid, $user_id));

		$r = $q->row_array();
		if (!empty($r ['refresh_token'])) {
			return $r ['refresh_token'];
		}
		$this->db->query("INSERT INTO oauth2_refresh_tokens (refresh_token, appkey, openid, user_id) VALUES (?, ?, ?, ?)", array($refresh_token, $appid, $openid, $user_id));
		return $refresh_token;
	}

	/**
	 * Implements OAuth2::getAccessToken().
	 */
	public function getAccessToken($oauth_token, $openid) {

	    $sql = "SELECT * FROM oauth2_tokens WHERE oauth_token = '{$oauth_token}' and openid = '{$openid}'" ;

		$q = $this->db->query($sql);

		if ( empty($q) ) return false;
		$r = $q->row_array();
		if ( empty($r) ) return false;

		return $r; 
	}

	/**
	 * Implements OAuth2::setAccessToken().
	 */
	protected function setAccessToken($oauth_token, $openid, $app_key, $expires, $scope = NULL, $user_id = NULL, $grant_type = 'code') {
		try {
			$token = array();
            $expires = $expires + time();
			//刷新token，更新access_token、exprise
			if ($grant_type == 'refresh_token' || $grant_type == 'password') {
				$this->db->query("DELETE FROM oauth2_tokens WHERE app_key=? AND user_id=?", array($app_key, $user_id));

			} else {
				$q = $this->db->query("SELECT * FROM oauth2_tokens WHERE app_key = ? AND user_id = ?", array($app_key, $user_id));
				$result = $q->row_array();

				if (!empty($result ['oauth_token'])) {
					$token = array(
						'access_token' => $result ['oauth_token'],
						'expire_time' =>  $expires,
						'openid' => $result ['openid'],
						'scope' => $result ['scope'],
					);
					return $token;
				}
			}

			$this->db->query("INSERT INTO oauth2_tokens (oauth_token, app_key, user_id, openid, expires, scope) 
				VALUES (?, ?, ?, ?, ?, ?)", array($oauth_token, $app_key, $user_id, $openid, $expires, $scope));

			$token = array(
				'access_token' => $oauth_token,
				'expire_time' => $expires,
				'openid' => $openid,
				'scope' => $scope,
			);
			return $token;

		} catch (PDOException $e) {
			$this->handleException($e);
		}
	}

	/**
	 * Return supported grant types.
	 *
	 * You should override this function with something, or else your OAuth
	 * provider won't support any grant types!
	 *
	 * @return
	 * A list as below. If you support all grant types, then you'd do:
	 * @code
	 * return array(
	 * OAUTH2_GRANT_TYPE_AUTH_CODE,
	 * OAUTH2_GRANT_TYPE_USER_CREDENTIALS,
	 * OAUTH2_GRANT_TYPE_ASSERTION,
	 * OAUTH2_GRANT_TYPE_REFRESH_TOKEN,
	 * OAUTH2_GRANT_TYPE_NONE,
	 * );
	 * @endcode
	 *
	 * @ingroup oauth2_section_4
	 */
	protected function getSupportedGrantTypes() {
		return array(OAUTH2_GRANT_TYPE_AUTH_CODE, OAUTH2_GRANT_TYPE_APP, OAUTH2_GRANT_TYPE_USER_CREDENTIALS, OAUTH2_GRANT_TYPE_ASSERTION, OAUTH2_GRANT_TYPE_REFRESH_TOKEN, OAUTH2_GRANT_TYPE_NONE);
	}

	/**
	 * 通过code获取相关信息
	 * Required for OAUTH2_GRANT_TYPE_AUTH_CODE.
	 * @param type $code
	 * @return boolean
	 */
	protected function getAuthCode($code) {
		$q = $this->db->query("SELECT * FROM oauth2_codes WHERE code=?", array($code));

		if ( empty($q) ) return false;
		$r = $q->row_array();
		if ( empty($r) ) return false;

		return $r;
	}

	/**
	 * 存储临时生成的用于交换access token的code
	 * Required for OAUTH2_GRANT_TYPE_AUTH_CODE.
	 * @param type $code
	 * @param type $appid
	 * @param type $redirect_uri
	 * @param type $expires
	 * @param type $scope
	 * @param type $user_id
	 * @return boolean
	 */
	protected function setAuthCode($code, $appid, $redirect_uri, $expires, $scope = NULL, $user_id = NULL) {
		$q = $this->db->query("INSERT INTO oauth2_codes (code, app_key, user_id, redirect_uri, expires, scope) VALUES (?, ?, ?, ?, ?, ?)", array($code, $appid, $user_id, $redirect_uri, $expires, $scope));	
		if($q) {
			return true;
		} else{
			return false;
		}
	}

    /**
     * 存储APP 中的OpenId 到相应的 OAuth2_User 字段中
     * @param $appid
     * @param $userid
     * @param $openid
     * @return bool
     */
    protected function setClientOpenId($appid, $userid, $openid) {

        $sql = "UPDATE oauth2_users SET openid = '{$openid}' WHERE app_key='{$appid}' AND user_id='{$userid}'";

        $ret = $this->db->query($sql);

        if($ret) {
            return true;
        } else{
            return false;
        }
    }
}
