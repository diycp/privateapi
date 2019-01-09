<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class CustomerCashpool extends CI_Model {
    public function __construct() {
        parent::__construct();
        $this->db = $this->load->database('default', true);
        //$this->load->driver('cache');
    }

    public function getBooks($books) {
        $where = implode("','", $books);
        $sql = "SELECT Id,CashpoolCode,pool_code FROM Customer_Cashpool WHERE pool_code in ('{$where}')";
        $query = $this->db->query($sql);
        $arr = array();
        foreach($query->result_array() as $row) {
            $arr[$row['pool_code']] = array(
                                      'id'=>$row['Id'],
                                      'CashpoolCode'=>$row['CashpoolCode']);
        }
        return $arr;
    }

}
