<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class MarketModel extends CI_Model {

    protected $profile ;

	public function __construct()    {
        parent::__construct();

        $this->db = $this->load->database('default', true);
        $this->load->driver('cache');

        #$this->profile = $this->cache->memcached->get('profile');
    }

    //查询所有打开的市场
    public function get_markets_open(){

        $result = $this->cache->memcached->get('markets-open');

        if( $result == null || empty($result)) {

            $sql = "select 
              p.Id, 
              p.CashpoolCode,    
              p.CompanyId,
              p.CompanyDivision , 
              p.MarketStatus, 
              p.CurrencySign, 
              p.CurrencyName, 
              p.MiniAPR, 
              p.ExpectAPR, 
              p.NextPaydate, 
              IFNULL(p.AutoAmount,0) as AvailableAmount ,
              IFNULL(AvailableAmount, 0) as TotalAmount,
              IFNULL(AllocateId,0) as AllocateId,
              ReservePercent,
              ReconciliationDate
              from  `Customer_Cashpool` p                 
              where p.MarketStatus = 1
              ORDER BY p.NextPaydate ASC;";

            $query = $this->db->query($sql);

            $result = array();
            $rst = $query->result_array();

            foreach( $rst as $item){
                $result[$item["CashpoolCode"]] = array(
                    "id" => $item["Id"],
                    "company_id" => $item["CompanyId"],
                    "market_name" => $item["CompanyDivision"],
                    "market_status" => $item["MarketStatus"],
                    "currency_sign" => $item["CurrencySign"],
                    "currency" => $item["CurrencyName"],
                    "mini_apr" => $item["MiniAPR"],
                    "expect_apr" => $item["ExpectAPR"],
                    "paydate" => $item["NextPaydate"],
                    "available_amount" => $item["AvailableAmount"],
                    "total_amount" => $item["TotalAmount"],
                    "allocate_id" => $item["AllocateId"],
                    "reserve_percent" => $item["ReservePercent"],
                    "reconciliation_date" => $item["ReconciliationDate"]
                );
            }

            $this->cache->memcached->save( 'markets-open', $result, 360);

        }
        return $result;
    }

    //查询某市场
    public function get_market_open_by_code($cashpoolCode){

        $result = $this->cache->memcached->get('markets-open');

        if( $result == null || empty($result)) {

           $this->get_markets();

            $result = $this->cache->memcached->get('markets-open');
        }

        return $result[$cashpoolCode];
    }



    //查询所有打开的市场
    public function get_markets_close(){

        $result = $this->cache->memcached->get('markets-close');

        if( $result == null || empty($result)) {

            $sql = "select 
              p.Id, 
              p.CashpoolCode,    
              p.CompanyId,
              p.CompanyDivision , 
              p.MarketStatus, 
              p.CurrencySign, 
              p.CurrencyName, 
              p.MiniAPR, 
              p.ExpectAPR, 
              p.NextPaydate, 
              IFNULL(p.AutoAmount,0) as AvailableAmount ,
              IFNULL(AvailableAmount, 0) as TotalAmount,
              IFNULL(AllocateId,0) as AllocateId,
              ReservePercent,
              ReconciliationDate
              from  `Customer_Cashpool` p                 
              where p.MarketStatus in ( 0 , -1)
              ORDER BY p.NextPaydate ASC;";

            $query = $this->db->query($sql);

            $result = array();
            $rst = $query->result_array();

            foreach( $rst as $item){
                $result[$item["CashpoolCode"]] = array(
                    "id" => $item["Id"],
                    "company_id" => $item["CompanyId"],
                    "market_name" => $item["CompanyDivision"],
                    "market_status" => $item["MarketStatus"],
                    "currency_sign" => $item["CurrencySign"],
                    "currency" => $item["CurrencyName"],
                    "mini_apr" => $item["MiniAPR"],
                    "expect_apr" => $item["ExpectAPR"],
                    "paydate" => $item["NextPaydate"],
                    "available_amount" => $item["AvailableAmount"],
                    "total_amount" => $item["TotalAmount"],
                    "allocate_id" => $item["AllocateId"],
                    "reserve_percent" => $item["ReservePercent"],
                    "reconciliation_date" => $item["ReconciliationDate"]
                );
            }

            $this->cache->memcached->save( 'markets-close', $result, 360);

        }
        return $result;
    }

    //查询某市场
    public function get_market_close_by_code($cashpoolCode){

        $result = $this->cache->memcached->get('markets-close');

        if( $result == null || empty($result)) {

            $this->get_markets();

            $result = $this->cache->memcached->get('markets-close');
        }

        return $result[$cashpoolCode];
    }



    public function get_service_time(){

        $result = $this->cache->memcached->get('service-time');

        if( $result == null || empty($result)) {

            $sql = "select StartTime as starttime, EndTime as endtime  
              from Customer_Cashpool_Service where `ServiceStatus` = 1;";

            $query = $this->db->query($sql);

            $result = $query->row_array();

            $this->cache->memcached->save( 'service-time', $result, 3600);
        }

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
                
                if( strlen($sql) > 5) {

                    $sql = "UPDATE `Customer_Cashpool` \n
                      SET {$sql}                      
                      WHERE Id = '{$market["Id"]}' ; ";

                    $this->db->query($sql);

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
