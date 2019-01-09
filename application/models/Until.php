<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Until extends CI_Model {
	public function __construct()    {
        parent::__construct();
        $this->db = $this->load->database('default', true);
    }

    public function select($sql_arr) {
        if (is_array($sql_arr) && count($sql_arr) > 0) {
            $arr   = array();
            $sql   = implode(" UNION ALL ", $sql_arr);
            $query = $this->db->query($sql);
            $arr   = $query->result_array();
            return $arr;
        }
    }

    public function exeSql($sql_arr) {
        if (is_array($sql_arr) && count($sql_arr) > 0) {
            $this->db->trans_begin();
            foreach($sql_arr as $sql) {
                $this->db->query($sql);
            }
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return FALSE;
            } else {
                $this->db->trans_commit();
                return TRUE;
            }
        } 
        return FALSE;
    }
}
