<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class HistoryModel extends CI_Model {

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

    }


    public function getAwardInvoiceList($cashpoolCode, $begindate = null , $enddate = null)
    {
        $sql = "
            select  `Vendorcode` ,`InvoiceNo`,
              `AwardDate` ,
              `PayDate` ,
              `PayAmount` ,
              `PayDpe` ,
              `PayDiscount`,
              `IsManual`
              from Customer_OptimalAwards
              where CashpoolCode = '{$cashpoolCode}'
        ";

        if( $begindate != null )
        {

            $sql .= " and AwardDate >= '{$begindate}' ";

            if( $enddate != null && $enddate > $begindate){

                $sql .= " and AwardDate >= '{$begindate}' and AwardDate < '{$enddate}' ;";

            } else{
                $sql .= " and AwarDate = '{$begindate}' ;";
            }
        }

        $query = $this->db->query($sql);

        return $query->result_array();
    }

     // Get category
	public function getDailyAwardList( $cashpoolCode, $begindate = null , $enddate = null)
    {

        $sql = "select Id,
              `AwardDate`, 
              `PayDate`,
              `PayAmount` ,
              `PayDiscount` ,
              `InvoiceCount`,
              `AvgDpe` ,
              `AvgDiscount` ,
              `AvgAPR`
              from `Customer_DailyAwards` where CashpoolCode = '{$cashpoolCode}'"; 
			  
			  
			  if( $begindate != null )
			  {

					$sql .= " and AwardDate >= '{$begindate}' ";

					if( $enddate != null && $enddate > $begindate){

						$sql .= " and AwardDate >= '{$begindate}' and AwardDate < '{$enddate}' ;";

					} else{
						$sql .= " and AwarDate = '{$begindate}' ;";
					}
			  }

        $query = $this->db->query($sql);

        return $query->result_array();
    }


}
