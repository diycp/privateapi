<?php

defined('BASEPATH') OR exit('No direct script access allowed');



/**
 * Keys Controller
 * This is a basic Key Management REST controller to make and delete keys
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Auth {

    protected $methods = [
            'index_put' => ['level' => 10, 'limit' => 10],
            'index_delete' => ['level' => 10],
            'level_post' => ['level' => 10],
            'regenerate_post' => ['level' => 10],
        ];

    
    public static function login($username,$password){
        
        return md5('admin_bak' . ':' . "REST API" . ":1234");
        
    } 

}
