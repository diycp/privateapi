<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class SupplierModel extends CI_Model {

    protected $profile ;
    protected $marketId;
    protected $vendors ;

    protected function base64url_encode($data) {

        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected function base64url_decode($data) {

        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

	public function __construct()    {
        parent::__construct();

        $this->db = $this->load->database('default', true);

        $this->load->driver('cache');

        $this->profile = $this->cache->memcached->get('profile');

    }

    // Get category
    public function getSuppliers($marketId){

        return $this->_getVendors($marketId);

    }

    public function getSuppliersUsers($cashpoolCode, $vendorId){

        $cashKey = $cashpoolCode."-".$vendorId;

        $users = $this->cache->memcached->get($cashKey);

        if( $users ==  null){

            $users = array();

            $sql = 	"select * from `Customer_Suppliers_Users` u  where SupplierId = '{$vendorId}' ORDER BY `CreateTime` DESC; ";

            $query = $this->db->query($sql);

            if($query->num_rows() >0 )
            {
                $result = $query->result_array();

                foreach($result as $row){
                    $user = array();

                    $user["user_id"] = $row['Id'] ;
                    $user["user_email"] = $row['UserEmail'];
                    $user["contact_name"] = $row['UserContact'];
                    $user["position"] = $row['UserPosition'];
                    $user["contact_phone"] = $row['UserPhone'];

                    if($row['UserStatus'] == -1){
                        $user["registered_status"] = "Closed";
                        $user["registered_time"] = "-";
                        $user["registered_event"] = null;
                    }else if($row['UserStatus'] == 0){
                        $user["registered_status"] = "Pending Register";
                        $user["registered_time"] = "-";
                        $user["registered_event"] = array(
                            "label" => "Invite",
                            "action_type" => "invite",
                            "action_id" => $this->base64url_encode($cashpoolCode).'-'.$this->base64url_encode($row['SupplierId']).'-'.$this->base64url_encode($row['UserEmail'])
                        );
                    }else {
                        $user["registered_status"] = "Registered";
                        $user["registered_time"] = date('Y-m-d H:i:s', strtotime($row['CreateTime']));
                        $user["registered_event"] = null;
                    }

                    $users[] = $user;
                }
            }

            $this->cache->memcached->save($cashKey, $users);
        }

        return $users;

    }



    //
    private function _getVendors($marketId){

       $vendors = $this->cache->memcached->get($marketId);

        if( $vendors ==  null){

            $vendors = array();

            $sql = 	"select v.Id, v.Supplier, v.Vendorcode
                    ,CASE WHEN b.Id IS NULL THEN 0 ELSE 1 END as VendorStatus
                    , SUM(CASE WHEN u.Id IS NOT NULL THEN 1 ELSE 0 END) as `VendorUsers`
                    from  `Customer_Suppliers` v 
                    left join `Base_Vendors_Items` b ON b.SupplierId = v.Id
                    left join `Base_Vendors_Users` u ON b.VendorId = u.VendorId
                    where v.CashpoolCode = '{$marketId}'
                    GROUP BY v.Id, v.Supplier, v.Vendorcode, b.Id
                    ORDER BY v.`Id` DESC;
                  ";

            $query = $this->db->query($sql);

            //print_r($this->db->last_query()); die;

            if($query->num_rows() >0 )
            {


                $result = $query->result_array();

                foreach($result as $row){

                    if ( $row['Id'] == null || empty($row['Id']))
                        continue;

                    $vendor = array();

                    $vendor["supplier_id"] = $row['Id'] ;
                    $vendor["supplier_name"] = $row['Supplier'];
                    $vendor["supplier_status"] = $row['VendorStatus'];
                    $vendor["vendor_code"] = $row['Vendorcode'];
                    $vendor["supplier_users"] = $row['VendorUsers'];

                    $vendors[] = $vendor;
                }
            }

            $this->cache->memcached->save($marketId, $vendors);
        }

        return $vendors;
    }



    public function getSupplierStat($cashpoolCode){

        $market = array();

        //所有供应商
        $sql = "SELECT                 
					COUNT(s.Id) as TotalVendor,														 
					SUM( CASE WHEN ap.Vendorcode IS NOT NULL THEN 1 ELSE 0 END) as AP_TotalVendor			 
					FROM `Customer_Suppliers` s   
                    LEFT JOIN ( 
                       SELECT DISTINCT Vendorcode  FROM  `Customer_Payments`  WHERE CashpoolCode = '{$cashpoolCode}'
                        ) ap ON s.Vendorcode = ap.Vendorcode
                    WHERE s.CashpoolCode = '{$cashpoolCode}';";

        $query = $this->db->query($sql);
        $result = $query->row_array();

        $market["total_count"] = isset($result["TotalVendor"]) ? $result["TotalVendor"] : 0 ;
        $market["total_ap_count"] = isset($result["AP_TotalVendor"]) ? $result["AP_TotalVendor"] : 0 ;

        //注册供应商
        $sql = "SELECT                 
					COUNT(s.Id) as TotalVendor,														 
					SUM( CASE WHEN ap.Vendorcode IS NOT NULL THEN 1 ELSE 0 END) as AP_TotalVendor			 
					FROM `Customer_Suppliers` s   
					INNER JOIN `Base_Vendors_Items` i ON i.SupplierId = s.Id
                    LEFT JOIN ( 
                       SELECT DISTINCT Vendorcode  FROM  `Customer_Payments`  WHERE CashpoolCode = '{$cashpoolCode}'
                        ) ap ON s.Vendorcode = ap.Vendorcode
                    WHERE s.CashpoolCode = '{$cashpoolCode}';";


        $query = $this->db->query($sql);
        $result = $query->row_array();

        $market["registered_count"] = isset($result["TotalVendor"]) ? $result["TotalVendor"] : 0 ;
        $market["registered_ap_count"] = isset($result["AP_TotalVendor"]) ? $result["AP_TotalVendor"] : 0 ;

        //参与竞价供应商
        $sql = "SELECT                 
					COUNT(s.Id) as TotalVendor,														 
					SUM( CASE WHEN ap.Vendorcode IS NOT NULL THEN 1 ELSE 0 END) as AP_TotalVendor			 
					FROM `Customer_Suppliers` s   
					INNER JOIN (
                      SELECT DISTINCT b.Vendorcode 
                      FROM `Supplier_Bids` b
                      INNER JOIN `Customer_Cashpool` p ON p.Id = b.CashpoolId and p.CashpoolCode = '{$cashpoolCode}'                                         
					) q ON q.Vendorcode = s.Vendorcode
                    LEFT JOIN ( 
                       SELECT DISTINCT Vendorcode  FROM  `Customer_Payments`  WHERE CashpoolCode = '{$cashpoolCode}'
                        ) ap ON s.Vendorcode = ap.Vendorcode
                    WHERE s.CashpoolCode = '{$cashpoolCode}';";

        $query = $this->db->query($sql);
        $result = $query->row_array();

        $market["participated_count"] = isset($result["TotalVendor"]) ? $result["TotalVendor"] : 0 ;
        $market["participated_ap_count"] = isset($result["AP_TotalVendor"]) ? $result["AP_TotalVendor"] : 0 ;

        return $market;

    }

    public function reloadVendors($marketId){
        $this->cache->memcached->save($marketId, null);
    }

    public function reloadVendorUsers($vendorId){
        $this->cache->memcached->save($vendorId, null);
    }


		
}
