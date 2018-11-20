<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( !function_exists('error_output') ) {
    // 接口输出
	function error_output($error_no, $error_des) {
		$output = array(
			'code' => $error_no,
			'msg' => isset($error_des[$error_no]) ? $error_des[$error_no] : 'invalid request',
		);
		echo json_encode($output);
		exit;
	}
}

if ( !function_exists('output') ) {
    // 接口输出
	function output($data) {
		$output = json_encode($data);
		echo $output;
		exit;
	}
}

/* Location: ./system/helpers/file_helper.php */
