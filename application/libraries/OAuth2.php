<?php

require_once(APPPATH.'/libraries/OAuth2/Autoloader.php');

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-04-02
 * Time: 12:13
 */
class OAuth2
{
    protected $response = NULL;
    protected $storage = NULL;
    protected $server = NULL;
    protected $request = NULL;
    protected $params = NULL;

    public function __construct($params = array()){

        // Set the super object to a local variable for use later
        $this->CI =& get_instance();
        // Load the Sessions class
        $this->CI->load->driver('session', $params);

        OAuth2\Autoloader::register();
        $this->storage = new OAuth2\Storage\Pdo(array('dsn' => 'mysql:dbname=oauth;host=123.207.24.172', 'username' => 'dbuser', 'password' => 'wodemima'));
        #$this->storage = new OAuth2\Storage\Pdo($db['default']);
        $this->server = new OAuth2\Server($this->storage, array('allow_implicit' => true));
        $this->request = OAuth2\Request::createFromGlobals();
        $this->response = new OAuth2\Response();
    }

    public function password_credentials($users){

        //$users = array("user" => array("password" => 'pass', 'first_name' => 'homeway', 'last_name' => 'yao'));
        $storage = new OAuth2\Storage\Memory(array('user_credentials' => $users));//user是认证的账户，在表oauth_users中
        $this->server->addGrantType(new OAuth2\GrantType\UserCredentials($storage));
        $this->server->handleTokenRequest($this->request)->send();
    }

    public function authorize($is_authorized){
        $this->server->addGrantType(new OAuth2\GrantType\AuthorizationCode($this->storage));
        $response = $this->server->handleAuthorizeRequest($this->request, $this->response, $is_authorized);
        var_dump($response);
        if ($is_authorized) {
            $code = substr($response->getHttpHeader('Location'), strpos($response->getHttpHeader('Location'), 'code=')+5, 40);
            header("Location: ".$response->getHttpHeader('Location'));
        }
        $response->send();
    }

    public function client_credentials(){
        $this->server->addGrantType(new OAuth2\GrantType\ClientCredentials($this->storage, array("allow_credentials_in_request_body" => true)));
        $this->server->handleTokenRequest($this->request)->send();
    }

    public function refresh_token(){
        $this->server->addGrantType(new OAuth2\GrantType\RefreshToken($this->storage, array(
            "always_issue_new_refresh_token" => true,
            "unset_refresh_token_after_use" => true,
            "refresh_token_lifetime" => 2419200,
        )));
        $this->server->handleTokenRequest($this->request)->send();
    }

}