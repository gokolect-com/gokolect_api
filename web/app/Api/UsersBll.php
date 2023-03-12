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
// require_once "vendor/firebase/php-jwt/src/JWT.php";

use Gokolect\Api\Dal\UsersDal;
use Gokolect\GUMP;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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
class UsersBll
{
    private static $_input_data;
    private static $_input_file;
    /**
     * Class constructor
     * 
     * @param array $data  An array of user input data.
     * @param array $files An array of user input file
     * 
     * @return array
     */
    public function __construct(array $data = null, array $files = null)
    {        
        if (!is_null($data)) {
            unset($data['action']);
            self::$_input_data = $data;
        }   

        if (!is_null($files)) {
            self::$_input_file = $files;
        }                
    }

    /**
     * Get user profile method.
     * Get's user profile on successful user authentication.
     * 
     * @return array
     */
    public function getUserProfile()
    {        
        $user = new UsersDal();
        $response = $user->getCurrentUser();
        return $response;
    }

    /**
     * Give out item method.
     * Uploads items to be given out by users.
     * 
     * @return array
     */
    public function giveOutItems()
    {
        $validated = $this->_validateInputData(self::$_input_data);
        if ($validated['error']) {
            $response = ['statuscode' => -1, 'status' => $validated['errormsg']];
        } else {
            $data = $validated['my_post'];
            $_auth = new UsersDal($data, self::$_input_file);
            $response = $_auth->giveOutItem();
        }
        return $response;
    }

    /**
     * Update user method.
     * Updates a user's profile.
     * 
     * @return array
     */
    public function updateUserProfile()
    {
        $validated = null;        
        if (self::$_input_data['update_type'] === 'details') {
            $validated = $this->_validateProfileData(self::$_input_data);
        } else if (self::$_input_data['update_type'] === 'social_media') {
            $validated = $this->_validateSocialData(self::$_input_data);
        }
        if ($validated['error']) {
            $response = ['statuscode' => -1, 'status' => $validated['errormsg']];
        } else {
            $data = $validated['my_post'];
            $_auth = new UsersDal($data, self::$_input_file);
            $response = $_auth->updateProfile();
        }
        return $response;
    }

    /**
     * Give out item method.
     * Uploads items to be given out by users.
     * 
     * @return array
     */
    public function addCategories()
    {
        $validated = $this->_validateCategoryData(self::$_input_data);
        if ($validated['error']) {
            $response = ['statuscode' => -1, 'status' => $validated['errormsg']];
        } else {
            $data = $validated['my_post'];
            $_auth = new UsersDal($data, self::$_input_file);
            $response = $_auth->addCategory();
        }
        return $response;
    }

    /**
     * Change password 
     * Change default or previous password
     * 
     * @return bool
     */
    public function changePassword()
    {
        $validated = self::_validatePassword(self::$_input_data);
        if ($validated['error']) {
            $response = ['statuscode' => -1, 'status' => $validated['errormsg']];
        } else {
            $data = $validated['mypost'];
            $user = new UsersDal($data);
            $response = $user->changePassword();
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
        if ($this->_db->dbconnect()) {
            if ($this->_exists('user_id, email', $this->_input_data->email, 'admins', 'user_id', $this->_db)) {
                $result = $this->_db->qry(
                    'delete',
                    'gate_house_users',
                    ['email' => $this->_input_data->email]
                );
                if ($result) {
                    $response = ['statuscode' => 0, 'status' => "User account deleted successfully"];
                } else {
                    $response = ['statuscode' => 1, 'status' => "Cannot delete this user account!"];
                }
            } else {
                $response = ['statuscode' => -1, 'status' => "Unknown user account!"];
            }
        } else {
            $response = ['statuscode' => -1, 'status' => "Connection error!"];
        }
        return $response;
    }

    /**
     * Lock user account 
     * Locks a user account on request using user email 
     * 
     * @return array   
     */
    public function lockUser()
    {
        if ($this->_db->dbconnect()) {
            if ($this->_exists('user_id, email', self::$_input_data['email'], 'admins', 'user_id', $this->_db)) {
                if (!$this->_isLocked(self::$_input_data["email"], $this->_db)) {
                    $result = $this->_db->qry(
                        'update',
                        'gate_house_users',
                        [
                            ['is_locked'=> false], 
                            ['email'=>self::$_input_data["email"]]
                        ],
                        ['is_locked' => true]
                    );
                    if ($result) {
                        $response = ['statuscode' => 0, 'status' => "This user account has been locked now!"];
                    } else {
                        $response = ['statuscode' => -1, 'status' =>"Unable to lock user account at the moment"];
                    }
                } else {
                    $response = ['statuscode' => -1, 'status' => "User account was locked already!"];
                }
            } else {
                $response = ['statuscode' => -1, 'status' => "Unkown user account!"];
            }
        } else {
            $response = ['statuscode' => -1, 'status' => "Connection error"];
        }
        return $response;
    }

    /**
     * Is locked 
     * Checks if a user's account is locked or not
     * 
     * @param string $user_id the user email to check with 
     * @param object $db      database connection object
     * 
     * @return bool
     */
    private function _isLocked($user_id, $db)
    {
        $result = $db->qry(
            'select',
            'gate_house_users',
            ['email' => $user_id],
            ['is_locked']
        );
        if ($result->num_rows() > 0) {
            $rows = $result->fetch_assoc();
            $response = $rows['is_locked']? true : false;
        }
        return $response;
    }

    /**
     * Validate input data method
     * Validates input data from user to create account
     * 
     * @param array $dataSet input data to validate
     * 
     * @return object
     */
    private function _validateInputData($dataSet)
    {
        $validator = new \GUMP;
        $rules = null;   
        $mypost = $dataSet;
    
        $mypost = $validator->sanitize($mypost);

        $filters = array(
            'item_name' => 'trim|sanitize_string',
            'item_desc' => 'trim|sanitize_string',
            'category' => 'trim|sanitize_string',   
            'item_type' => 'trim|sanitize_string',
            'comment' => 'trim|sanitize_string',         
        );
        $mypost = $validator->filter($mypost, $filters);
        
        $rules = array(
            'item_name' => 'required|min_len,1|max_len,200',
            'item_desc' => 'required|min_len,1|max_len,200',
            'category' => 'required|min_len,1|max_len,200',
            'item_type' => 'min_len,0|max_len,200',
            'comment' => 'min_len,0|max_len,200'
        );               

        $validated = $validator->validate($mypost, $rules);
        if ($validated === true) {
            $return = array(
                'post' => $dataSet,
                'error' => false,
                'errormsg' => "",
                'my_post' => $mypost
            );
        } else {
            $return = array(
                'post' => $dataSet,
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
     * @param array $dataSet input data to validate
     * 
     * @return array
     */
    private function _validateProfileData($dataSet)
    {
        $validator = new \GUMP;
        $rules = null;   
        $mypost = $dataSet;
    
        $mypost = $validator->sanitize($mypost);
        
        $filters = array(
            'first_name' => 'trim|sanitize_string',
            'last_name' => 'trim|sanitize_string',
            'other_name' => 'trim|sanitize_string',   
            'user_name' => 'trim|sanitize_string',
            'street' => 'trim|sanitize_string',         
            'city' => 'trim|sanitize_string',         
            'state' => 'trim|sanitize_string',         
            'country' => 'trim|sanitize_string',         
            'dob' => 'trim|sanitize_string',         
            'gender' => 'trim|sanitize_string',         
            'mobile' => 'trim|sanitize_string',         
            'nick_name' => 'trim|sanitize_string',        
        );
        $mypost = $validator->filter($mypost, $filters);
        
        $rules = array(
            'first_name' => 'required|min_len,3|max_len,200',
            'last_name' => 'required|min_len,3|max_len,200',
            'other_name' => 'min_len,0|max_len,200',
            'user_name' => 'min_len,0|max_len,200',
            'street' => 'required|min_len,3|max_len,200',
            'city' => 'required|min_len,3|max_len,200',
            'state' => 'min_len,2|max_len,200',
            'country' => 'required|min_len,2|max_len,200',
            'dob' => 'min_len,0|max_len,200',
            'gender' => 'required|min_len,1|max_len,200',
            'mobile' => 'required|min_len,6|max_len,200',
            'nick_name' => 'min_len,0|max_len,200',
        );               

        $validated = $validator->validate($mypost, $rules);
        if ($validated === true) {
            $return = array(
                'post' => $dataSet,
                'error' => false,
                'errormsg' => "",
                'my_post' => $mypost
            );
        } else {
            $return = array(
                'post' => $dataSet,
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
     * @param array $dataSet input data to validate
     * 
     * @return array
     */
    private function _validateSocialData($dataSet)
    {
        $validator = new \GUMP;
        $rules = null;   
        $mypost = $dataSet;
    
        $mypost = $validator->sanitize($mypost);
        
        $filters = array(
            'twitter' => 'trim|sanitize_string',
            'facebook' => 'trim|sanitize_string',
            'instagram' => 'trim|sanitize_string',   
            'tiktok' => 'trim|sanitize_string'       
        );
        $mypost = $validator->filter($mypost, $filters);
        
        $rules = array(
            'twitter' => 'required|min_len,0|max_len,200',
            'facebook' => 'required|min_len,0|max_len,200',
            'instagram' => 'required|min_len,0|max_len,200',
            'tiktok' => 'required|min_len,0|max_len,200'
        );               

        $validated = $validator->validate($mypost, $rules);
        if ($validated === true) {
            $return = array(
                'post' => $dataSet,
                'error' => false,
                'errormsg' => "",
                'my_post' => $mypost
            );
        } else {
            $return = array(
                'post' => $dataSet,
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
     * @param array $dataSet input data to validate
     * 
     * @return array
     */
    private static function _validateCategoryData($dataSet)
    {
        $data = (array) $dataSet;   

        $validator = new \GUMP;
        $rules = null;   
        $mypost = $data;

        $mypost = $validator->sanitize($mypost);

        $filters = array(
            'category_name' => 'trim|sanitize_string',
            'sub_category' => 'trim|sanitize_string', 
            'description' => 'trim|sanitize_string'       
        );
        $mypost = $validator->filter($mypost, $filters);
        
        $rules = array(
            'category_name' => 'required|min_len,3|max_len,200',
            'sub_category' => 'required|min_len,0|max_len,200',
            'description' => 'required|min_len,0|max_len,200'
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
     * @param array $dataSet input data to validate
     * 
     * @return array
     */
    private static function _validatePassword($dataSet)
    {
        $data = (array) $dataSet;   

        $validator = new \GUMP;
        $rules = null;   
        $mypost = $data;

        $mypost = $validator->sanitize($mypost);

        $filters = array(
            'current_password' => 'trim|sanitize_string',
            'new_password' => 'trim|sanitize_string'         
        );
        $mypost = $validator->filter($mypost, $filters);
        
        $rules = array(
            'current_password' => 'required|min_len,6|max_length,200',
            'ne_password' => 'required|min_len,6|max_length,200'
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
     * @param array $dataSet input data to validate
     * 
     * @return array
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
     * @return array
     */
    private function _validateCode($dataSet)
    {
        $data = (array) $dataSet;

        $validator = new \GUMP;
        $rules = null;   
        $mypost = $data;

        $mypost = $validator->sanitize($mypost);

        $filters = array(
            'confirm_code' => 'trim|sanitize_string',
            'new_password' => 'trim|sanitize_string',
            'confirm_password' => 'trim|sanitize_string',
        );
        $mypost = $validator->filter($mypost, $filters);
        
        $rules = array(
            'confirm_code' => 'required|min_len,6',
            'new_password' => 'required|min_len,6',
            'confirm_password' => 'required|min_len,6',
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