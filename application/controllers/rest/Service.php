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
class Service extends CI_Controller {

    protected $_post_args;
    protected $_get_args;

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->_parse_post();
        $this->_parse_get();
    }

    protected function get($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_get_args;
        }

        return isset($this->_get_args[$key]) ? $this->_get_args[$key] : NULL;
    }

    protected function post($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_post_args;
        }

        return isset($this->_post_args[$key]) ? $this->_post_args[$key] : NULL;
    }



    private function _parse_post()
    {
        $postString = file_get_contents('php://input');

        $postObject = array();
        if( isset($postString) && !empty($postString) && strlen($postString) > 4)
        {
            $postObject = json_decode($postString, true);
        }

        $this->_post_args = $postObject ;

    }

    private function _parse_get() {

        $queryString = $_SERVER["QUERY_STRING"];

        if (!empty($queryString)) {
            $QueryObject = new stdClass();
            $queryString = explode('&', $queryString);
            foreach ($queryString as $r) {
                $r = explode('=', $r);
                if (count($r) == 2) {
                    $key               = strtolower($r[0]);
                    $QueryObject->$key = strtolower($r[1]);
                }
            }
            $this->_get_args = $QueryObject;
        }
    }


    /**
     * 输出JSON
     * @param mixed $arr
     */
    private function echoJson($arr) {
        header('Content-Type: application/json; charset=utf-8');

        if (strpos(PHP_VERSION, '5.3') > -1) {
            // php 5.3-
            echo json_encode($arr);
        } else {
            // php 5.4+
            echo json_encode($arr, JSON_UNESCAPED_UNICODE);
        }
        return true;
    }



    public function verify_email(){

        $email = $this->post('email');

        if( isset($email) && !empty($email) && strlen($email) > 4){

            if( $this->check_user($email))
            {
                $this->echoJson([
                    'code' => 1,
                    'msg' => 'success'
                ]);
            }else{
                $this->echoJson([
                    'code' => -1,
                    'msg' => "You post email is not exists, please check it ."
                ]);
            }


        }else{
            $this->echoJson([
                'code' => -1,
                'msg' => "You post email parameter is invalid, please check it first ."
            ]);
        }

    }

    private function check_user($email){
        $this->load->model('Usermodel');

        return $this->Usermodel->check_exists_user(strtolower($email)) ;
    }

    public function reset_password(){

        $email = $this->post('email');

        if( isset($email) && !empty($email) && strlen($email) > 4){


            if( $this->check_user($email))
            {
                $this->echoJson([
                    'code' => 1,
                    'msg' => 'success'
                ]);
            }else{
                $this->echoJson([
                    'code' => -1,
                    'msg' => "You post email is not exists, please check it ."
                ]);
            }


        }else{
            $this->echoJson([
                'code' => -1,
                'msg' => "You post email parameter is invalid, please check it first ."
            ]);
        }

    }


}
