<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/BaseController.php';

class Sync extends BaseController {

    public function index() {
        return;
    }

    public function suppliersids() {
        $this->init();//初始化参数
        if ($this->_check($this->get)) {//验签
            $sql_arr = array();
            foreach($this->post as $post) {
                if (! array_key_exists(trim($post['book_code']),$this->book)) {//参数不存在就丢弃
                    echo json_encode(array('code'=>-1,'msg'=>'pool_code:' + $post['book_code'] + ' not exist!'));
                    return;
                }
                $cash_pool_code = $this->book[trim($post['book_code'])]['CashpoolCode'];//转换为内部编码
                $supplier       = $post['supplier'];//供应商
                $vendorcode     = $post['vendorcode'];//供应商编码
                $sql_arr[] = "(SELECT Id,CashpoolCode,Vendorcode,Supplier FROM Customer_Suppliers WHERE CashpoolCode='{$cash_pool_code}' AND Vendorcode='{$vendorcode}')";
            }
                if (count($sql_arr) > 0) {
                    $this->load->model('Until');
                    $result = $this->Until->select($sql_arr);
                    $arr    = array();
                    foreach($result as $r) {
                        $tmp               = array();
                        $tmp['id']         = $r['Id'];
                        $tmp['book_code']  = $r['CashpoolCode'];
                        $tmp['vendorcode'] = $r['Vendorcode'];
                        $tmp['supplier']   = $r['Supplier'];
                        $arr[]             = $tmp;
                    }
                    if (count($arr) == 0) {
                        echo json_encode(array('code'=>1,'data'=> []));
                        return;
                    }
                    echo json_encode(array('code'=>1,'data'=>$arr));
                } else {
                    echo json_encode(array('code'=>-1, 'msg'=>'no sql'));
                }
        } else {
            echo json_encode(array('code'=>-1, 'msg'=> 'check is fails'));
        }                
    }

    public function suppliers() {
        $this->init();//初始化参数
        if ($this->_check($this->get)) {//验签
            if (is_array($this->post) && count($this->post) > 0) {
                //$data        = $this->getData();
                $sql_arr     = array();
                $create_user = 'API';
                foreach($this->post as $post) {
                    if (! array_key_exists(trim($post['book_code']),$this->book)) {//参数不存在就丢弃
                        echo json_encode(array('code'=>-1,'msg'=>'book_code:' + $post['book_code'] + ' not exist!'));
                        return;
                    }
                    $cash_pool_code  = $this->book[trim($post['book_code'])]['CashpoolCode'];
                    $supplier        = $post['supplier'];
                    $vendorcode      = $post['vendorcode'];
                    $payment_days    = $post['payment_days'];
                    $vendorstatus    = 0;
                    $relevancy_email = $post['relevancy_email'];
                    if (isset($post['id'])) {//判断操作为修改 或者 新增
                        $id = $post['id'];
                        $sql_arr[] = "UPDATE Customer_Suppliers SET CreateUser='{$create_user}',CashpoolCode='{$cash_pool_code}',Supplier='{$supplier}',Vendorcode='{$vendorcode}',VendorStatus={$vendorstatus},RelevancyEmail='{$relevancy_email}',payment_days={$payment_days} WHERE Id={$id};"; 
                    } else {
                        $sql_arr[] = "INSERT INTO Customer_Suppliers(Id,CreateUser,CashpoolCode,Supplier,Vendorcode,VendorStatus,RelevancyEmail,payment_days)VALUES(uuid_short(),'{$create_user}', '{$cash_pool_code}', '{$supplier}', '{$vendorcode}', $vendorstatus, '{$relevancy_email}', {$payment_days});";
                    }
                }
                if (count($sql_arr) > 0) {
                    $this->load->model('Until');
                    if ($this->Until->exeSql($sql_arr)) {
                        echo json_encode(array('code'=>1,'msg'=>'success'));
                    } else {
                        echo json_encode(array('code'=>-1,'msg'=>'fails'));
                    }
                } else {
                    echo json_encode(array('code'=>-1,'msg'=>'no sql'));
                }
            }
        } else {
            echo json_encode(array('code'=>-1, 'msg'=>'check is fails'));
        }
    }

    public function payments() {
        $this->init();
        if ($this->_check($this->get)) {
            $sql_arr = array();
            foreach($this->post as $post) {
                if (! array_key_exists(trim($post['book_code']),$this->book)) {//参数不存在就丢弃
                    echo json_encode(array('code'=>-1,'msg'=>'pool_code:' + $post['book_code'] + ' not exist!'));
                    return;
                }
                $createUser     = 'API';
                $cash_pool_code = $this->book[trim($post['book_code'])]['CashpoolCode'];//转换为内部编码
                $vendorcode     = $post['vendorcode'];//参数解释见文档
                $invoice_no     = $post['invoice_no'];
                $invoice_amount = $post['invoice_amount'];
                $invoice_date   = $post['invoice_date']; 
                $estPay_date    = $post['estPay_date'];
                //$invoce_status  = 1;//default 1
                $client_status  = $post['status'];
                //$isincluded     = 1;//default 1
                if (isset($post['id'])) {//判断 修改还是新增
                    $id = $post['id'];
                    $sql_arr[] = "UPDATE Customer_Payments SET CashpoolCode='{$cash_pool_code}',Vendorcode='{$vendorcode}',InvoiceNo='{$invoice_no}',InvoiceAmount={$invoice_amount},InvoiceDate='{$invoice_date}',EstPaydate='{$estPay_date}',client_status={$client_status} WHERE Id={$id}";
                } else {
                    $sql_arr[] = "INSERT INTO Customer_Payments(Id,CreateUser,CashpoolCode,Vendorcode,InvoiceNo,InvoiceAmount,InvoiceDate,EstPaydate,client_status)VALUES(uuid_short(),'{$createUser}','{$cash_pool_code}','{$vendorcode}','{$invoice_no}',$invoice_amount,'{$invoice_date}','{$estPay_date}',{$client_status});";    
                }
            } 
            if (count($sql_arr) > 0) {
                $this->load->model('Until');
                if ($this->Until->exeSql($sql_arr)) {
                    echo json_encode(array('code'=>1,'mgs'=>'success'));
                } else {
                    echo json_encode(array('code'=>-1,'msg'=>'fails'));
                }
            } else {
                echo json_encode(array('code'=>-1,'msg'=>'no sql'));
            }  
        } else {
            echo json_encode(array('code'=>-1, 'msg'=>'check is fails'));
        }
    }

    public function paymentids() {
        $this->init();//初始化参数
        if ($this->_check($this->get)) {//验签
            $sql_arr  = array();
            foreach($this->post as $post) {
                if (! array_key_exists(trim($post['book_code']),$this->book)) {//参数不存在就丢弃
                    echo json_encode(array('code'=>-1,'msg'=>'book_code:' + $post['book_code'] + ' not exist!'));
                    return;
                }
                $cash_pool_code = $this->book[trim($post['book_code'])]['CashpoolCode'];//转换为内部编码
                $invoice_no     = $post['invoice_no'];
                $sql_arr[]      = "(SELECT Id,CashpoolCode,InvoiceNo FROM Customer_Payments WHERE CashpoolCode='{$cash_pool_code}' AND InvoiceNo='{$invoice_no}')"; 
            }
            if(count($sql_arr) > 0) {
                $this->load->model('Until');
                $result = $this->Until->select($sql_arr);
                $arr    = array();
                foreach($result as $r) {
                    $tmp               = array();
                    $tmp['id']         = $r['Id'];
                    $tmp['book_code']  = $r['CashpoolCode'];
                    $tmp['invoice_no'] = $r['InvoiceNo'];
                    $arr[]             = $tmp;
                }
                if (count($arr) == 0) {
                    echo json_encode(array('code'=>1,'data'=> []));
                    return;
                }
                echo json_encode(array('code'=>1, 'data'=>$arr));
            } else {
                    echo json_encode(array('code'=>-1, 'msg'=> 'no sql'));
            }
        } else {
            echo json_encode(array('code'=>-1, 'mgs'=> 'check is fails'));
        }
    }
}
