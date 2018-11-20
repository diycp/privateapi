<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * oauth2.0
 */
class OAuth2 extends CI_Controller {

	public function __construct(){
		parent::__construct();
		$this->load->helper('output');
		$this->load->model(array('oauth2/oauth_model', 'oauth2/OAuth', 'app/app_model'));

		$this->load->config('errno');
		$this->_errno = $this->config->item('errno');
	}

	/**
	 * 展示授权页面
	 */
	public function authorize() {

	    //提供密码登录
        $username  = $this->input->get_post('username', TRUE);
        $password  = $this->input->get_post('username', TRUE);

        $appid = $this->input->get_post('appid', TRUE);//code
        $redirect_uri = $this->input->get_post('redirect_uri', TRUE);//code
		$response_type = $this->input->get_post('response_type', TRUE);//code
		$scope = $this->input->get_post('scope', TRUE);// 应用授权作用域
        $state = $this->input->get_post('state', TRUE);// 返回状态

		$params = array(
				'appid' => $appid,
				'redirect_uri' => $redirect_uri,
				'response_type' => $response_type,
				'scope' => $scope,
                'state' => $state
			);

		if (!in_array($scope, array('snsapi_base', 'snsapi_userinfo'))) {
			error_output(ERROR_INVALID_SCOPE, $this->_errno);
		}

		if( !isset($username) || empty($username) || !isset($password) || empty($password)){
            error_output(ERROR_INVALID_ACCOUNT, $this->_errno);
        }

		//设为登录用户与登录密码
		$user_id = $this->oauth_model->getLoginUserID($appid, $username, $password);

        $is_authorized = $user_id ? true : false;

		$code = $this->OAuth->finishClientAuthorization($is_authorized, $params, $user_id);
	}


    /**
     * 展示授权页面
     */
    public function check_authorize() {


        $ret = array('code' => 1, 'msg' => 'success') ;

        $post = file_get_contents('php://input');

        $post = json_decode($post, true);

        //POST的用户名与密码
        $username  = $post["username"];
        $password  = $post["password"];
        $openid = $post["openid"];

        $appid = $this->input->get_post('appid', TRUE);//code
        $secret = $this->input->get_post('secret', TRUE);// 返回状态

        $result = $this->OAuth->finishAppAuthorization($username, $password, $appid, $secret, $openid);

        if($result === false) {
            $ret['code'] = -1;
            $ret['msg'] = "Binding failure";
        }

        output($ret ) ;

    }

	/**
	 * 换取 access token
	 */
	public function get_access_token() {

		$ret = $this->OAuth->grantAccessToken();

        $ret["code"] = 1;

		output($ret);
	}

	/**
	 * 刷新 access token
	 */
	public function refresh_access_token() {

		$ret = $this->OAuth->grantAccessToken();

        $ret["code"] = 1;

		output($ret);
	}

}
