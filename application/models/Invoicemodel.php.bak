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

	public function __construct()    {
        parent::__construct();

        $this->db = $this->load->database('default', true);

        $this->load->driver('cache');

        $this->profile = $this->cache->memcached->get('profile');
    }

    public function __init($marketid){

        if(!isset($marketid) || empty($marketid)){
            return false;
        }


        $this->buyerid = $marketid;

        $sql = "select p.Id, p.MarketStatus, c.CompanyName,p.CompanyDivision, p.CurrencySign, p.CurrencyName, p.NextPaydate	
               from `Customer_Cashpool` p
               inner join `Base_Companys` c ON c.Id = p.CustomerId
               where p.CashpoolCode = '{$marketid}';
               ";

        $query = $this->db->query($sql);

        if ($query->num_rows() > 0) {
            $ret = $query->row_array();

            $this->customerId = $ret['CustomerId'];
            $this->buyerName = $ret['CompanyName'];
            $this->currencyId = $ret['CurrencyId'];
            $this->currencyName = $ret['CurrencyName'];
            $this->currencySign = $ret['CurrencySign'];

            $this->paydate = isset($ret['PayDate']) ? $ret['PayDate'] : date('Y-m-d', time());
            $this->cashpoolId = $ret['CashpoolId'];
            return true;

        }else{
            return false;
        }

    }

    public function getCurrencySign() {
        return $this->currencySign;
    }
    public function getCurrencyName() {
        return $this->currencyName;
    }
     // Get category
	public function getMarketState()
    {

        $market = $this->cache->memcached->get( $this->buyerid .'-inv-state');

        if( $market == null || empty($market)) {
            $market = array();
            $market['buyer_id'] = $this->buyerid;
            $market["currency_sign"] = $this->currencySign;
            $market["currency"] = $this->currencyName;
            $market["suppliers_count"] = 0;

            $market["invoice_count"] = 0;
            $market["available_amount"] = 0;

            $sql = "select count(Id) as cnt from `Vendors` where CashpoolCode = '{$this->buyerid}' and VendorStatus = 1  ;";

            $query = $this->db->query($sql);

            if ($query->num_rows() > 0) {

                $row = $query->row_array();

                $market["suppliers_count"] = $row['supplier'];

                $sql = "SELECT count(Id) as cnt,sum(InvoiceAmount) as amount
                        FROM `Vendors_Invoices`
                        where CashpoolCode = '{$this->buyerid}'
                        and InvoiceStatus in (0,1)
                        and EstPaydate > '" . date('Y-m-d', time()) . "';
                    ";

                $query = $this->db->query($sql);

                if ($query->num_rows() > 0) {
                    $row = $query->row_array();

                    $market["invoice_count"] = $row["cnt"];
                    $market["available_amount"] = $row["amount"];
                }

            }

            $this->cache->memcached->save( $this->buyerid .'-inv-state', $market);
        }

        return $market;
    }


    public function getInvoicesByCode($cashpoolCode){

        $data = $this->cache->memcached->get( $cashpoolCode .'-inv-list');

        if( $data == null || empty($data) ) {

            $sql = "SELECT i.Vendorcode, i.Id, i.InvoiceStatus, i.InvoiceNo, i.InvoiceAmount, i.EstPaydate
                     FROM `Customer_Payments` i                        
                     where i.CashpoolCode = '{$cashpoolCode}'                        
                    ";

            $query = $this->db->query($sql);
            $data = array();

            if ($query->num_rows() > 0) {

                $ret = $query->result_array();

                foreach ($ret as $row) {
                    $inv = array();
                    $inv['inv_id'] = $row['Id'];

                    $inv['vendor_code'] = $row['Vendorcode'];
                    $inv['invoice_no'] = $row['InvoiceNo'];
                    $inv['original_paydate'] = $row['EstPaydate'];
                    $inv['invoice_amount'] = $row['InvoiceAmount'];


                    if ($row['InvoiceStatus'] == -1 || $row['EstPaydate'] < date('Y-m-d', time()))
                        $inv['invoice_status'] = "ineligible";
                    else if ($row['InvoiceStatus'] == 0)
                        $inv['invoice_status'] = "adjustments";
                    else if ($row['InvoiceStatus'] == 1)
                        $inv['invoice_status'] = "eligible";
                    else if ($row['InvoiceStatus'] == 2)
                        $inv['invoice_status'] = "awarded";

                    $data[] = $inv;
                }
                $this->cache->memcached->save( $cashpoolCode .'-inv-list', $data);
            }
        }

        return $data;

    }


    public function getInvoices(){

        $data = null;

        if( $data == null || empty($data) ) {

            $sql = "SELECT v.Supplier, i.Vendorcode, i.Id, i.InvoiceStatus, i.InvoiceNo, i.InvoiceAmount, i.EstPaydate
                        FROM `Customer_Payments` i
                        INNER JOIN `Customer_Suppliers` v ON v.CashpoolCode = i.CashpoolCode AND v.Vendorcode = i.Vendorcode 
                        where i.CashpoolCode = '{$this->buyerid}'  
                        and i.InvoiceStatus > -2;                      
                    ";

            $query = $this->db->query($sql);
            $data = array();

            if ($query->num_rows() > 0) {

                $ret = $query->result_array();

                foreach ($ret as $row) {
                    $inv = array();
                    $inv['inv_id'] = $row['Id'];

                    $inv['supplier_name'] = $row['Supplier'];
                    $inv['vendor_code'] = $row['Vendorcode'];
                    $inv['invoice_no'] = $row['InvoiceNo'];
                    $inv['original_paydate'] = $row['EstPaydate'];
                    $inv['invoice_amount'] = $row['InvoiceAmount'];


                    if ($row['InvoiceStatus'] == -1 || $row['EstPaydate'] < date('Y-m-d', time()))
                        $inv['invoice_status'] = "ineligible";
                    else if ($row['InvoiceStatus'] == 0)
                        $inv['invoice_status'] = "adjustments";
                    else if ($row['InvoiceStatus'] == 1)
                        $inv['invoice_status'] = "eligible";
                    else if ($row['InvoiceStatus'] == 2)
                        $inv['invoice_status'] = "awarded";

                    $data[] = $inv;
                }

                #$this->cache->memcached->save( $this->buyerid .'-inv-list', $data);
            }
        }

        return $data;

    }

    public function update_invoices($invoice, $is_eligiable ){

        $sql = "UPDATE `Vendors_Invoices` SET `InvoiceStatus` = '{$is_eligiable}' WHERE Id ";

        if( is_array($invoice) && count($invoice) > 0 ){
            $sql .= " in (" . implode(',',$invoice).");";
        }else{
            $sql .= " = {$invoice};";
        }

        $this->db->query($sql);//INSERT 新的开价

        if (!$this->db->affected_rows()) {
            $this->db->trans_rollback();
            return false;
        }else{
            $this->db->trans_commit();
            $data = $this->cache->memcached->save( $this->buyerid .'-inv-list', null);
            return true;
        }

    }

		
}
