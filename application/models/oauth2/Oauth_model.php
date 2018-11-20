<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Oauth_model extends CI_Model {

	public function __construct() {
		parent::__construct();
		$this->load->helper('output');
	}

	/**
	 * 获取当前登录用户uid
	 */
	public function getLoginUserID($appid, $username, $password){

	    $username = strtolower($username);
        $password = MD5($appid.'_'.$password);

        $sql = "SELECT user_id FROM oauth2_users WHERE app_key='{$appid}' and lower(username)='{$username}' and password = '{$password}'" ;

        $q = $this->db->query($sql);

        if ( empty($q) ) return false;

        $r = $q->row_array();

        if ( empty($r) ) return false;

		//@todo: app如何传递uid给openapi
		return $r["user_id"];
	}

    /**
     * 获取当前微信用户uid
     */
    public function getWechateUserID($openId) {
        //@todo: app如何传递uid给openapi
        return 1000;
    }

}
