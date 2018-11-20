<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . 'libraries/REST_Controller.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class History extends REST_Controller {

    protected $history = array();
    protected $cashpoolCode = null;

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $marketid = $this->get('market_id');

        if( !isset($marketid) ||  $marketid  == null )
        {
                $this->set_response([
                    'code' => -1,
                    'msg' => 'Please make sure the parameter market_id is valid. '
                ], REST_Controller::HTTP_BAD_REQUEST);
                exit;
        }
        $this->load->model('Historymodel');
        $this->cashpoolCode = $marketid;

    }

    public function get_market_stat_get()
    {

        $this->load->model('Marketmodel');
        $market = $this->Marketmodel->getMarketStatusByCode( $this->cashpoolCode);

        $amount = 0 ;
        $discount = 0;
        $avg_discount = 0.00 ;

        $begindate = $this->get('startdate');
        $enddate = $this->get('enddate');

        $history = $this->Historymodel->getAwardInvoiceList( $this->cashpoolCode, $begindate, $enddate) ;

        foreach($history as $val){
            $amount += $val['PayAmount'];
            $discount += $val['PayDiscount'];
        }


        foreach ( $history as $val){
            $avg_discount += round( $val["PayDiscount"]/ $amount , 4);
        }

        $result = array(
            'currency' => $market["CurrencyName"],
            'currency_sign' => $market["CurrencySign"],
            'awarded_amount' => $amount,
            'discount_amount' => $discount,
            'average_discount' => $avg_discount *100
        );

        $this->response([
            'code' => 1,
            'data' => $result
        ], REST_Controller::HTTP_OK);

    }

    public function get_market_graph_get()
    {
        $result = array();

        foreach($this->history as $val){
            $result[] = array(
                'date' => date('M.d',strtotime($val['award_date'])),
                'awarded_amount' => $val['invoice_amount'],
                'awarded_discount' => $val['discount_amount']
            );
        }

        $this->response([
            'code' => 1,
            'data' => $result
        ], REST_Controller::HTTP_OK);
    }

    public function get_awarded_list_get()
    {
        $begindate = $this->get('startdate');
        $enddate = $this->get('enddate');

        $result = $this->Historymodel->getDailyAwardList($this->cashpoolCode, $begindate, $enddate);
        $history = array();

        foreach( $result as $row){
            $history[] = array(
                'award_id'=> $row["Id"],
                'award_date'=> $row["AwardDate"],
                'paid_amount'=> $row["PayAmount"] - $row["PayDiscount"] ,
                'discount_amount'=> $row["PayDiscount"],
                'average_discount'=> $row["AvgDiscount"],
                'average_apr'=> $row["AvgAPR"],
                'average_dpe'=> $row["AvgDpe"],
                'invoice_count'=> $row["InvoiceCount"],
                'invoice_amount'=> $row["PayAmount"] ,
            );
        }

        $this->response([
            'code' => 1,
            'data' => $history
        ], REST_Controller::HTTP_OK);

    }

    public function download_awarded_detail_get(){
        $awardid = $this->get('award_id');
        $filetype = $this->get("type");

        if( $awardid != null && $filetype != null){

            $header = array(

                array(
                    'datakey' => 'supplier','colheader' => 'supplier Name','colwidth' => '20'
                ),
                array(
                    'datakey' => 'Vendorcode','colheader' => 'Vendor Code','colwidth' => '20'
                ),
                array(
                    'datakey' => 'InvoiceNo','colheader' => 'Invoice No','colwidth' => '16'
                ),
                array(
                    'datakey' => 'EstPaydate','colheader' => 'Original Paydate','colwidth' => '14'
                ),
                array(
                    'datakey' => 'InvoiceAmount','colheader' => 'Invoice Amount'
                )
            );

            $data = array(

                array(
                    'Vendorcode' => 'V0000005',
                    'supplier' => 'L5',
                    'InvoiceNo' => '098763467',
                    'EstPaydate' => '2018-06-04',
                    'InvoiceAmount' => 106671.00
                ),
                array(

                    'Vendorcode' => 'V0000005',
                    'supplier' => 'L5',
                    'InvoiceNo' => '098763468',
                    'EstPaydate' => '2018-05-14',
                    'InvoiceAmount' => 266071.00
                ),
                array(

                    'Vendorcode' => 'V0000006',
                    'supplier' => 'L6',
                    'InvoiceNo' => '098763469',
                    'EstPaydate' => '2018-05-24',
                    'InvoiceAmount' => 271621.00
                ),
                array(

                    'Vendorcode' => 'V0000005',
                    'supplier' => 'L5',
                    'InvoiceNo' => '098763470',
                    'EstPaydate' => '2018-06-23',
                    'InvoiceAmount' => 166710.00
                )

            );

            switch (strtolower($filetype)){
                case "excel" :
                    $this->load->library('PHPExcel');
                    $this->export_xls($data, $header);
                    break;
                case "csv" :
                    $this->export_csv($data, $header);
                    break;
                default:
                    break;
            }


        }else{
            $this->response([
                'code' => 0,
                'msg' => "No 'award_id' or 'type' parameter "
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    private function export_csv($data,$columns = array()){
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=".date("YmdHis",time()).".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');

        $row = "";
        if(is_array($columns ) && count($columns) > 0 ){

            foreach($columns as $col){
                $row .= $col['colheader'].",";
            }

            echo (substr($row, 0, strlen($row)-1)  . "\n");
        }

        foreach($data  as $item){
            echo (implode(",", array_values($item)) . "\n");
        }

    }

    private  function export_xls($data,$columns = array())
    {
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();

        $count = count($data);

        $col = array_keys($data[0]);

        if(!is_array($columns) || count($columns) <=0)
        {
            $columns = array();

            foreach($col as $value)
            {
                $columns[] = array(
                    'datakey' => $value,
                    'colheader' => $value,
                );
            }

        }

        $xlsCol = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
        $this->debuglog($columns);
        foreach($columns as $key=>$c)
        {

            $objPHPExcel->getActiveSheet()->SetCellValue($xlsCol[$key].'1', $c['colheader']);
        }

        $raw=1;
        foreach($data as $i){
            $raw++;

            foreach($columns as $key=>$c)
            {
                $objPHPExcel->getActiveSheet()->SetCellValue($xlsCol[$key].$raw, $i[$c['datakey']]);

                /*
                if(isset($c['coltype']))
                {
                    $objPHPExcel->getActiveSheet()->getStyle($xlsCol[$key].$raw)->getNumberFormat()->setFormatCode($c['coltype']);

                    //getActiveSheet()->setCellValueExplicit( $xlsCol[$key].$raw,$i[$c['datakey']],$c['coltype']);
                }
                */
            }

        }

        //设置样式

        foreach($columns as $key=>$c)
        {

            $fill = $xlsCol[$key].'2:'.$xlsCol[$key].$raw ;


            if(isset($c['colwidth']))
                $objPHPExcel->getActiveSheet()->getColumnDimension($xlsCol[$key])->setWidth($c['colwidth']);
            else
                $objPHPExcel->getActiveSheet()->getColumnDimension($xlsCol[$key])->setAutoSize(true);


            if(isset($c['colcolor']))
                $objPHPExcel->getActiveSheet()->getStyle($fill)->getFont()->getColor()->setARGB($c['colcolor']);

        }


        //选择所有数据
        $fill = $xlsCol[0].'1:'.$xlsCol[count($columns) - 1 ].$raw ;

        //设置居中
        $objPHPExcel->getActiveSheet()->getStyle($fill)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        //所有垂直居中
        $objPHPExcel->getActiveSheet()->getStyle($fill)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);



        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.'payblelist-'.date('Y-m-d').'.xls"');
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');


        $objWriter->save('php://output');

        exit;

    }

}
