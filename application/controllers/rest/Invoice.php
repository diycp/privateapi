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
class Invoice extends REST_Controller {

    protected $invoices = NULL;

    protected $failure = array();

    protected $marketid = null;

    protected $filter = array(

        'invoice_status' => "all" ,
        'invoice_dpe' => [],
        'invoice_amount' => [],
        'start_date' => null,
        'end_date' => null

    );

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $market_id = $this->get("market_id");

        if( !isset($market_id) ||  $market_id  == null )
        {
            $market_id = $_GET["market_id"];

            if( !isset($market_id) ||  $market_id  == null ) {
                $this->set_response([
                    'code' => -1,
                    'msg' => 'Please make sure the parameter market_id is valid. '
                ], REST_Controller::HTTP_BAD_REQUEST);
                exit;
            }
        }


        $this->load->model('Invoicemodel');


            $this->invoices =  $this->Invoicemodel->getInvoices('invoices');

            if ( $this->get('invoice_status') != null)
                $this->filter['invoice_status'] = $this->get('invoice_status');

            if ($this->get('is_clearing') != null)
                $this->filter['is_clearing'] = $this->get('is_clearing');

            if ($this->get('start_date') != null)
                $this->filter['start_date'] = $this->get('start_date');

            if ($this->get('end_date') != null)
                $this->filter['end_date'] = $this->get('end_date');

            if ($this->get('invoice_dpe') != null){
                if(is_array($this->get('invoice_dpe')) )
                    $this->filter['invoice_dpe'] = $this->get('invoice_dpe');
                else
                    $this->filter['invoice_dpe'] = explode(',',$this->get('invoice_dpe'));
            }

            if ($this->get('invoice_amount') != null){
                if(is_array($this->get('invoice_amount')) )
                    $this->filter['invoice_amount'] = $this->get('invoice_amount');
                else
                    $this->filter['invoice_amount'] = explode(',',$this->get('invoice_amount'));
            }
    }

    public function get_market_stat_get(){

        $result = $this->get_invoices_by_filters();

        $amount = 0 ;
        $supplier_list = array();
        $invoice_list = array();

        $this->load->model('Marketmodel');
        $market = $this->Marketmodel->getMarketStatusByCode( $this->cashpoolCode);

        foreach($result as $val){

            $amount += $val['invoice_amount'];

            if( !in_array($val['vendor_code'],$supplier_list) )
                $supplier_list[count($supplier_list)] = $val['vendor_code'];

            if( !in_array($val['inv_id'],$invoice_list) )
                $invoice_list[count($invoice_list)] = $val['inv_id'];

        }

        $result = array(
            'currency' => $market["CurrencyName"],
            'currency_sign' => $market["CurrencySign"],
            'available_amount' => $amount,
            'invoice_count'=> count($invoice_list),
            'suppliers_count'=> count($supplier_list)
        );

        $this->response([
            'code' => 1,
            'data' => $result
        ], REST_Controller::HTTP_OK);

    }

    public function sync_market_invoices_get(){
        $this->response([
            'code' => 1,
            'msg' => 'success'
        ], REST_Controller::HTTP_OK);
    }

    public function get_invoices_list_get()
    {

        $result = $this->get_invoices_by_filters();

       // if ($result && count($result) > 0) {
            $this->response([
                'code' => 1,
                'data' => array(
                    'currency' => $this->Invoicemodel->getCurrencyName(),
                    'currency_sign' =>$this->Invoicemodel->getCurrencySign(),
                    'list' => $result
                )
            ], REST_Controller::HTTP_OK);
        /*
        } else {

                $this->response([
                    'code' => 0,
                    'msg' => 'No Invoice were found'
                ], REST_Controller::HTTP_OK);
            }
        */
    }

    private function filter_status($status){

        if( isset($this->filter["invoice_status"]) && $this->filter["invoice_status"] != "all")
        {
            if( $this->filter["invoice_status"] != $status)
                return true;
            else
                return false;

        }else{
            return false;
        }

    }

    private function filter_dpe($dpe){

        if($this->filter['invoice_dpe'] != null && count($this->filter['invoice_dpe']) > 0)
        {
            foreach($this->filter['invoice_dpe'] as $v){
                switch($v){
                    case 1:
                        if($dpe > 15)
                            continue;
                        else
                            return false;
                        break;
                    case 2:
                        if($dpe < 15 || $dpe >= 30)

                            continue;
                        else
                            return false;
                        break;
                    case 3:
                        if($dpe < 30 || $dpe >= 45)
                            continue;
                        else
                            return false;
                        break;
                    case 4:
                        if($dpe < 45 )
                            continue;
                        else
                            return false;
                        break;
                    default:
                        return false;
                        break;
                }
            }
            return true;
        }else{
            return false;
        }

    }

    private function filter_original_paydate($original_paydate){

        if( isset($this->filter["start_date"]) && $this->filter["start_date"] > $original_paydate)
            return true;

        if( isset($this->filter["end_date"]) && $this->filter["end_date"] < $original_paydate)
            return true;

        return false;
    }

    private function filter_amount($amount){
        if($this->filter['invoice_amount'] != null && count($this->filter['invoice_amount']) > 0)
        {
            foreach($this->filter['invoice_amount'] as $v){
                switch($v){
                    case 1:
                        if($amount > 25000)
                            continue;
                        else
                            return false;
                        break;
                    case 2:
                        if($amount < 25000 || $amount >= 50000)
                            continue;
                        else
                            return false;
                        break;
                    case 3:
                        if($amount < 50000 || $amount >= 75000)
                            continue;
                        else
                            return false;
                        break;
                    case 4:
                        if($amount < 75000 )
                            continue;
                        else
                            return false;
                        break;
                    default:
                        return false;
                        break;
                }
            }
            return true;
        }else{
            return false;
        }

    }

    private function get_invoices_by_filters(){

        $result = array();

        foreach($this->invoices as $inv){

            if($this->filter_status($inv['invoice_status']))
                continue;
            if($this->filter_original_paydate($inv['original_paydate']))
                continue;
            if($this->filter_dpe( (strtotime($inv['original_paydate']) - strtotime(date('Y-m-d'))) / (3600*24) ) )
                continue;
            if($this->filter_amount($inv['invoice_amount']))
                continue;
            if($this->filter_status($inv['invoice_status']))
                continue;

            $result[] = $inv;

        }

        return $result;
    }

    public function set_invoices_eligiable_post(){

        $invoice = $this->post('inv_id');
        $is_eligiable = $this->post('is_eligiable');


        if( !isset($invoice) || empty($invoice) || !isset($is_eligiable) || empty($is_eligiable))
        {
            $this->set_response([
                'code' => -1,
                'data' => $this->failure
            ], REST_Controller::HTTP_BAD_REQUEST);

        } else{

            $result =  $this->Invoicemodel->update_invoices($invoice, $is_eligiable);

            if( $result) {

                $this->set_response([
                    'code' => 1,
                    'msg' => 'success'
                ], REST_Controller::HTTP_OK); // CREATED (201) being the HTTP response code
            }else{
                $this->set_response([
                    'code' => -1,
                    'msg' => 'failure to update invoice , please check.'
                ], REST_Controller::HTTP_BAD_REQUEST);
            }
        }

    }




}
