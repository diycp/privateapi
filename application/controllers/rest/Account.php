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
class Account extends REST_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Usermodel');
    }

    public function get_profile_get()
    {

        /*
        $profile = array(
            "profile"=> "xxxx-1-zzz",
            "email"=> "cust1@ep-fo.com",
            "name"=> "Jorden",
            "lastname"=> "Michael",
            "job"=> "Account",
            "department_email"=> "ares@ep-fo.com",
            "phone"=> "+86 755 12345678",
            "fiscalyear"=> "",
            "industry"=> "",
            "country"=> "Chinese"
	    );
        */

        $profile = $this->Usermodel->getProfile($this->_user_id);

        if($profile == null){
            // Set the response and exit
            $this->response([
                'code' => -1,
                'data' => 'Invalid User'
            ], REST_Controller::HTTP_BAD_REQUEST); // OK (200) being the HTTP response code

        }else{
            // Set the response and exit
            $this->response([
                'code' => 1,
                'data' => $profile
            ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        }

    }

    public function reset_password_post()
    {

        $original_password = $this->post('old_password');
        $new_password = $this->post('new_password');
        $confirm_password = $this->post('confirm_password');


        if(!isset($original_password) || !isset($new_password) || !isset($confirm_password)){
            $this->set_response([
                'code' => -200,
                'msg' => 'Please confirm your enter value'
            ], REST_Controller::HTTP_OK);
        }

        if($new_password != $confirm_password){
            $this->set_response([
                'code' => -1,
                'msg' => 'Confirm password not equal new password'
            ], REST_Controller::HTTP_OK);
        }

        $this->set_response([
            'code' => 1,
            'msg' => 'success'
        ], REST_Controller::HTTP_OK); // CREATED (201) being the HTTP response code
    }

    public function change_password_get(){
        $this->set_response([
            'code' => 1,
            'msg' => 'success'
        ], REST_Controller::HTTP_OK);
    }

    public function update_profile_post()
    {
        $first_name = $this->post("name");
        $last_name= $this->post("lastname");
        $job= $this->post("job");
        $department_email= $this->post("department_email");
        $phone= $this->post("phone");
        $fiscalyear= $this->post("fiscalyear");
        $industry= $this->post("industry");
        $country= $this->post( "country");


        $this->set_response([
            'code' => 1,
            'msg' => 'success'
        ], REST_Controller::HTTP_OK); // CREATED (201) being the HTTP response code
    }


}
