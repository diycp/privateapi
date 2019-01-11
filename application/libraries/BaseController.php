<?php

defined('BASEPATH') OR exit('No direct script access allowed');

abstract class BaseController extends CI_Controller {


    public $encrypt_name = 'encrypt';
    public $salt         = 'abcdfdsfd';
    public $get          = null;
    public $post         = null;
    public $book         = null;
    public function Api() {
    
    }
   
    public function init() {
        $this->get  = $this->input->get();
        $this->post = null;
        $data       = $this->getData();
        if (isset($data['post']) && count($data['post']) > 0) {
            $this->post = $data['post'];
            $this->load->model('CustomerCashpool');
            //$this->CustomerCashpool.php->init($this->profile)
            $keys       = array_column($this->post, 'book_code');
            $this->book = $this->CustomerCashpool->getBooks($keys);
        }
    }
 
    public function array_remove($data, $key) {
        if (! array_key_exists($key, $data)) {
            return $data;
        }
        $keys  = array_keys($data);
        $index = array_search($key, $keys);
        if($index !== FALSE) {
            array_splice($data, $index, 1);
        }
        return $data;
    }

    public function _check($get) {
        $encrypt = '';
        if (isset($get) && is_array($get)) {
            if (isset($get[$this->encrypt_name])) {
                $encrypt = trim($get[$this->encrypt_name]);
            }
            $get = $this->array_remove($get, $this->encrypt_name);
            if(ksort($get)) {
                $str  = implode('|', $get);
                $code = crypt($str, $this->salt);
                if ($code === $encrypt) {
                    return true; 
                }
            }
        return false;
        }
    }

    public function getData() {
        $str = file_get_contents("php://input");
        if (! empty($str)) {
            return json_decode($str, true);
        }
        return [];
    }





}
