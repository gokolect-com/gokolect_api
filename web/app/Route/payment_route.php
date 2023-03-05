<?php

/** 
 * Authentication route
 * PHP Version 8.1.3
 * 
 * @category MicroService_API_Application
 * @package  Gokolect_API_Service
 * @author   Tamunobarasinipiri Samuel Joseph <joseph.samuel@cinfores.com>
 * @license  MIT License
 * @link     https://gokolect.test
 */

use Gokolect\Api\PaymentBll;

error_reporting(E_ALL);

// require_once __DIR__."/../api/auth.bll.php";

/**
 * Consultant function allows requests that are for this route only
 * 
 * @param array $data input data object
 * 
 * @return json
 */
function paymentRouter(array $data = null)
{
    $user_object = new PaymentBll($data);

    switch($data['action']) {
        
    case "make_payment":
         
        break;    
        
    case "generate_ref_payment":
        $response = $user_object->generatePayRef();
        return $response;
        break;
        
    case "process_payment":
         include_once __DIR__.DIRECTORY_SEPARATOR."../payment/processPayment.php";
        
        break;
        
    default:
        return ['statuscode'=>0, "status"=>"Invalid request action payment"];
        return $response;
        break;
    }
} 
