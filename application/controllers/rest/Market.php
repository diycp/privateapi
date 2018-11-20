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
class Market extends REST_Controller {

    protected  $markets = NULL;
    protected  $close_time = NULL;
    protected  $confirm = NULL;
    
    function __construct()
    {
        // Construct the parent class
        parent::__construct();


        $this->load->driver('cache');
        $this->load->model('Marketmodel');

        $this->Marketmodel->init( $this->profile);

        $this->get_tradding_time() ;
    }

    private function get_tradding_time(){

        $result = $this->Marketmodel->getMarketServiceTime();

        if(  isset($result["StartTime"]) && $result["StartTime"] != null
            && isset($result["EndTime"]) && $result["EndTime"] != null
        ){

            $start_time = strtotime(  date('Y-m-d', time()).' '.date("H:i:s", strtotime($result["StartTime"]) ) );
            $end_time = strtotime(  date('Y-m-d', time()).' '.date("H:i:s", strtotime($result["EndTime"]) ));

            $close_time  = $start_time < time() && $end_time > time() ? $end_time : -1;

        }else{
            $close_time = -1;
        }

        $this->close_time  = $close_time;
    }


    public function get_trading_time_get(){

        $this->response([
            'code' => 1,
            'data' => array(
                'close_time' =>  $this->close_time
            )
        ], REST_Controller::HTTP_OK);
    }

    public function get_market_list_get()
    {
        // Users from a data store e.g. database

            $markets = $this->Marketmodel->getMarkets();
            $this->response([
                'code' => 1,
                'data' =>  $markets

            ], REST_Controller::HTTP_OK);

    }

    public function get_market_setting_get(){

        $market_id = $this->get("market_id");

        if(isset($market_id) && !empty($market_id) ){

            $this->set_response([
                'code' => 1,
                'data' => $this->Marketmodel->getMarketSetting($market_id)
            ], REST_Controller::HTTP_OK);

        }else{
            $this->set_response([
                'code' => -1,
                'msg' => 'Please make sure the parameter market_id is valid. '
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

    }

    public function set_market_active_post(){

        $market_id = $_GET['market_id'];

        $active_status = $this->post('active_status');

        $valid = false;

        if( !isset($market_id) || empty($market_id)){
            $this->set_response([
                'code' => -1,
                'msg' => 'Please make sure the parameter market_id is valid. '
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $code = -1;

        $market = $this->Marketmodel->getMarketStatusByCode($market_id);

        $market_status = intval($market["MarketStatus"]);

        if( $market_status == 1 &&  $active_status != 1){

                $confirm_id = md5(time());
                $message = "This market is offered by Suppliers ";

                $confirm  = array(
                    "object" => "market",
                    "object_value" => array( "active_status" => $active_status),
                    "object_key" => $market_id,
                    'expire_time' => time() + 120 # 2分钟后失效
                );

                $this->cache->memcached->save($confirm_id, $confirm);

                $this->set_response([
                    'code' => 0,
                    'data' => [
                        'confirm-id' => $confirm_id,
                        'type' => 'warning',
                        'message' => $message
                    ]
                ], REST_Controller::HTTP_OK);

            }else if($market_status == -1 && $active_status == 1)  {

                $result = $this->Marketmodel->setMarketActive($market_id,  0);

                if( $result){

                    $this->set_response([
                        'code' => 1,
                        'msg' => 'success'
                    ], REST_Controller::HTTP_OK);

                }else{
                    $this->set_response([
                        'code' => -1,
                        'msg' => 'Update status of market failure , please try to submit it again'
                    ], REST_Controller::HTTP_BAD_REQUEST);
                }

            }else if($market_status == 0){
               $this->set_response([
                    'code' => 21111 ,
                    'msg' => "Status of market is pending, you couldn't operate it! "
                ], REST_Controller::HTTP_OK);

            }else{
                $this->set_response([
                    'code' => 21112,
                    'msg' => "Nothing affect! "
                ], REST_Controller::HTTP_OK);
            }

    }

    public function set_market_setting_post(){

        $market_id = $_GET['market_id'];

        $setting = array(
            "market_cash" => $this->post('market_cash'),
            "expect_apr" => $this->post('expect_apr'),
            "min_apr" => $this->post('min_apr'),
           # "paydate" => $this->post('paydate'),
            "reserve_percentage" => $this->post('reserve_percentage'),
            "reconcilation_date" => $this->post('reconcilation_date')
        );


        if( !isset($market_id) || empty($market_id)){
            $this->set_response([
                'code' => -1,
                'msg' => 'Please confirm the parameter market_id is valid. '
            ], REST_Controller::HTTP_OK);
        }


        if(  $setting["expect_apr"] > 99.99 ||  $setting["expect_apr"] <= 0.1 ||  $setting["min_apr"] > 99.99 ||  $setting["min_apr"] <= 0.1 ){
            $this->set_response([
                'code' => -1,
                'msg' => 'Please confirm expect_apr and min_apr are available . They must be limit 0.1% ~ 99.99% '
            ], REST_Controller::HTTP_OK);
        }


        if ( $setting["expect_apr"] < $setting["min_apr"]){
            $this->set_response([
                'code' => -1,
                'msg' => 'Please confirm the Expect apr is greater than the Min apr! '
            ], REST_Controller::HTTP_OK);
        }


        if ( $setting["reserve_percentage"] > 99 || $setting["reserve_percentage"] < 0){
            $this->set_response([
                'code' => -1,
                'msg' => 'Please confirm the Reserve  Percentage limit 0~99% ! '
            ], REST_Controller::HTTP_OK);
        }

        if ( $setting["reconcilation_date"] > 30 ||   $setting["reconcilation_date"] <= 0){
            $this->set_response([
                'code' => -1,
                'msg' => 'Please confirm the Reconcilation  Date limit 1 ~ 30 ! '
            ], REST_Controller::HTTP_OK);
        }

        $result = $this->Marketmodel->setMarketSetting($market_id , $setting);

        if($result){
            $this->set_response([
                'code' => 1,
                'msg' => 'success'
            ], REST_Controller::HTTP_OK);
        }else{
            $this->set_response([
                'code' => -1,
                'msg' => 'Please make sure your post data parameter is correct.'
            ], REST_Controller::HTTP_OK);
        }

        /*
        //暂时注释下面的语句

        $market = $this->Marketmodel->getMarketSetting($market_id) ;

            if(isset($market) && !empty($market) && $market != null){

                $valid = true;

                if($market["market_status"] != 1){

                    $confirm_id = md5(time());
                    $message = "";

                    switch( intval($market["market_status"]) ){
                        case 0:
                            $message = "This market is offered by Suppliers ";
                            break;
                        case -1:
                            $message = "This market is closed ";
                            break;
                        default:
                            break;
                    }

                    if( isset( $this->confirm[$confirm_id] ) ){
                        $this->confirm[$confirm_id] = array(
                            "object" => "market",
                            "object_value" => $setting,
                            "object_key" => $market_id,
                            'expire_time' => time() + 120 # 2分钟后失效
                        );
                    }else{
                        $this->confirm[$confirm_id] = array(
                            "object" => "market",
                            "object_value" => $setting,
                            "object_id" => $market_id,
                            'expire_time' => time() + 120 # 2分钟后失效
                        );
                    }

                    $this->cache->memcached->save('confirm', $this->confirm);

                    $this->set_response([
                        'code' => 0,
                        'data' => [
                            'confirm-id' => $confirm_id,
                            'type' => 'warning',
                            'message' => $message
                        ]
                    ], REST_Controller::HTTP_OK);

                }else{


                    $result = $this->Marketmodel->setMarketSetting($market_id , $setting);

                    if($result){
                        $this->set_response([
                            'code' => 1,
                            'msg' => 'success'
                        ], REST_Controller::HTTP_OK);
                    }else{
                        $this->set_response([
                            'code' => -1,
                            'msg' => 'Please make sure your post data parameter is correct.'
                        ], REST_Controller::HTTP_OK);
                    }
                }

            }


        if( $valid == false) {
            $this->set_response([
                'code' => -1,
                'msg' => 'Market_id is not been found'
            ], REST_Controller::HTTP_UNPROCESSABLE_ENTITY); // CREATED (201) being the HTTP response code
        }
        */
    }

    public function set_market_allocate_post()
    {

        $market_id = $_GET['market_id'];

        if (!isset($market_id) || empty($market_id)) {
            $this->set_response([
                'code' => -1,
                'msg' => 'Please confirm the parameter market_id is valid. '
            ], REST_Controller::HTTP_OK);
        }

        $allocates = $this->post('allocates');

        if( !isset( $allocates ) || empty($allocates) || $allocates == null || !is_array($allocates)){

            $this->set_response([
                'code' => -1,
                'msg' => 'Please make sure you post [allocates] data is valid. '
            ], REST_Controller::HTTP_OK);

        }else {


            $valid = $this->Marketmodel->setMarketAllocate($market_id, $allocates);


            if ($valid) {

                $this->set_response([
                    'code' => 1,
                    'msg' => 'success'
                ], REST_Controller::HTTP_OK);

            } else {

                $this->set_response([
                    'code' => -1,
                    'msg' => 'There are some items had not required field , Please make sure you post [allocates] data is valid.'
                ], REST_Controller::HTTP_OK);

            }
        }

    }

    public function drop_market_allocate_post()
    {

        $market_id = $_GET['market_id'];

        $allocateId =  $this->post('allocate_id') ;

        if (!isset($market_id) || empty($market_id)) {
            $this->set_response([
                'code' => -1,
                'msg' => 'Please confirm the parameter market_id is valid. '
            ], REST_Controller::HTTP_OK);
        }else{

            #$this->Marketmodel->deleteMarketAllocate($allocateId);


            if ($this->Marketmodel->deleteMarketAllocate($allocateId)) {
                $this->set_response([
                    'code' => 1,
                    'msg' => 'success'
                ], REST_Controller::HTTP_OK);
            }else{
                $this->set_response([
                    'code' => -1,
                    'msg' => 'Drop allocate failure!'
                ], REST_Controller::HTTP_OK);
            }

        }

    }

    public function confirm_market_action_post(){

        $market_id = $_GET['market_id'];
        $confirm_id = $this->post('confirm-id');

        if( isset($market_id) && !empty($market_id) && isset($confirm_id) && !empty($confirm_id)){


            $confirm = $this->cache->memcached->get($confirm_id) ;

            if(isset($confirm) && !empty($confirm))
            {
                 if($confirm['expire_time'] < time() ){

                     $this->set_response([
                         'code' => -1,
                         'msg' => 'It had been timeout,please re-try to update it. '
                     ], REST_Controller::HTTP_OK);

                 }else{

                     $result = false;

                     switch ($confirm["object"]) {
                         case "market":
                             $result = $this->Marketmodel->setMarketActive($confirm["object_key"] , $confirm["object_value"]["active_status"]);
                             break;
                         default:
                             break;
                     }

                     $this->cache->memcached->save($confirm_id, null);

                     if( $result) {
                         $this->set_response([
                             'code' => 1,
                             'msg' => 'success'
                         ], REST_Controller::HTTP_OK);
                     }else{
                         $this->set_response([
                             'code' => -1,
                             'msg' => 'update is failure, please try to submit again. '
                         ], REST_Controller::HTTP_BAD_REQUEST);
                     }

                 }
            }else{

                $this->set_response([
                    'code' => -1,
                    'msg' => 'This confirm-id is not exists. '
                ], REST_Controller::HTTP_BAD_REQUEST);
            }

        }else{
            $this->set_response([
                'code' => -1,
                'msg' => 'Please check market_id and confirm-id is valid. '
            ], REST_Controller::HTTP_OK);
        }


    }

    public function get_market_stat_get(){

        $market_id = $this->get("market_id");
        $market = NULL;

        if(isset($market_id) && !empty($market_id) ){

            $market = $this->Marketmodel->getMarketStatusByCode($market_id);

            $this->set_response([
                'code' => 1,
                'data' => $this->Marketmodel->getMarketStat($market)
            ], REST_Controller::HTTP_OK);

        }else{
            $this->set_response([
                'code' => -1,
                'msg' => 'Please make sure the parameter market_id is valid. '
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

    }

    public function get_market_graph_get(){

        $market_id = $this->get("market_id");
        $market = NULL;
        if(isset($market_id) && !empty($market_id) ){


            $this->set_response([
                'code' => 1,
                'data' => $this->Marketmodel->getCurrentMarketGraph($market_id)
            ], REST_Controller::HTTP_OK);

        }else{
            $this->set_response([
                'code' => -1,
                'msg' => 'Please make sure the parameter market_id is valid. '
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

    }

    public function get_market_current_stat_get(){


        $market_id = $this->get("market_id");
        $market = NULL;
        if(isset($market_id) && !empty($market_id) ){


            $this->set_response([
                'code' => 1,
                'data' => $this->Marketmodel->getCurrentMarketStat($market_id)
            ], REST_Controller::HTTP_OK);

        }else{
            $this->set_response([
                'code' => -1,
                'msg' => 'Please make sure the parameter market_id is valid. '
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

    }

    public function get_market_supplier_stat_get(){
        $market_id = $this->get("market_id");
        $market = NULL;
        if(isset($market_id) && !empty($market_id) ){

            $this->set_response([
                'code' => 1,
                'data' => $this->Marketmodel->getMarketSupplierStat($market_id)
            ], REST_Controller::HTTP_OK);

        }else{
            $this->set_response([
                'code' => -1,
                'msg' => 'Please make sure the parameter market_id is valid. '
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }



}
