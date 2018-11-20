<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class InvoiceModel extends CI_Model {

    protected $profile ;

    protected $customerId;
    protected $cashpoolId;
    protected $currencyId;
    protected $paydate;

    protected $buyerid;
    protected $buyerName;
    private $currencyName;
    private $currencySign;

    /***
     * Invoice Entity.
     */
    protected $Invoice = array(

    );

	public function __construct()    {
        parent::__construct();

        $this->db = $this->load->database('default', true);

        $this->load->driver('cache');

    }

    //查询某市场的所有发票
    public function get_market_invoices($cashpoolCode){

        $result = $this->cache->memcached->get($cashpoolCode .'-invoice');

        if( $result == null || empty($result)) {

            $sql = "SELECT i.Id as id, 
                      i.Vendorcode as vendorcode, 
                      i.InvoiceStatus as status, 
                       i.IsIncluded as include,
                      i.InvoiceNo as invoiceno, 
                      i.InvoiceAmount as amount, 
                      i.EstPaydate as paydate
                    FROM `Customer_Payments` i                   
                    where i.CashpoolCode = '{$cashpoolCode}'
                    and i.InvoiceStatus > -2;";

            $query = $this->db->query($sql);


            $result = $query->result_array();

            $this->cache->memcached->save($cashpoolCode . '-invoice', $result, 60);

        }
        return $result;

    }

    //查询某市场的所有发票
    public function get_market_invoices_valid($cashpoolCode, $paydate){

        $result = $this->cache->memcached->get($cashpoolCode .'-invoice-valid');

        if( $result == null || empty($result)) {

            $sql = "SELECT i.Id as id, 
                      i.Vendorcode as vendorcode, 
                      i.InvoiceNo as invoiceno, 
                      i.InvoiceAmount as amount, 
                      i.EstPaydate as paydate
                    FROM `Customer_Payments` i                   
                    where i.CashpoolCode = '{$cashpoolCode}'
                    and i.InvoiceStatus = 1
                    and i.IsIncluded = 1
                    and i.EstPaydate > '{$paydate}';";

            $query = $this->db->query($sql);


            $result = $query->result_array();

            $this->cache->memcached->save($cashpoolCode . '-invoice-valid', $result, 60);

        }
        return $result;

    }

    //查询某市场中某供应商发票
    public function get_market_vendor_invoices($cashpoolCode, $vendorcode){
        $result = $this->cache->memcached->get($cashpoolCode .'-invoice');

        if( $result == null || empty($result)) {

            $sql = "SELECT i.Id as id, 
                      i.Vendorcode as vendorcode, 
                      i.InvoiceStatus as status, 
                      i.IsIncluded as include,
                      i.InvoiceNo as invoiceno, 
                      i.InvoiceAmount as amount, 
                      i.EstPaydate as paydate
                    FROM `Customer_Payments` i                   
                    where i.CashpoolCode = '{$cashpoolCode}'
                    and i.Vendorcode = '{$vendorcode}'
                    and i.InvoiceStatus > -2;";

            $query = $this->db->query($sql);

            $result = $query->result_array();
            $this->cache->memcached->save($cashpoolCode . '-invoice', $result, 60);

        }
        return $result;
    }

    //查询某市场中某供应商发票
    public function get_market_vendor_invoices_valid($cashpoolCode, $vendorcode, $paydate){
        $result = $this->cache->memcached->get($cashpoolCode .'-invoice-valid');

        if( $result == null || empty($result)) {

            $sql = "SELECT i.Id as id, 
                      i.Vendorcode as vendorcode, 
                      i.InvoiceStatus as status, 
                      i.IsIncluded as include,
                      i.InvoiceNo as invoiceno, 
                      i.InvoiceAmount as amount, 
                      i.EstPaydate as paydate
                    FROM `Customer_Payments` i                   
                    where i.CashpoolCode = '{$cashpoolCode}'
                    and i.Vendorcode = '{$vendorcode}'
                    and i.InvoiceStatus = 1
                    and i.IsIncluded = 1
                    and i.EstPaydate > '{$paydate}';";

            $query = $this->db->query($sql);

            $result = $query->result_array();
            $this->cache->memcached->save($cashpoolCode . '-invoice-valid', $result, 60);

        }
        return $result;
    }

    //设置某发票是否包含
    public function set_invoice_included($Id){

    }

    public function set_invoice_included_batch($list){

    }




		
}
