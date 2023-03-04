<?php

namespace Gokolect\Api;


/**
 * The connection class, this file is configured to connect to the database
 * 
 * PHP version 8.1.3
 * 
 * @category Micro_Service_Application
 * @package  Gokolect_API_Service
 * @author   Tamunobarasinipiri Joseph Samuel <josephsamuelw1@gmail.com>
 * @license  MIT license 
 * @link     https://gokolect.test
 */

// require_once "utility.php";

use Gokolect\Api\Dal\AuthDal;
// use Gokolect\GUMP;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__."/../../../vendor/wixel/gump/gump.class.php";
require_once __DIR__."/JWTConfig.php";

/**
 * Gokolect Authentication class holds all attributes of the authentication functionalities.
 * Gokolect Authentication class holds all attributes of the authentication functionalities from 
 * sign up to email verification and session management.
 * 
 * PHP Version 8.1.3
 * 
 * @category Micro_Service_Application
 * @package  Gokolect_API_Service
 * @author   Tamunobarasinipiri Joseph Samuel <josephsamuelw1@gmail.com>
 * @license  MIT license 
 * @link     https://gokolect.test
 */
class AuthBll
{
    private static $_input_data;
    /**
     * Class constructor
     * 
     * @param array $data An array of user input data
     * 
     * @return array
     */
    public function __construct($data = null)
    {        
        if (!is_null($data)) {
            unset($data['action']);
            self::$_input_data = $data;
        }                
    }

    /**
     * Sign up method 
     * Creates a user record on the db
     * 
     * @return array
     */
    public function signUpUsers()
    {
        $validated = $this->_validateInputData((array) self::$_input_data);
        if ($validated['error']) {
            $response = ['statuscode' => -1, 'status' => $validated['errormsg']];
        } else {
            $data = $validated['my_post'];
            $_auth = new AuthDal($data);
            $response = $_auth->signUp();
        }
        return $response;
    }
 
    /**
     * Verify user sign up email
     * 
     * @return bool
     */
    public function verifyEmail()
    {        
        $_auth = new AuthDal(self::$_input_data);
        return $_auth->verifyEmail();
    }

    /**
     * Sign In method
     * Signs a user in the system by confirming user's 
     * record based on given parameters.
     * 
     * @return array
     */
    public function signInUsers()
    {
        $validated = $this->_validateSignIn(self::$_input_data);
        if (!filter_var(self::$_input_data['email'], FILTER_VALIDATE_EMAIL)) {
            $response = ['statuscode' => -1, 'status' => 'Invalid email address'];

        } else if (!$validated['error']) {

            $data = $validated['mypost'];

            $_auth = new AuthDal($data);
            $response = $_auth->signIn();

        } else {
            $response = ["statuscode" => -1, "status" => $validated['errormsg']];
        }
        return $response;
    }

    /**
     * Sign out a session
     * Sign a user out from a session
     * 
     * @return boolean
     */
    public function signOutUser()
    {       
        $_auth = new AuthDal();
        return $_auth->signOut();
    }

    /**
     * Change password 
     * Change default or previous password
     * 
     * @return bool
     */
    public function changePassword()
    {
        if (!filter_var(self::$_input_data['email'], FILTER_VALIDATE_EMAIL)) {
            $response = ['statuscode'=>-1,'status'=>'Email is invalid'];
        } else {
            $_auth = new AuthDal(self::$_input_data);
            $response = $_auth->changePassword();
        }
        return $response;
    }

    /**
     * Confirm OTP confirmation code 
     * reset user password on request
     * 
     * @return array
     */
    public function confirmOtpCode()
    {
        $validated = $this->_validateCode(self::$_input_data);
        $data = $validated['mypost'];
        if ($validated['error']) {
            $response = ['statuscode' => -1, "status" => $validated['errormsg']];
        } else {
            $_auth = new AuthDal($data);
            $response = $_auth->confirmOtpCode();
        }        
        return $response;
    }

    /**
     * Reset password 
     * Reset password if password has been forgotten
     * 
     * @return bool
     */
    public function resetUserPassword()
    {               
        if (!filter_var(self::$_input_data['reset_email'], FILTER_VALIDATE_EMAIL)) {
            $response = ['statuscode'=>-1,'status'=>'Email is invalid'];
        } else {
            $_auth = new AuthDal(self::$_input_data);
            $response = $_auth->resetPassword();
        }
        return $response;
    }

    /**
     * Delete an account
     * Delete user account on request using the users's email address required
     * 
     * @return bool   
     */
    public function deleteAccount()
    {
        $_auth = new AuthDal(self::$_input_data);
        return $_auth->deleteAccount();
    }

    /**
     * Lock user account 
     * Locks a user account on request using user email 
     * 
     * @return array   
     */
    public function lockUser()
    {
        $_auth = new AuthDal(self::$_input_data);        
        return $_auth->lockUser();
    }

    /**
     * Validate input data method
     * Validates input data from user to create account
     * 
     * @param object $dataSet input data to validate
     * 
     * @return object
     */
    private function _validateInputData($dataSet)
    {
        $data = (array) $dataSet;   

        $validator = new \GUMP;
        $rules = null;   
        $mypost = $data;

        $mypost = $validator->sanitize($mypost);

        $filters = array(
            'first_name' => 'trim|sanitize_string',
            'last_name' => 'trim|sanitize_string',
            'email' => 'trim|sanitize_string',
            'mobile' => 'trim|sanitize_string',
            'password' => 'trim|sanitize_string',
            'confirm_password' => 'trim|sanitize_string',
        );
        $mypost = $validator->filter($mypost, $filters);
        
        $rules = array(
            'first_name' => 'required|min_len,3|max_len,200',
            'last_name' => 'required|min_len,3|max_len,200',
            'email' => 'required|min_len,10',
            'mobile' => 'required|min_len,8|max_len,20',
            'password' => 'required|min_len,6',
            'confirm_password' => 'required|min_len,6'
        );
               

        $validated = $validator->validate($mypost, $rules);
        if ($validated === true) {
            $return = array(
                'post' => $data,
                'error' => false,
                'errormsg' => "",
                'my_post' => $mypost
            );
        } else {
            $return = array(
                'post' => $data,
                'error' => true,
                'errormsg' => $validator->get_readable_errors(),
                'my_post' => $mypost
            );
        }
        return $return;
    }

    /**
     * Validate input data method
     * Validates input data from user to create account
     * 
     * @param object $dataSet input data to validate
     * 
     * @return object
     */
    private function _validateSignIn($dataSet)
    {
        $data = (array) $dataSet;

        $validator = new \GUMP;
        $rules = null;   
        $mypost = $data;

        $mypost = $validator->sanitize($mypost);

        $filters = array(
            'email' => 'trim|sanitize_string',
            'password' => 'trim|sanitize_string',
        );
        $mypost = $validator->filter($mypost, $filters);
        
        $rules = array(
            'email' => 'required|min_len,10',
            'password' => 'required|min_len,6',
        );
               

        $validated = $validator->validate($mypost, $rules);
        if ($validated === true) {
            $return = array(
                'post' => $data,
                'error' => false,
                'errormsg' => "",
                'mypost' => $mypost
            );
        } else {
            $return = array(
                'post' => $data,
                'error' => true,
                'errormsg' => $validator->get_readable_errors(),
                'mypost' => $mypost
            );
        }
        return $return;
    }

    /**
     * Validate input data method
     * Validates input data from user to create account
     * 
     * @param array $dataSet input data to validate
     * 
     * @return object
     */
    private function _validateCode($dataSet)
    {
        $validator = new \GUMP;
        $rules = null;   
        $mypost = $dataSet;
        
        $mypost = $validator->sanitize($mypost);

        $filters = array(
            'confirm_code' => 'trim|sanitize_string',
            'new_password' => 'trim|sanitize_string',
            'confirm_password' => 'trim|sanitize_string',
        );
        $mypost = $validator->filter($mypost, $filters);
        
        $rules = array(
            'confirm_code' => 'required|min_len,3',
            'new_password' => 'required|min_len,6',
            'confirm_password' => 'required|min_len,6',
        );
               

        $validated = $validator->validate($mypost, $rules);
        if ($validated === true) {
            $return = array(
                'post' => $dataSet,
                'error' => false,
                'errormsg' => "",
                'mypost' => $mypost
            );
        } else {
            $return = array(
                'post' => $dataSet,
                'error' => true,
                'errormsg' => $validator->get_readable_errors(),
                'mypost' => $mypost
            );
        }
        return $return;
    }

    /**
     * Confirm user account method.
     * Confirms user account based on given parameters.
     * 
     * @param array  $id     parameter key to confirm user account.
     * @param array  $param  parameter value to confirm user account.
     * @param string $schema the schema to search.
     * @param array  $fields the fields to return.
     * 
     * @return array
     */
    private function _checkExists($id, $param, $schema, $fields)
    {
        $response = array();
        if ($this->_db->dbconnect()) {
            $result = $this->_db->qry("SELECT {$fields} FROM {$schema} WHERE {$id} = '?'", $param);            
            if ($result->num_rows > 0) {
                $response = ['statuscode' => 0, 'status' => 'User exists'];
            } else {
                $response = ['statuscode' => -1, 'status' => 'User does not exist'];
            }
        } else {
            $response = ['statuscode' => -1, 'status' => 'Database connection failed'];
        }
        return $response;
    } 
}