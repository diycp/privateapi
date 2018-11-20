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
class Award extends REST_Controller {

    protected $invoices = NULL;

    protected $currency = "USD";
    protected $currency_sign = "$";

    protected $failure = array();
    protected $success = array();



    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $market_id = $this->get("market-id");

        if( !isset($market_id) ||  $market_id  == null )
        {
            $market_id = $_GET["market-id"];

            if( !isset($market_id) ||  $market_id  == null ) {
                $this->set_response([
                    'code' => -1,
                    'msg' => 'Please make sure the parameter market-id is valid. '
                ], REST_Controller::HTTP_BAD_REQUEST);
                exit;
            }
        }


        $this->load->driver('cache');


        if( $this->cache->memcached->get('invoices') == null ) {
            $this->load_templates();
        }

        $this->invoices =  $this->cache->memcached->get('invoices');




    }

    public function get_supplier_stat_get(){

        $amount = 0 ;
        $supplier_list = array();
        $invoice_list = array();

        $paydate = date('Y/m/d',time()+3600*24*8);
        $offer_value = 20.0;

        $list = array();
        $average_dpe = 0;

        foreach($this->invoices as $val){

            if( $val['invoice_status'] != 'eligiable')
                continue;

            $amount += $val['invoice_amount'];

            $dpe = (strtotime($val['original_paydate']) - strtotime($paydate)) / (3600*24) ;
            $discount = round($val['invoice_amount']*$offer_value/365*$dpe/100, 2) ;
            $average_dpe + $dpe;

            $list[] = array(
                'inv-id' => $val['inv-id'],
                'invoice_status' => $val['invoice_status'],
				'invoice_id' => $val['invoice_id'],
				'invoice_amount' => $val['invoice_amount'],
				'original_paydate' => $val['original_paydate'],
				'invoice_dpe' => $dpe,
				'discount_amount'=> $discount,
				'discount_rate' => round($discount/$val['invoice_amount']*100, 2)
            );

        }

        $result = array(
            'currency' => $this->currency,
            'currency_sign' => $this->currency_sign,
            'available_cash' => 80000.00,
            'paydate' => $paydate,
            'supplier' => 'NXP',
		    'vendorcode' => 'S01',
            'eligible_paid' => $amount,
            'invoice_count'=> count($list),
            'average_dpe' => count($list) > 0 ? round($average_dpe/count($list), 1) : 0 ,
            'offer_type' => 'APR',
            'offer_value' => $offer_value,
            'list' => $list
        );

        $this->response([
            'code' => 1,
            'data' => $result
        ], REST_Controller::HTTP_OK);

    }

    public function give_invoices_award_post(){

        $invoice = $this->post('inv-id');

        if( is_array($invoice) && count($invoice) >0 ){
            foreach($invoice as $inv){
                $this->award_invoice($inv);
            }
        }else{
            $this->award_invoice($invoice);
        }

        $this->cache->memcached->save('invoices', $this->invoices);

        if( count($this->failure) > 0)
        {
            $this->set_response([
                'code' => 0,
                'data' => $this->failure
            ], REST_Controller::HTTP_OK);
        }
        elseif(count($this->success) <= 0){

            $this->set_response([
                'code' => -1,
                'msg' => 'No invoice had been awarded , please check your inv-id is correct.'
            ], REST_Controller::HTTP_NOT_FOUND); // CREATED (201) being the HTTP response code

        }else{
            $this->set_response([
                'code' => 1,
                'msg' => 'success'
            ], REST_Controller::HTTP_OK); // CREATED (201) being the HTTP response code
        }

    }

    private function award_invoice($val){

        foreach($this->invoices as &$item){
            if($item['inv-id'] == $val) {

                if($item['invoice_status'] != "eligiable"){
                    $this->failure[] = $val;
                }else{
                    $item['invoice_status'] = "awarded";
                    $this->success[] = $val;
                }
                break;
            }
        }
    }

    private function load_templates()
    {

                $this->cache->memcached->save('invoices',
                    [
                        array(
                            'inv-id' => 'xxxx-1-xxx',
                            'invoice_id' => 'l_2018031234',
                            'invoice_amount' => 21027.13,
                            'original_paydate' => '2018-08-16',
                            'invoice_status' => 'eligiable'
                        ),
                        array(
                            'inv-id' => 'xxxx-2-xxx',
                            'invoice_id' => 'l_2018040104',
                            'invoice_amount' => 30317.70,
                            'original_paydate' => '2018-08-03',
                            'invoice_status' => 'eligiable'
                        ),
                        array(
                            'inv-id' => 'xxxx-3-xxx',
                            'invoice_id' => 'l_2018011213',
                            'invoice_amount' => 51027.13,
                            'original_paydate' => '2018-07-26',
                            'invoice_status' => 'eligiable'
                        ),
                        array(
                            'inv-id' => 'xxxx-4-xxx',
                            'invoice_id' => 'l_2018030221',
                            'invoice_amount' => 41027.13,
                            'original_paydate' => '2018-09-16',
                            'invoice_status' => 'eligiable'
                        )
                    ]
                );

    }

}
