<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class App_model extends CI_Model {

	public function __construct() {
		parent::__construct();
		$this->load->helper('output');
	}

	/**
	 * 通过appkey查询app信息
	 * Required for OAUTH2_GRANT_TYPE_AUTH_CODE.
	 * @param type $code
	 * @return boolean
	 */
	public function getAppInfoByAppkey($app_key) {
		$q = $this->db->query("SELECT * FROM app WHERE app_key=?", array($app_key));

		if ( empty($q) ) return false;
		$r = $q->row_array();
		if ( empty($r) ) return false;

		return $r;
	}
}
