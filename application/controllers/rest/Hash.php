<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . 'libraries/REST_Controller.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Hash extends REST_Controller {


    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('StatCurrentmodel');
        $this->StatCurrentmodel->init($this->profile);
    }

    public function get_hash_post() {
        $result = []; 
        $cashpool_code = $this->post('cashpool_code');
        if (empty($cashpool_code)) {
            $result = $this->StatCurrentmodel->getAllHash();
        } else {
            $result = $this->StatCurrentmodel->getHashByCashPoolCodes($cashpool_code);
        }
      
        if (0 == count($result)) {
            $this->response([
                'code' => 0,
                'msg' => '暂无市场权限'
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'code' => 1,
                'data' => $result 
            ], REST_Controller::HTTP_OK);
        }
    }
}
