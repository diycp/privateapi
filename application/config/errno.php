<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

define('ERROR_INVALID_APPKEY', "10001");
define('ERROR_INVALID_CODE', "10002");
define('ERROR_INVALID_ACCESS_TOKEN', "10003");
define('ERROR_INVALID_OPENID', "10004");
define('ERROR_INVALID_SCOPE', "10005");
define('ERROR_INVALID_ACCOUNT', "10006");
define('ERROR_INVALID_WC_OPENID', "10007");

$config['errno'] = array(
	'10001' => 'invalid appkey',
	'10002' => 'invalid code',
	'10003' => 'invalid access token',
	'10004' => 'invalid openid',
	'10005' => 'invalid scope',
    '10006' => 'invalid username or password',
    '10007' => 'invalid wechat openid',
);
