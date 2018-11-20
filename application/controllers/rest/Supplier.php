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
class Supplier extends REST_Controller {

    protected  $marketId = null;

    function __construct()
    {
        // Construct the parent class
        parent::__construct();


        $market_id = $this->get('market_id');

        if( !isset($market_id) || !empty($market_id) || $market_id == null)
        {
            $this->set_response([
                'code' => -1,
                'msg' => 'Please make sure the parameter market_id is valid. '
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        $this->marketId = $market_id;

        $this->load->model('Suppliermodel');
    }


    public function get_supplier_list_get()
    {
        // Users from a data store e.g. database

            $this->response([
                'code' => 1,
                'data' =>  $this->Suppliermodel->getSuppliers($this->marketId)
            ], REST_Controller::HTTP_OK);


    }

    public function get_supplier_user_list_get()
    {

        // Users from a data store e.g. database
        $supplierId = $this->get("supplier_id");

            if(isset($supplierId) && !empty($supplierId)){

                    $this->response([
                        'code' => 1,
                        'data' => $this->Suppliermodel->getSuppliersUsers($this->marketId, $supplierId)
                    ], REST_Controller::HTTP_OK);

            }else{
                $this->response([
                    'code' => -1,
                    'msg' => 'Please make sure the parameter supplier_id is valid.'
                ], REST_Controller::HTTP_OK);
            }

    }

    public function sync_market_suppliers_get(){
        $this->response([
            'code' => 1,
            'msg' => 'success'
        ], REST_Controller::HTTP_OK);
    }

    public function set_supplier_action_post(){

        $supplierId = $_GET['supplier_id'];

        $active_type = $this->post('action_type');
        $action_id = $this->post('action_id');


        $valid = false;

        if( !isset($supplierId) || empty($supplierId)){
            $this->set_response([
                'code' => -1,
                'msg' => 'Please make sure the parameter supplier_id is valid. '
            ], REST_Controller::HTTP_OK);
        }else{
            //#根据 $active_type 处理
            if(!isset($active_type) || empty($active_type) || !isset($action_id) || empty($action_id)){
                $this->set_response([
                    'code' => -1,
                    'msg' => 'Please make sure your post data is correct. '
                ], REST_Controller::HTTP_BAD_REQUEST);
            }else{
                $this->set_response([
                    'code' => 1,
                    'msg' => 'success'
                ], REST_Controller::HTTP_OK);

            }

        }

    }

    public function get_supplier_stat_get(){


            $this->set_response([
                'code' => 1,
                'data' =>  $this->Suppliermodel->getSupplierStat($this->marketId)
            ], REST_Controller::HTTP_OK);

    }


    private function action_handler($action_type, $action_id){

        return true;
    }

}
