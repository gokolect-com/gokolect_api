<?php

namespace Gokolect\Api\Dal;
/**
 * Gokolect Administration class holds all 
 * attributes of administration functionalities
 * 
 * PHP Version 8.1.3
 * 
 * @category Web_Application
 * @package  GokolectAPI
 * @author   Tamunobarasinipiri Samuel Joseph <joseph.samuel@cinfores.com>
 * @license  MIT License
 * @link     https://Gokolect.idea.cinfores.com
 */

use Gokolect\Api\Utility;
use Gokolect\Data\DataOps;

/**
 * Gokolect Admin class holds all attributes of the Administration functionalities
 * Admin Class
 * 
 * PHP Version 8.1.3
 * 
 * @category Web_Application
 * @package  GokolectAPI
 * @author   Tamunobarasinipiri Samuel Joseph <joseph.samuel@cinfores.com>
 * @license  MIT License
 * @link     https://Gokolect.idea.cinfores.com
 */
class UsersDal extends DataOps
{

    private static $_input_data;
    private static $_input_file;
    private $_host_url = null;
    private static $_utility = null;
    private const BAD_REQUEST = "HTTP/1.0 400 Bad Request";

    /**
     * Class constructor
     * 
     * @param array $data  An array of user input data.
     * @param array $files file to be uploaded.
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

        $this->_host_url = $_SERVER['HTTP_HOST']."/gokolect_api/web/index.php";

        self::$_utility = new Utility();
        
    }

    /**
     * Get current user method
     * Gets details of current user using the system
     * 
     * @return array
     */
    public function getCurrentUser()
    {
        static::$table = "gk_users_tbl";
        static::$pk = "id";
        
        if (! preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            exit(header(self::BAD_REQUEST));           
        }
        
        $jwt = $matches[1];
        if (empty($matches) || empty($jwt)) {
            exit(header(self::BAD_REQUEST));
        }

        $item = explode('_', base64_decode($jwt));
        
        $verify_jwt = self::$_utility->decodeJWTToken($item[3], $item[0]);
       
        $get_maggie = base64_decode($verify_jwt->maggie);
        $json = array();
        $response = [];
        
        $result = self::findOne(['id' => $get_maggie]);
        
        if (!empty($result)) {
            $json = $result;
            $dir = $json['profile_path'];
            
            $file = self::$_utility::getUploadedImagesFromServer($dir, $json['profile_photo']);
            
            $json['profile_photo'] = $file['photo'];
            unset($json['password']);
            unset($json['confirm_code']);
            $response = ['statuscode' => 0, 'status' => 'User details Success', 'data' => $json];
        } else {
            $response = ['statuscode' => -1, 'status' => 'No record avaialable'];
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
        static::$table = "gk_users_tbl";
        static::$pk = "email";
        $data = self::$_input_data;
        
        if (empty($_SERVER['HTTP_AUTHORIZATION']) || ! preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            exit(header(self::BAD_REQUEST));           
        }
        
        $jwt = $matches[1];
    
        if (empty($matches) || empty($jwt)) {
            exit(header(self::BAD_REQUEST));         
        }
        $item = explode('_', base64_decode($jwt));
        
        $verify_jwt = self::$_utility->decodeJWTToken($item[3], $item[0]);
        $get_maggie = base64_decode($verify_jwt->maggie);

        if (!self::$_input_data['confirm_password'] == self::$_input_data['new_password']) {
            $response = ['statuscode' => -1, 'status' => 'Both passwords do not math'];
            return $response;
        }
        
        $old_password = base64_encode(self::$_input_data['current_password']);
        $new_password = base64_encode(self::$_input_data['new_password']);
        $fields = ['email' => 'email'];
        $data['email'] = $get_maggie;

        if (self::getConnection()) {
            $result = self::findResults($fields, $data);
            if (password_verify($new_password, $result['password'])) {
                $response = ['statuscode' => -1, 'status' => "New password cannot be same as your current password"];
            } else if (password_verify($old_password, $result['password'])) {
                $password = password_hash($new_password, PASSWORD_DEFAULT);
                $data['password'] = $password;
                $update = self::update($data);                    
                if ($update) {
                    $response = ['statuscode' => 0, "status" => "Password changed successfully"];
                }
            } else {
                $response = ['statuscode' => -1, "status" => "current password is incorrect"];
            }
        } else {
            $response = ['statuscode' => -1, "status" => "Connection error"];
        }
        return $response;
    }
    
    /**
     * Update admin profile method
     * Updates the admin profile on request
     * 
     * @return array
     */
    public function updateProfile()
    {
        static::$table = "gk_users_tbl";
        static::$pk = "id";
        $upload = null;
        
        if (empty($_SERVER['HTTP_AUTHORIZATION']) || ! preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            exit(header(self::BAD_REQUEST));           
        }
        
        $jwt = $matches[1];

        if (empty($matches) || empty($jwt)) {
            exit(header(self::BAD_REQUEST));
        }

        $item = explode('_', base64_decode($jwt));
        
        $verify_jwt = self::$_utility->decodeJWTToken($item[3], $item[0]);
       
        $response = array();
        if ($verify_jwt->valid) {
            $get_maggie = base64_decode($verify_jwt->maggie);
            if (self::getConnection()) {
                $my_dir = str_replace(' ', '',self::$_input_data["last_name"].self::$_input_data["first_name"].$get_maggie);                            
                $dir = "/user_profiles".DIRECTORY_SEPARATOR.$my_dir;
                if (self::$_input_data['update_type'] == 'details') {
                    if (!empty(self::$_input_file['profile_photo']['type']) || !empty(self::$_input_file['profile_photo']['name']) || !empty(self::$_input_file['profile_photo']['tmp_name'])) {
                        $upload = self::$_utility->uploadImg(self::$_input_data, self::$_input_file, $dir);
                    } else {
                        $upload['statuscode'] = 200;
                    }
                } else {
                    $upload['statuscode'] = 200;
                }
                
                if ($upload['statuscode'] === 200) {
                    self::$_input_data['id'] = $verify_jwt->userName;
                    self::$_input_data['modified'] = date('now');
                    unset(self::$_input_data['update_type']);

                    if (!empty(self::$_input_file)) {
                        self::$_input_data['profile_photo'] = $upload['filename'];
                        self::$_input_data['profile_path'] = $upload['target_dir'];
                        unset(self::$_input_data['avatar_remove']);
                    }                
                    $result = self::update(self::$_input_data);

                    if ($result) {
                        $response = ['statuscode' => 0, 'status' => 'Profile updated successfully'];
                    } else {
                        $response = ['statuscode' => -1, 'status' => 'Profile update failed'];
                    }
                } else {
                    $response = ['statuscode' => -1, 'status' => "Unable to upload profile picture"];
                }            
            } else {
                $response = ['statuscode' => -1, 'status' => "Connection error"];
            } 
        } else {
            $response = ['statuscode' => -1, 'status' => "Your session has expired"];
        }       
        return $response;
    }

    /**
     * Create platform method.
     * Creates a new election platform on request
     * 
     * @return array
     */
    public function giveOutItem()
    {
        static::$table = "gk_items_tbl";
        static::$pk = "item_code";
      
        if (empty($_SERVER['HTTP_AUTHORIZATION']) || ! preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            exit(header(self::BAD_REQUEST));           
        }
        
        $jwt = $matches[1];
        if (empty($matches) || empty($jwt)) {
            exit(header(self::BAD_REQUEST));
        }
        $item = explode('_', base64_decode($jwt));
        $verify_jwt = self::$_utility->decodeJWTToken($item[3], $item[0]);
        if ($verify_jwt->valid) {

            $data = self::$_input_data;
            $data['giver_id'] = $verify_jwt->userName;
            $data['item_code'] = self::_generateItemCode();
            $data['item_status'] = "Available";
            $dir = "app_items/gkit_". strtotime(date('Y-m-d'));
            $response = array();
           
            if (self::getConnection()) {
                $upload = self::$_utility::uploadItems($data, self::$_input_file, $dir);
                   
                if ($upload['statuscode'] == 200) {
                    $data['item_image'] = $upload['filename'];
                    $data['item_image_path'] = $upload['target_dir'];
                    
                    $result = self::save($data);
                    if ($result) {
                        $items = self::findOne(['item_code' => $data['item_code']]);
                        $dir = $items['item_image_path'];
            
                        $file = self::$_utility::getUploadedImagesFromServer($dir, $items['item_image']);
                        $items['item_image'] = $file['photo'];
                        $response = ['statuscode' => 0, 'status' => 'The Item has been placed for collection ' .$upload['status'], 'data'=>$items];
                    } else {
                        $response = ['statuscode' => -1, 'status' => 'Platform creation failed'];
                    }
                } else {
                    $response = ['statuscode' => -1, 'status' => $upload['status']];
                }           
            } else {
                $response = ['statuscode' => -1, 'status' => 'Failed to connect to server'];
            }        
        } else {
            header("HTTP/1.1 500 Internal Server Error");
            exit(0);
        }
        return $response;
    }

    /**
     * Get platform method
     * 
     * Get's a platform details based on given parameters
     * 
     * @return array
     */
    public function getUploadItem() 
    {
        
        if (empty($_SERVER['HTTP_AUTHORIZATION']) || ! preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            exit(header(self::BAD_REQUEST));           
        }
        
        $jwt = $matches[1];
        if (empty($matches) || empty($jwt)) {
            exit(header(self::BAD_REQUEST));
        }

        $item = explode('_', base64_decode($jwt));
        $data = self::$_input_data;
        $verify_jwt = self::$_utility->decodeJWTToken($item[3], $item[0]);
        if ($verify_jwt->valid) {
            $json = array();
            $response = [];        
            if (self::getConnection()) {
                $result = self::findOne(['item_code' => $data['item_code']]);
                if ($result) {
                    
                    $dir = str_replace(' ', '', $result['item_image_path']);
                    $file = self::$_utility::getUploadedImages($dir, $result['item_image']);
                    $result['item_image'] = $file['photo'];
                    array_push($json, $result);
                    
                    $response = ['statuscode' => 0, 'status' => 'User details Success', 'data' => $json];
                } else {
                    $response = ['statuscode' => -1, 'status' => 'No platform avaialable'];
                }
            }
        } else {
            $response = ['statuscode'=> -1, "status" => "Invalid session"]; 
        }
        return $response;
    }

    /**
     * Get platform method
     * 
     * Get's a platform details based on given parameters
     * 
     * @return array
     */
    public function getAllUploadItems() 
    {
        
        if (empty($_SERVER['HTTP_AUTHORIZATION']) || ! preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            exit(header(self::BAD_REQUEST));           
        }
        
        $jwt = $matches[1];
        if (empty($matches) || empty($jwt)) {
            exit(header(self::BAD_REQUEST));
        }

        $item = explode('_', base64_decode($jwt));
        $data = self::$_input_data;

        $verify_jwt = self::$_utility->decodeJWTToken($item[3], $item[0]);
        if ($verify_jwt->valid) {
            $json = array();
            $response = [];        
            if (self::getConnection()) {
                $result = self::findAll();
                if ($result) {
                    for ($idx = 0; $idx < count($result); $idx++) {
                        $dir = str_replace(' ', '', $result['item_image_path']);
                        $file = self::$_utility::getUploadedImagesFromServer($dir, $result['item_image']);
                        $result['item_image'] = $file['photo'];
                        array_push($json, $result);
                    }                
                    $response = ['statuscode' => 0, 'status' => 'User details Success', 'data' => $json];
                } else {
                    $response = ['statuscode' => -1, 'status' => 'No platform avaialable'];
                }
            }
        } else {
            $response = ['statuscode'=> -1, "status" => "Invalid session"]; 
        }
        return $response;
    }

    /**
     * Generate random Item Code (IC) method.
     * Randomly generates a IC for Items that will uniquely 
     * identify each item.
     * 
     * @param string $cat_id platform id
     * @param string $length length of IC characters to be generated.
     * 
     * @return string
     */
    private static function _generateItemCode(string $cat_id = null, int $length = 8)
    {
        $chars =  'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.
                  '0123456789';      
        $str = 'gk';
        $max = strlen($chars) - 1;      
        for ($i=0; $i < $length; $i++) {            
            $str .= $chars[random_int(0, $max)].$cat_id;
        }      
        $str = strtolower($str);
        return $str;
    }     
    
    /**
     * Change password 
     * Change default or previous password
     * 
     * @return bool
     */
    public function addCategory()
    {
        static::$table = "gk_categories_tbl";
        static::$pk = "id";
        $data = self::$_input_data;
       
        if (empty($_SERVER['HTTP_AUTHORIZATION']) || ! preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            exit(header(self::BAD_REQUEST));           
        }
        
        $jwt = $matches[1];
    
        if (empty($matches) || empty($jwt)) {
            exit(header(self::BAD_REQUEST));
        }

        $item = explode('_', base64_decode($jwt));
        
        $verify_jwt = self::$_utility->decodeJWTToken($item[3], $item[0]);
        
        if (self::getConnection()) {
            $result = self::exists(['category_name' => $data['category_name']]);
            if (!$result) {                                
                $add = self::save($data);                    
                if ($add) {
                    $response = ['statuscode' => 0, "status" => "Category has been added"];
                } else {
                    $response = ['statuscode' => 0, "status" => "Could not add the category"];
                }
            } else {
                $update = self::update($data);
                if ($update) {
                    $response = ['statuscode' => -1, "status" => "Category has been added"];
                }
                $response = ['statuscode' => -1, "status" => "Could not add the category"];
            }
        } else {
            $response = ['statuscode' => -1, "status" => "Connection error"];
        }
        return $response;
    }
}