<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class DistributeModel extends CI_Model {

    protected $profile ;
    protected $marketId;
    protected $vendors ;

    protected function base64url_encode($data) {

        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected function base64url_decode($data) {

        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

	public function __construct()    {
        parent::__construct();
        $this->db = $this->load->database('supplier', true);

    } 	

		
}
