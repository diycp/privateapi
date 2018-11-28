<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class MarketModel extends CI_Model {

    protected $profile ;

	public function __construct()    {
        parent::__construct();
        $this->db = $this->load->database('default', true);
        $this->load->driver('cache');

        #$this->profile = $this->cache->memcached->get('profile');
    }

    public function init($profile){
        $this->profile = $profile;
    }

    public function getMarketServiceTime(){
        $sql = "select StartTime,EndTime 
              from Customer_Cashpool_Service where `ServiceStatus` = 1;";

        $query = $this->db->query($sql);

        $result = $query ->row_array();

        return $result;
    }

     // Get category
	public function getMarkets(){


            $markets = array();

            $sql = "select p.Id, p.CashpoolCode, c.companyname, p.CompanyDivision , p.MarketStatus, p.CurrencySign, p.CurrencyName, p.MiniAPR, p.ExpectAPR, p.NextPaydate
              , IFNULL(p.AutoAmount,0) as AvaAmount  ,IFNULL(AvailableAmount, 0) as TotalAmount
                from  `Customer_Cashpool` p 
                inner join `Base_Companys` c ON c.Id = p.CompanyId
                where p.MarketStatus >= 0
                ORDER BY `c`.`CreateTime` ASC
            ";

            $query = $this->db->query($sql);

            //print_r($this->db->last_query()); die;

            if ($query->num_rows() > 0) {
                $result = $query->result_array();

                foreach ($result as $row) {
                    $market = array();

                    $cashpoolId = $row["Id"];
                    $market["market_id"] = $row['CashpoolCode'];
                    $market["market_status"] = intval($row['MarketStatus']);
                    $market["market_name"] = $row['CompanyDivision'];
                    $market["division_name"] = $row['CompanyDivision'];
                    $market["currency_sign"] = $row['CurrencySign'];
                    $market["currency"] = $row['CurrencyName'];
                    $market["expect_apr"] = $row['ExpectAPR'];
                    $market["min_apr"] = $row['MiniAPR'];
                    $market['paydate'] = isset($row['NextPaydate']) && $row['NextPaydate'] != null ? $row['NextPaydate'] : '-';
                    $market['cash_total'] = $row['TotalAmount'];
                    $market['cash_available'] = $row['AvaAmount'];


                    $stat = $this->getMarketStat( $row );       //获得市场当日的清算统计

                    $market['paid_total'] = $stat['total']['available_amount'];          //需要支付的发票金额
                    $market['paid_eligible'] = $stat['total']['available_amount'];       //有效清算的发票金额
                    $market['cash_deployed'] = $stat['clearing']['available_amount'];       //当日资金分配
                    $market['income'] = $stat['clearing']['discount_amount'];               //当日资金清算获得的折扣
                    $market['average_apr'] = $stat['clearing']['average_apr'];         //当日资金清算获得的平均年化率
                    $market['average_dpe'] = $stat['clearing']['average_dpe'];         //当日资金清算发票的平均早付天数

                    $market["market_status"] = $stat['total']['available_amount'] > 0 ? $market["market_status"] : -1;
                    $markets[] = $market;
                }

            }

        array_multisort(array_column($markets,'market_status'),SORT_DESC,$markets);
        return $markets;

	}    
    
	public function setMarketActive($marketid, $active_status = 1 )
	{

        $active_status = $active_status == 1 ? 1 : 0 ;

        $sql = "UPDATE `Customer_Cashpool` SET MarketStatus = '{$active_status}' WHERE CashpoolCode = '{$marketid}';";

        $this->db->query($sql);

        if (!$this->db->affected_rows()) {
            $this->db->trans_rollback();
            return false;
        }

        if ($this->db->trans_status() === TRUE) {
            $this->db->trans_commit();
            $this->cache->memcached->save( 'markets', null) ;
            return true;
        }

        return false;


	}

    private function get_offers($cashpoolId)
    {
        $sql = "  select b.Vendorcode,b.BidRate,b.ResultRate,b.MinAmount
                from Supplier_Bids b
                where b.CashpoolId = {$cashpoolId}
                and b.BidStatus >= 0 and b.BidRate > 0";

        $query = $this->db->query($sql);
        $result = $query->result_array();

        $offers = array();

        foreach ( $result as $val){
            $offers[$val["Vendorcode"]] = array(
                "offerAPR" => $val["BidRate"],
                "getAPR" => $val["ResultRate"],
                "minPaid" => floatval($val["MinAmount"]),
            );
        }

        return $offers;
    }
    private function get_invoice($cashpoolCode, $paydate)
    {
        $invoices = array();

        $sql = "SELECT p.Id, p.Vendorcode, p.InvoiceNo, p.InvoiceAmount, p.EstPaydate  
                    FROM `Customer_Payments` p                    
                    WHERE p.EstPaydate > '" . $paydate . "' 
                    AND p.InvoiceStatus = 1
                    AND p.IsIncluded = 1
                    AND p.CashpoolCode = '{$cashpoolCode}'
                    Order by p.Vendorcode; ";


        $query = $this->db->query($sql);

        $result = $query->result_array();

        foreach ($result as $row) {

            $invoices[] =
                array(
                    "Id" => intval($row["Id"]),
                    "Vendorcode" =>  $row["Vendorcode"],
                    "InvoiceNo" => $row["InvoiceNo"],
                    "InvoiceAmount" => $row["InvoiceAmount"],
                    "EstPaydate" => $row["EstPaydate"],
                    "Dpe" => (strtotime($row['EstPaydate']) - strtotime($paydate)) / 86400
                );
        }

        return $invoices;
    }
    private function get_awards($cashpoolId)
    {

        $awards = array();

        $sql = "SELECT p.InvoiceId, p.PayDpe, p.PayDiscount, p.PayAmount  
                    FROM `Customer_PayAwards` p                    
                    WHERE p.AwardDate = '" . date("Y-m-d", time()) . "' 
                    AND p.AwardStatus >= 0
                    AND p.CashpoolId = '{$cashpoolId}'; ";

        $query = $this->db->query($sql);

        $result = $query->result_array();

        foreach ($result as $row) {

            $awards[$row["InvoiceId"]] = array(
                "dpe" =>  $row["PayDpe"],
                "discount" => $row["PayDiscount"],
                "amount" => $row["PayAmount"]

            );
        }

        return $awards;

    }

    public function getMarketStat($_market){

        //print_r($this->db->last_query()); die;
        $market = array();

        if(isset($_market) && $_market != null && is_array($_market) ) {

            $paydate = $_market['NextPaydate'];

            $market['currency'] = $_market['CurrencyName'];
            $market['currency_sign'] = $_market['CurrencySign'];

            $market['total'] = array(
                'available_amount' => 0,
                'discount_amount' => 0,
                'average_dpe'=> 0 ,
                'average_apr'=> 0
                );

            $market['nonclearing'] = array(
                'available_amount'=> 0,
                'discount_amount' => 0,
                'average_dpe'=> 0,
                'average_apr'=> 0,
                'list' => array()
            );

            $market['clearing'] = array(
                'available_amount'=> 0,
                'discount_amount' => 0,
                'average_dpe'=> 0,
                'average_apr'=> 0,
                'list' => array()
            );


            if($paydate == null || empty($paydate))
            {
                $paydate = date("Y-m-d", time()+3600);
                $market['market_status'] = -1;

            }else{

                $market['market_status'] = $_market['MarketStatus'];

            }

            /*
             * 这一段代码主要是处理有效的供应商发票的预成交统计
             */
            $offers = $this->get_offers($_market["Id"]);
            $invoices = $this->get_invoice( $_market["CashpoolCode"] , $paydate );
            $awards = $this->get_awards($_market["Id"]);

            foreach ($offers as $key => $item) {

                foreach ($invoices as $inv) {

                    if ($key == $inv["Vendorcode"]) {

                        $market['total']["average_dpe"] += $inv["Dpe"];

                        if (isset( $awards[$inv["Id"]])) {

                            $market['clearing']["list"][] = array( 'dpe' => $inv["Dpe"], 'discount'=> $awards[$inv["Id"]]["discount"]);
                            $market['clearing']["average_dpe"] += $inv["Dpe"];
                            $market['clearing']["available_amount"] += $inv["InvoiceAmount"];
                            $market['clearing']["discount_amount"] += $awards[$inv["Id"]]["discount"];

                        } else {

                            $discount = round($inv["InvoiceAmount"] * $inv["Dpe"] * floatval($item["offerAPR"])/365/100  ,2 );

                            $market['nonclearing']["list"][] = array( 'dpe' => $inv["Dpe"], 'discount'=> $discount);
                            $market['nonclearing']["average_dpe"] += $inv["Dpe"];
                            $market['nonclearing']["available_amount"] += $inv["InvoiceAmount"];
                            $market['nonclearing']["discount_amount"] += $discount;
;
                        }
                    }
                }
            }


            $market['total']["available_amount"] = $market['clearing']["available_amount"] + $market['nonclearing']["available_amount"];
            $market['total']["discount_amount"] = $market['clearing']["discount_amount"] + $market['nonclearing']["discount_amount"];

            $market['clearing']["average_dpe"] = count($market['clearing']["list"]) > 0 ? round( $market['clearing']["average_dpe"] /count($market['clearing']["list"]),1 ) : 0 ;
            $market['nonclearing']["average_dpe"] = count($market['nonclearing']["list"]) > 0 ? round( $market['nonclearing']["average_dpe"] /count($market['nonclearing']["list"]),1 ) : 0 ;


            $market['total']["average_dpe"] = count($market['nonclearing']["list"]) > 0 || count($market['clearing']["list"]) > 0 ?
                round(   $market['total']["average_dpe"]  / (count($market['nonclearing']["list"]) + count($market['clearing']["list"]) ), 1) : 0 ;

            $avg_apr = 0;

            foreach( $market['clearing']["list"] as $val)
            {
                $market["clearing"]["average_apr"] += round($val['discount']/$val['dpe']*365*100/ $market['clearing']["available_amount"], 2);
                $avg_apr  += round($val['discount']/$val['dpe']*365*100/ $market['total']["available_amount"], 2);
            }

            foreach( $market['nonclearing']["list"] as $val)
            {
                $market["nonclearing"]["average_apr"] += round($val['discount']/$val['dpe']*365*100/ $market['nonclearing']["available_amount"], 2);
                $avg_apr  += round($val['discount']/$val['dpe']*365*100/ $market['total']["available_amount"], 2);
            }

            $market['total']["average_apr"] = $avg_apr;

        }

        return $market;

    }

    public function getCurrentMarketGraph($marketid){

        //print_r($this->db->last_query()); die;
        $market = array();

        $result = $this->getMarketStatusByCode($marketid);

        $market['currency'] = $result['CurrencyName'];
        $market['currency_sign'] = $result['CurrencySign'];
        
        //获取每日统计
        $sql = "SELECT AwardDate,a.PayAmount as amount, a.PayDiscount as discount, a.AvgDpe as dpe
                    FROM `Customer_DailyAwards` a                           
                    where a.CashpoolCode = '{$marketid}' 
                    ORDER BY AwardDate;
                ";

        $query = $this->db->query($sql);

        $data = array();

        if($query->num_rows() >0 ) {
            $result = $query->result_array();

            foreach($result as $row){

                if ($row['amount'] != null && $row['amount'] > 0) {
                    $data[] = array(
                        'date' => date('d/m', strtotime($row['AwardDate']) ) ,
                        'discount_amount' => $row['discount'],
                        'average_apr' => $row['dpe'] > 0 ? round($row['discount'] / $row['dpe'] * 365 * 100 / $row['amount'], 2) : 0
                    );
                }

            }
        }
        $market['list'] = $data;
        return $market;
    }

    public function getMarketStatusByCode($cashpoolCode){

        $query = $this->db->query(" select * from Customer_Cashpool where CashpoolCode = '{$cashpoolCode}';") ;

        return $query->row_array();

    }

    public function getCurrentMarketStat($marketid){


        $result =  $this->getMarketStatusByCode($marketid)  ;

        $market = array();

        $market['currency'] = $result['CurrencyName'];
        $market['currency_sign'] = $result['CurrencySign'];
        $paydate = $result ["NextPaydate"];
        $cashpoolId = $result["Id"];

        $sql = "SELECT   sum(a.PayAmount) as amount, sum(a.PayDiscount) as discount, avg(a.PayDpe) as dpe,
                      sum(CASE WHEN a.IsManual = 1 then 0 ELSE a.PayAmount END) as c_amount ,
                      sum(CASE WHEN a.IsManual = 1 then 0 ELSE a.PayDiscount END) as c_discount, 
                      avg(CASE WHEN a.IsManual = 1 then 0 ELSE a.PayDpe END) as c_dpe,
                      sum(CASE WHEN a.IsManual = 1 then a.PayAmount ELSE 0  END) as m_amount ,
                      sum(CASE WHEN a.IsManual = 1 then a.PayDiscount ELSE 0 END) as m_discount, 
                      avg(CASE WHEN a.IsManual = 1 then a.PayDpe ELSE  0 END) as m_dpe
                    FROM `Customer_PayAwards` a      
                    where a.CashpoolCode = '{$marketid}'      
                    and a.AwardStatus = 0 and a.AwardDate = '".date("Y-m-d", time())."'
                ";

        $query = $this->db->query($sql);

        $discount = array();

        if($query->num_rows() >0 ) {
            $row = $query->row_array();

                if ($row['amount'] != null && $row['amount'] > 0) {
                    $discount['total'] = array(
                        'discount_amount' => $row['discount'],
                        'average_dpe' => round($row['dpe'],1),
                        'average_apr' => round($row['discount'] / $row['dpe'] * 365 * 100 / $row['amount'], 2)
                    );
                }else{
                    $discount['total'] = array(
                        'discount_amount' => 0,
                        'average_dpe' => 0,
                        'average_apr' => 0
                    );
                }

                if ($row['m_amount'] != null && $row['m_amount'] > 0) {
                    $discount['manual'] = array(
                        'discount_amount' => $row['m_discount'],
                        'average_dpe' => round($row['m_dpe'],1),
                        'average_apr' => round($row['m_discount'] / $row['m_dpe'] * 365 * 100 / $row['m_amount'], 2)
                    );
                }else{
                    $discount['manual'] = array(
                        'discount_amount' => 0,
                        'average_dpe' => 0,
                        'average_apr' => 0
                    );
                }

                if ($row['c_amount'] != null && $row['c_amount'] > 0) {
                    $discount['clearing'] = array(
                        'discount_amount' => $row['c_discount'],
                        'average_dpe' => round($row['c_dpe'],1),
                        'average_apr' => round($row['c_discount'] / $row['c_dpe'] * 365 * 100 / $row['c_amount'], 2)
                    );
                }else{
                    $discount['clearing'] = array(
                        'discount_amount' => 0,
                        'average_dpe' => 0,
                        'average_apr' => 0
                    );
                }


        }

        $market['discount'] = $discount;


        $amount = array();


        $sql = "SELECT COUNT(DISTINCT i.Vendorcode) as supplier , count(o.Id) as invoice, sum(o.PayAmount) as amount
                FROM `Customer_PayAwards` o   
                inner join `Customer_Payments` i ON i.Id = o.InvoiceId               
                where o.CashpoolCode = '{$marketid}' AND o.AwardStatus >= 0   and o.AwardDate = '".date("Y-m-d", time())."' ;
                ";

        $query = $this->db->query($sql);


        $ret = $query->row_array();

        $amount['total'] = array(
            'available_amount' => isset($ret['amount']) ? $ret['amount'] : 0 ,
            'supplier_count' => isset($ret['supplier']) ? $ret['supplier'] : 0 ,
            'invoice_count' => isset($ret['invoice']) ? $ret['invoice'] : 0
        );


        $sql = "SELECT COUNT(DISTINCT o.Vendorcode) as supplier , count(o.Id) as invoice, sum(o.PayAmount) as amount
                FROM `Customer_PayAwards` o 
                where o.CashpoolCode = '{$marketid}' and o.AwardStatus >= 0 and o.AwardDate =  '".date("Y-m-d", time())."';
                ";

        $query = $this->db->query($sql);


        $amount['current'] = array(
            'available_amount' => isset($ret['amount']) ? $ret['amount'] : 0 ,
            'supplier_count' => isset($ret['supplier']) ? $ret['supplier'] : 0 ,
            'invoice_count' => isset($ret['invoice']) ? $ret['invoice'] : 0
        );

        $sql = "SELECT COUNT(DISTINCT i.Vendorcode) as supplier , count(i.Id) as invoice, sum(i.InvoiceAmount) as amount
                FROM  `Customer_Payments` i 
                INNER JOIN `Supplier_Bids` b ON b.Vendorcode = i.Vendorcode and b.BidStatus >=0 and b.BidRate > 0 and b.CashpoolId = '{$cashpoolId}'
                left join `Customer_PayAwards` a ON a.InvoiceId = i.Id AND a.AwardStatus >= 0 and a.AwardDate = '".date("Y-m-d", time())."'
                where i.IsIncluded = 1 and i.InvoiceStatus = 1 and  i.CashpoolCode = '{$marketid}' and i.EstPaydate > '{$paydate}' 
                and a.Id IS NULL  ;
                ";

        $query = $this->db->query($sql);

        $ret = $query->row_array();

        $amount['pending'] = array(
            'available_amount' =>   $result["AutoAmount"] - $amount['current']['available_amount'] + $discount['total']['discount_amount'],
            'supplier_count' => isset($ret['supplier']) ? $ret['supplier'] : 0,
            'invoice_count' => isset($ret['invoice']) ? $ret['invoice'] : 0
        );

        $market['available_amount'] = $amount;

        return $market;
    }

    public function getMarketSupplierStat($marketid){

        $sql = 		"select   p.Id, p.marketstatus, p.currencysign, p.currencyname , p.NextPaydate as paydate
                from  `Customer_Cashpool` p 
                where p.CashpoolCode = '{$marketid}' ; ";

        $query = $this->db->query($sql);

        //print_r($this->db->last_query()); die;
        $market = array();
        $paydate = date("Y-m-d", time()) ;

        if($query->num_rows() >0 ) {
            $result = $query->row_array();
            $paydate = $result["paydate"];
            $market['currency'] = $result['currencyname'];
            $market['currency_sign'] = $result['currencysign'];
            $market["id"] = $result["Id"];
        }

        $sql = "SELECT   count(DISTINCT a.Vendorcode) as supplierCnt, 
                      sum(CASE WHEN i.Vendorcode IS NOT NULL THEN 1 ELSE 0 END) as apCnt                    
                    FROM `Customer_Suppliers` a  
                    left join ( 
                        SELECT DISTINCT Vendorcode 
                         FROM `Customer_Payments` 
                         WHERE InvoiceStatus = 1  and IsIncluded = 1
                         and EstPaydate > '{$paydate}'
                         and CashpoolCode = '{$marketid}' 
                     ) i ON i.Vendorcode = a.Vendorcode 
                WHERE a.CashpoolCode = '{$marketid}'  ;
                " ;
        $query = $this->db->query($sql);

        if($query->num_rows() >0 ) {
            $row = $query->row_array();
            $market['total'] = array(
                'count' => $row['supplierCnt'],
                'count_ap' => $row['apCnt']
            );
        }else{
            $market['total'] = array(
                'count' => 0,
                'count_ap' => 0
            );
        }

        $sql = "SELECT count(DISTINCT a.Vendorcode) as supplierCnt, 
                      sum(CASE WHEN i.Vendorcode IS NOT NULL THEN 1 ELSE 0 END) as apCnt                     
                    FROM `Customer_Suppliers` a  
                    INNER JOIN `Customer_Suppliers_Users` u ON u.SupplierId = a.Id AND u.UserStatus = 1
                    left join ( 
                        SELECT DISTINCT Vendorcode 
                         FROM `Customer_Payments` 
                         WHERE InvoiceStatus = 1 and IsIncluded = 1
                         and EstPaydate > '{$paydate}'
                         and CashpoolCode = '{$marketid}' 
                     ) i ON i.Vendorcode = a.Vendorcode 
                WHERE a.CashpoolCode = '{$marketid}'  ;
                " ;

        $query = $this->db->query($sql);

        if($query->num_rows() >0 ) {
            $row = $query->row_array();
            $market['registerd'] = array(
                'count' => $row['supplierCnt'],
                'count_ap' => $row['apCnt']
            );
        }else{
            $market['registerd'] = array(
                'count' => 0,
                'count_ap' => 0
            );
        }

        $sql = "SELECT count(DISTINCT a.Vendorcode) as supplierCnt, 
                      sum(CASE WHEN i.Vendorcode IS NOT NULL THEN 1 ELSE 0 END) as apCnt    
                    FROM `Supplier_Bids` a
                    left join  ( 
                        SELECT DISTINCT Vendorcode 
                         FROM `Customer_Payments` 
                         WHERE InvoiceStatus = 1 and IsIncluded = 1
                         and EstPaydate > '{$paydate}'
                         and CashpoolCode = '{$marketid}' 
                     ) i ON i.Vendorcode = a.Vendorcode 
                    where a.CashpoolId = '{$market["id"]}' and a.BidStatus >= 0 and a.BidRate > 0 ;                 
                ";

        $query = $this->db->query($sql);

        if($query->num_rows() >0 ) {
            $row = $query->row_array();
            $market['particpated'] = array(
                'count' => $row['supplierCnt'],
                'count_ap' => $row['apCnt']
            );
        }else{
            $market['particpated'] = array(
                'count' => 0,
                'count_ap' => 0
            );
        }

        $sql = "SELECT v.Supplier , sum(i.InvoiceAmount) as Amount
                FROM `Customer_Payments` i 
                inner join `Customer_Suppliers` v ON v.vendorcode = i.vendorcode AND v.CashpoolCode = '{$marketid}'                 
                where i.CashpoolCode = '{$marketid}'  
                AND i.InvoiceStatus = 1 AND i.IsIncluded = 1  AND  i.EstPayDate > '{$paydate}'
                 group by v.Supplier
                ORDER BY Amount DESC
                limit 5;
                ";

        $query = $this->db->query($sql);
        $payment = array();

        if($query->num_rows() >0 ) {
            $ret = $query->result_array();

            foreach($ret as $row){
                $payment[$row['Supplier']] = $row['Amount'];
            }
        }
        $market['top_by_ap'] = $payment;

        $sql = "SELECT v.Supplier , sum(o.PayDiscount) as Income
                FROM `Customer_PayAwards` o   
                inner join `Customer_Suppliers` v ON v.Vendorcode = o.Vendorcode AND v.CashpoolCode = '{$marketid}'                 
                where o.CashpoolCode = '{$marketid}'  AND o.AwardStatus = 0 AND o.AwardDate = '".date("Y-m-d", time())."'
                GROUP BY v.Supplier
                ORDER BY Income DESC 
                limit 5;
                ";

        $query = $this->db->query($sql);
        $income = array();

        if($query->num_rows() >0 ) {
            $ret = $query->result_array();

            foreach($ret as $row){
                $income[$row['Supplier']] = $row['Income'];
            }
        }
        $market['top_by_income'] = $income;

        return $market;
    }

    public function  getMarketSetting($marketid)
    {

        $market = array();

        $result = $this->getMarketStatusByCode($marketid);

        $sql = "SELECT  Id, AllocateStatus, AllocateAmount,  AllocateDate  
                FROM `Customer_Cashpool_Allocate` 
                WHERE AllocateStatus in (0,1) and AllocateAmount > 0 
                AND CashpoolCode = '{$marketid}' 
                ORDER BY AllocateDate;	";

        $query = $this->db->query($sql);

        $allocates = [] ;

        if($query->num_rows() > 0) {

            $rst = $query->result_array();

            foreach( $rst as $v){
                $allocates[] = array(
                    "allocate_id" => $v["Id"] ,
                    "status" => $v["AllocateStatus"] ,
                    "paydate" => $v["AllocateDate"],
                    "cashamount" => $v["AllocateAmount"]
                );
            }

        }

        $market = ["market_id" => $marketid,
            "market_status" => $result['MarketStatus'],
            "market_name" => $result['CompanyDivision'],
            "currency" => $result["CurrencyName"],
            "currency_sign" => $result["CurrencySign"],
            "expect_apr" => $result["ExpectAPR"],
            "min_apr" => $result["MiniAPR"],
            "reserve_percentage" => $result["ReservePercent"],
            "reconcilation_date" =>  $result["ReconciliationDate"],
            "market_cash" => isset($result["AutoAmount"]) ? $result["AutoAmount"] : 0,
            "paydate" => isset($result["NextPaydate"]) ? $result["NextPaydate"] : "",
            "allocate_list" => $allocates
        ];


        return $market;
    }

    private function get_uuid(){
        $query = $this->db->query("select UUID_SHORT() as uId");
        $result = $query->row_array();
        return $result["uId"];
    }

    public function  setMarketSetting($marketid, $setting)
    {

        $market = array();

        $sql = "SELECT *  FROM `Customer_Cashpool`  where CashpoolCode = '{$marketid}'; ";

        $query = $this->db->query($sql);

        if ($query->num_rows() > 0) {

            $market = $query->row_array();

            IF (isset($market["Id"]) && !empty($market["Id"]) && $market["Id"] > 0) {

                $sql = "";


                $sql .= "ExpectAPR = {$setting["expect_apr"]}" ;
                $sql .= ",MiniAPR = {$setting["min_apr"]}" ;
                $sql .= ",ReservePercent = {$setting["reserve_percentage"]}" ;
                $sql .= ",ReconciliationDate = '{$setting["reconcilation_date"]}'" ;

                $cash = floatval($market["AvailableAmount"]);

				$update_sql = "";                
				$insert_sql = "";
				
                if ( isset( $setting["market_cash"]) && !empty($setting["market_cash"]) && floatval($setting["market_cash"]) != floatval($market["AutoAmount"]) )
                {
                    $uuid  = $this->get_uuid();

                    $cash = $cash  + floatval($setting["market_cash"]) - floatval($market["AutoAmount"]);

                    $sql .= ",AutoAmount = {$setting["market_cash"]}" ;
                    $sql .= ",AvailableAmount = {$cash} ";
                    $sql .= ",AllocateId = {$uuid} " ;


                    $update_sql = "UPDATE `Customer_Cashpool_Allocate`  SET AllocateStatus = -1,LastUpdateTime= NOW()
                              WHERE Id = '{$market["AllocateId"]}' ; ";
							  
					$insert_sql = "INSERT INTO Customer_Cashpool_Allocate(Id, CreateTime,CreateUser,CashpoolCode,AllocateStatus,AllocateAmount,AllocateDate,PreAllocateId) \n
						  SELECT {$uuid}, NOW(), '{$this->profile["email"]}',CashpoolCode, 1 , {$cash}, AllocateDate,Id FROM  Customer_Cashpool_Allocate \n  
                          WHERE Id = '{$market["AllocateId"]}'; ";
							
							
                }

                if (
                    (isset( $setting["market_cash"]) && !empty($setting["market_cash"]) && floatval($setting["market_cash"]) != floatval($market["AutoAmount"]) )
                    ||
                    (isset( $setting["reserve_percentage"]) && !empty($setting["reserve_percentage"]) && floatval($setting["reserve_percentage"]) != floatval($market["ReservePercent"]))
                ) {
                    $remainCash = round($cash * $setting["reserve_percentage"]/100, 2);
                    $sql .= ",ManualAmount = {$remainCash} ";
                }

                if( strlen($sql) > 5) {

                    $this->db->trans_strict(FALSE);
                    $this->db->trans_begin();

                    try{


                        $sql = "UPDATE `Customer_Cashpool` \n
                        SET {$sql}                      
                        WHERE Id = '{$market["Id"]}' ; ";

                        $result = $this->db->query($sql);

                        if( $result && strlen($update_sql) > 5 ) {
                            $result = $this->db->query($update_sql);
							
							if( $result){
								$result = $this->db->query($insert_sql);
							}
							
                        }

                        if( $result) {
                            $this->db->trans_commit();
                        }else{
                            $this->db->trans_rollback();
                        }

                    }catch(Exception $ex){

                        $this->db->trans_rollback();

                    }finally{


                        $this->db->close();
                        return $result;
                    }



                }

                return true;

            } ELSE {

                return false;
            }
        } else {
            return false;
        }

    }

    public function  setMarketAllocate($marketid, $allocates)
    {

        $market = array();

        $sql = "SELECT *  FROM `Customer_Cashpool`  where CashpoolCode = '{$marketid}'; ";

        $query = $this->db->query($sql);

        if ($query->num_rows() > 0) {

            $market = $query->row_array();

            IF (isset($market["Id"]) && !empty($market["Id"]) && $market["Id"] > 0) {


                try {

                    //采用 Codeigniter 事务的手动模式
                    $this->db->trans_strict(FALSE);
                    $this->db->trans_begin();

                    foreach ($allocates as $item) {

                        if( !key_exists("paydate", $item) || !key_exists("cashamount", $item))
                            break;

                        if ($item["id"] > 0) {

                            $sql = "UPDATE `Customer_Cashpool_Allocate`  SET AllocateAmount = {$item["cashamount"]},AllocateDate = '{$item["paydate"]}'
                                  WHERE Id = '{$item["id"]}' AND AllocateStatus < 2 ; ";

                        } else {
                            $sql = "INSERT INTO Customer_Cashpool_Allocate(Id, CreateTime,CreateUser,CashpoolCode,AllocateStatus,AllocateAmount,AllocateDate)VALUES \n
                                (UUID_SHORT(), NOW(), '{$this->profile["email"]}','{$market["CashpoolCode"]}', 0 , {$item["cashamount"]}, '{$item["paydate"]}' );
                            ";
                        }

                        $rst = $this->db->query($sql);

                        if (!$rst) {
                            $this->db->trans_rollback();
                            return false;
                        }
                    }

                    $this->db->trans_commit();
                    return true;
                }catch(Exception $ex){
                    $this->db->trans_rollback();
                }

            } ELSE {

                return false;
            }
        } else {
            return false;
        }

    }

    public function deleteMarketAllocate($allocateId){

       $sql = "UPDATE  `Customer_Cashpool_PaySchedule` SET AllocateStatus = -1  where AllocateStatus = 0 AND Id = '{$allocateId}'; ";

        #$this->db->update('Customer_Cashpool_PaySchedule', array('AllocateStatus'=> -1), array( 'AllocateStatus'=> 0 , 'Id' => '{$allocateId}')) ;

        #$rst = $this->db->affected_rows();

        $rst = $this->db->query($sql);

        if ($rst) {
            return true;
        }else{
            return false;
        }
    }

}
