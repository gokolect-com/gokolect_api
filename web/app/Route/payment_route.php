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
    $payment_object = new PaymentBll($data);

    switch($data['action']) {
        
    case "process_payment":
        $response = $payment_object->processPayment();
        break;    
        
    case "generate_ref_payment":
        $response = $payment_object->generatePayRef();
        return $response;
        break;
        
    case "verify_payment":
         $response = $payment_object->verifyPayment();        
        break;
        
    default:
        $response = ['statuscode'=>0, "status"=>"Invalid request action payment"];
        break;
    }
    
    return $response;
} 
