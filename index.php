<?php

error_reporting(E_ALL && E_NOTICE);
ini_set('display_errors', 1);

/**
 * Gate way.
 * Routes a request to the expected route based on available 
 * parameters that matches routes existing on this system.
 * 
 * PHP Version: 8.1.3
 * 
 * @category Web_Application
 * @package  Gokolect_API_Service
 * @author   Tamunobarasinipiri Samuel Joseph <joseph.samuel@cinfores.com>
 * @license  MIT License
 * @link     https://gokolect.test
 */
 // Allow from any origin
// if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    // header('Access-Control-Allow-Credentials: true');
    // header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
    // header("Access-Control-Allow-Headers:Origin, Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, accept, X-Auth-Token");
    // header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    header("Access-Control-Allow-Headers: *");
// }


if (isset($_POST['action']) || isset($_GET['action'])) {        
    include_once __DIR__ ."/web/app/Route/route_index.php";    
} else {
    exit(header('Location: apidoc.html'));
}