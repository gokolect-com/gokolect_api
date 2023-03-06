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

// header("Access-Control-Allow-Headers: Authorization, Content-Type");
// header("Access-Control-Allow-Origin: *");

header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");

header('content-type: application/json; charset=utf-8');

die(var_dump($_POST));
if (isset($_POST['action']) || isset($_GET['action'])) {        
    include_once __DIR__ ."/web/app/Route/route_index.php";    
} else {
    exit(header('Location: apidoc.html'));
}