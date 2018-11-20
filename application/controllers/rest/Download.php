<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Download extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */


	public function __construct()
    {
        #$this->load->library('session');
        $this->load->driver('cache');
        //无论什么时候都从URL中取 Access_Token 的值
        $access_token = isset($_GET['access_token']) ? $_GET['access_token'] : '';

        if( isset($access_token) && !empty($access_token))
            $token = $this->cache->memcached->get($access_token);
        #$expire_time = $this->session->userdata($access_token);

        // If false, then the user isn't logged in
        if ( !isset($access_token) || empty($access_token) || !isset( $token) || empty($token))
        {
            // Display an error response
            $this->response([
                'code' => 401,
                'msg' => 'No valid access_token  '.$access_token
            ], self::HTTP_UNAUTHORIZED);


        }
    }

    public function index()
	{
		$_entity = $this->get("entity") ;
        $_type = $this->get("type");
        $_keyid = $this->get("key_id");


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


        $data = [

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

        ];

        switch (strtolower($_type)){
            case "excel" :
                export_xls($data, $header);
                break;
            default:
                break;
        }


	}

	private  function export_xls($data,$columns = array())
    {
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();

        $count = count(data);

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
