<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class UserActiveLog extends CI_Model {
    public function __construct()    {
        parent::__construct();
        $this->db = $this->load->database('activity', true);
        global $data;
        $data["namespace"] = 'supplier';
    }
    public  function beginLog($ci)
    {
        global $data;
        //$gValue = var_export($_GET,true);
        //$pValue = var_export($_POST,true);
        $data["begin_time"] = date('Y-m-d H:i:s', time());
        /*file_put_contents("logtest1.txt","------------------------------------------------------------\r\n"
            ."开始:".date('Y-m-d H:i:s', time())."\r\n"
            .";控制器名称:".$ci->router->fetch_class()
            .";执行动作:".$ci->router->fetch_method()
            .";路径:".$ci->uri->segment(1)."\r\n"
            .';GET:'.$gValue."\r\n"
            .';POST:'.$pValue
            ."\r\n",FILE_APPEND);*/
    }
    public  function endLog($ci)
    {
        global $data;
        $request_data = $this->getLogJsonStr($_GET);
        $execute_data = $this->getLogJsonStr($_POST);
        $data["request_data"] = $request_data;
        $data["execute_data"] = $execute_data;
        $data["request_method"] = $_SERVER['REQUEST_METHOD'];
        $data["apply_api"] = $ci->uri->uri_string();
        $data["market_id"] = $ci->input->get('market_id')?$ci->input->get('market_id'):
            ($ci->get('buyer_id')?$ci->get('buyer_id'):"");
        $data["user_ip"] = $ci->input->ip_address()?$ci->input->ip_address():getIp();
        /*file_put_contents("logtest1.txt","------------------------------------------------------------\r\n"."结束:"
            .""
            ."\r\n"
            .date('Y-m-d H:i:s', time())."\r\n",FILE_APPEND);*/
    }
    public  function outLog($ci,$responsedata=null)
    {
        global $data;
        $data["end_time"] = date('Y-m-d H:i:s', time());
        if(!is_null($responsedata))
        {
            //$receiving_data = json_encode($responsedata);
            $receiving_data = $this->getLogJsonStr($responsedata);
        }
        else
        {
            $receiving_data = $ci->output->final_output;
        }
        $data["receiving_data"] = $receiving_data;
        $access_token = $ci->input->get('access_token');
        if(!empty($access_token))
        {
            $db1 = $this->load->database('oauth2', true);
            $query = $db1->query("select * from oauth2_tokens where oauth_token='$access_token'")->row_array();
            $data["user_id"] = $query["user_id"];
            $data["app_key"] = $query["app_key"];
            if(intval($data["user_id"])>0 && !empty($data["app_key"])) {
                $query = $db1->query("select * from oauth2_users where user_id=".$data["user_id"].
                    "  and app_key='".$data["app_key"]."'")->row_array();
                $data["user_email"] = $query["email"];
            }
        }
        $re = $this->db->insert('User_Active_Log', $data);
        //file_put_contents("logtest1.txt","输出:".$ci->output->final_output."\r\n".date('Y-m-d H:i:s', time())."\r\n------------------------------------------------------------\r\n",FILE_APPEND);
    }
    private function getLogJsonStr($dataObj)
    {
        $resultStr = count($dataObj)>0?json_encode($dataObj):"";
        /*$resultStr = (is_null($resultStr) || empty($resultStr))
            ?($resultStr==""?"":var_export($resultStr,true)) :$resultStr;*/
        return $resultStr;
    }
}