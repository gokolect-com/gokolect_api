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

use Gokolect\Api\AuthBll;

error_reporting(E_ALL);

// require_once __DIR__."/../api/auth.bll.php";


/**
 * Consultant function allows requests that are for this route only
 * 
 * @param object $data input data object
 * 
 * @return json
 */
function authRouter($data)
{ 
    $auth_object = new AuthBll($data);

    switch($data['action']) {
        
    case "sign_up_auth": 
        $response = $auth_object->signUpUsers();
        break;

    case "sign_in_auth":
        $response = $auth_object->signInUsers();
        break;

    case "sign_out_auth":
        $response = $auth_object->signOutUser();
        break;

    case "verify_session_auth":
        $response = $auth_object->verifySession();
        break;

    case "change_password_auth":
        $response = $auth_object->changePassword();
        break;

    case "reset_password_auth":
        $response = $auth_object->resetUserPassword();
        break;

    case "delete_account_auth":
        $response = $auth_object->deleteAccount();
        break;

    case "lock_account_auth":
        $response = $auth_object->lockUser();
        break;

    case "verify_signup_auth": 
        $response = $auth_object->verifyEmail();
        break;

    case "confirm_otp_auth":
        $response = $auth_object->confirmOtpCode();
        break;
        
    default:
        $response = ['statuscode'=>0, "status"=>"Invalid request action"];
        break;
    }
    return $response;
} 
