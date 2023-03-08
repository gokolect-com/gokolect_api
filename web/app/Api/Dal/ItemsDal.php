<?php

namespace Gokolect\Api\Dal;
/**
 * Gokolect Items Data Access Layer class. 
 * Holds all attributes and methods of class.
 * 
 * PHP Version 8.1.3
 * 
 * @category Web_Application
 * @package  Gokolect_API_Service
 * @author   Tamunobarasinipiri Samuel Joseph <joseph.samuel@cinfores.com>
 * @license  MIT License
 * @link     https://PollJota.idea.cinfores.com
 */

use Gokolect\Api\Utility;
use Gokolect\Data\DataOps;

/**
 * Gokolect Items Data Access Layer Class.
 * Items Class
 * 
 * PHP Version 8.1.3
 * 
 * @category Web_Application
 * @package  Gokolect_API_Service
 * @author   Tamunobarasinipiri Samuel Joseph <joseph.samuel@cinfores.com>
 * @license  MIT License
 * @link     https://PollJota.idea.cinfores.com
 */
class ItemsDal extends DataOps
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
        $this->_host_url = $_SERVER['HTTP_HOST'];
        self::$_utility = new Utility();

        if (!is_null($_FILES)) {
            self::$_input_file = $_FILES;
        }        
    }

    /**
     * Generate general token method.
     * Generates a request token for general authentication.
     * 
     * @return array
     */
    public function setGeneralToken()
    {
        session_start();
        $id = strtotime(date('Y')).uniqid();
        $hash = password_hash('gokolect@2022~', PASSWORD_DEFAULT);
        $details1 = ['user_id'=>$id, 'extra'=>session_id(), 'schema'=>self::$table, 'email'=>$hash];

        $details = $id.'_'.session_id().'_'.$hash;
        $jwt = self::$_utility->generateJWTToken($details1); 
        $token = base64_encode($details."_".(string) $jwt);
        $response = ['statuscode' => 0, 'status' => 'token created', 'data' => $token];
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
        
        if (! preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
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
     * Give gift item method.
     * The giver manually gives out items to his choice of person.
     * 
     * @return array
     */
    public function giveOutItemToReceiver()
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
            $data['collected_date'] = date('Y-m-d H:i:s');
            $data['item_status'] = "Collected";
            $response = array();
           
            if (self::getConnection()) {
                
                $result = self::save($data);
                if ($result) {
                    static::$table = "gk_users_tbl";
                    static::$pk = "id";

                    $receiver = self::findOne(['id' => $data['receiver_id']]);
                    $dir = $receiver['profile_path'];
        
                    $file = self::$_utility::getUploadedImagesFromServer($dir, $receiver['profile_photo']);
                    $receiver['profile_photo'] = $file['photo'];

                    $fullName = $receiver['first_name'] . " " . $receiver['last_name'];
                    $message = "<div style='display:flex; width:70%; height:auto; position:relative; box-sizing:border-box; background:#f0f0f0; font-family:sans-serif,arial;'><div style='width: 100%; padding: 10px; background:#2ECC71; margin:0; box-sizing:border-box; display:flex; align-items:center; justify-content:center;'><h1 style='color: #fff; font-weight: bold;'>Gokolect</h1></div>";
                    $message.="<section style='padding:2.5rem; display:flex; flex-direction:column; background:#fcfcfc;box-sizing:border-box; width:100%;'><h4 style='color: #4f4f4f; margin:15px 0;'>Hi ".$fullName.",</h4>";
                    $message.="<p>The Item you requested to collect has been given to you.
                                    <a href='https://gokolect.com'>Gokolect</a>
                                    please login to confirm and accept the item. 
                                </p>";
                                               
                    $email_subj = "Gokolect Item Collection Notification";
                    
                    $notification = (object) array(
                        'subject'=>$email_subj,
                        'message'=>$message,
                        'email'=>$data['email'],
                        'name'=>$fullName,
                        'sender' => 'josephsamuelw1@zohomail.com',
                        'appName' => 'Gokolect Focussed on social Kindness'
                    );
                    
                    $this->_utility->sendEmailNotification($notification); 

                    static::$table = "gk_requests_tbl";
                    static::$pk = "item_code";

                    self::delete(['item_code' => $data['item_code']]);

                    $response = ['statuscode' => 0, 'status' => 'This Item is now gifted out', 'data'=>$receiver];
                } else {
                    $response = ['statuscode' => -1, 'status' => 'Platform creation failed'];
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
     * Get posted items method.
     * Gets all the items given away for collection.
     * 
     * @return array
     */
    public function getGiftItemsForCollection()
    {
        static::$table = "gk_items_tbl";
        static::$pk = "item_status";    
       
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
            $response = [];        
            if (self::getConnection()) {
                $filter = [
                    "item_status" => "Available"
                ];

                $return_data = ["item_code"=>"item_code", "item_name"=>"item_name", "item_status" => "item_status", "receiver_id", "item_desc" => "item_desc", "item_type" => "item_type", "category" => "category", "giver_id" =>"giver_id", "item_image" => "item_image", "item_image_path" => "item_image_path", "posted_date" => "posted_date", "collected_date" => "collected_date", "comment" => "comment"];
                $result = self::findResults($return_data, $filter, "all");
                
                if ($result) {
                    for ($idx = 0; $idx < count($result); $idx++) {
                        $dir = "/". str_replace(' ', '', $result[$idx]['item_image_path']);
                        $file = self::$_utility::getUploadedImagesFromServer($dir, $result[$idx]['item_image']);
                        $result[$idx]['item_image'] = $file['photo'];
                        $result[$idx]['giver'] = self::_resolveValues(
                            "gk_users_tbl", "id", $result[$idx]["giver_id"]
                        );
                        $result[$idx]['category'] = self::_resolveValues(
                            "gk_categories_tbl", 
                            "id", 
                            $result[$idx]["category"]
                        );
                    }                
                    $response = ['statuscode' => 0, 'status' => count($result).' Collectible Items Available', 'data' => $result];
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
     * Get posted items method.
     * Gets all the items given away for collection.
     * 
     * @return array
     */
    public function getGiftItemByItemCode()
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
       
        $get_maggie = base64_decode($verify_jwt->maggie);

        $json = array();
        $response = [];
        
        if ($verify_jwt->valid) {
            if (self::getConnection()) {
                $result = self::findOne(self::$_input_data);  
                $response = [
                    'statuscode' => 0, 
                    "status" => "Available gift item", 
                    "data"=>$result
                ];
            } else {
                $response = ['statuscode' => -1, 'status' => 'Connection to server failed'];
            }
        } else {
            exit(header(self::BAD_REQUEST)); 
        }
        return $response;
    }

    /**
     * Get posted items method.
     * Gets all the items given away for collection.
     * 
     * @return array
     */
    public function getGiftItemByGiverId()
    {
        static::$table = "gk_items_tbl";
        static::$pk = "giver_id";
        
        if (empty($_SERVER['HTTP_AUTHORIZATION']) || ! preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            exit(header(self::BAD_REQUEST));           
        }
        
        $jwt = $matches[1];
        if (empty($matches) || empty($jwt)) {
            exit(header(self::BAD_REQUEST));
        }

        $item = explode('_', base64_decode($jwt));
        
        $verify_jwt = self::$_utility->decodeJWTToken($item[3], $item[0]);
       
        $json = array();
        
        if ($verify_jwt->valid) {
            if (self::getConnection()) {                
                $result = self::findAll(['giver_id' => $item[0]]); 
                // $result = self::findResults(['giver_id' => self::$_input_data['giver_id']]); 
                
                if ($result) {                    
                    for ($idx = 0; $idx < count($result); $idx++) {
                        $dir = "/".str_replace(' ', '', $result[$idx]['item_image_path']);
                        $file = self::$_utility::getUploadedImagesFromServer($dir, $result[$idx]['item_image']);
                        $result[$idx]['item_image'] = $file['photo'];
                        static::$table = "gk_requests_tbl";
                        static::$pk = "item_code";
                        $count = self::findAll(["item_code" => $result[$idx]['item_code']]);
                        if ($count) {
                            $result[$idx]["request_count"] = count($count);
                        } else {
                            $result[$idx]["request_count"] = "N/A";
                        }
                        array_push($json, $result[$idx]);
                    }
                    $response = [
                        'statuscode' => 0, 
                        "status" => "Available gift item", 
                        "data"=>$json
                    ];
                } else {
                    $response = [
                        'statuscode' => -1, 
                        "status" => "No available gift item"
                    ];
                }
            } else {
                $response = ['statuscode' => -1, 'status' => 'Connection to server failed'];
            }
        } else {
            exit(header(self::BAD_REQUEST)); 
        }
        return $response;
    }

    /**
     * Get posted items method.
     * Gets all the items given away for collection.
     * 
     * @return array
     */
    public function getRequestPersonsByItemCode()
    {
        static::$table = "gk_requests_tbl";
        static::$pk = "item_code";

        if (empty($_SERVER['HTTP_AUTHORIZATION']) || ! preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            exit(header(self::BAD_REQUEST));           
        }
        
        $jwt = $matches[1];
        if (!$jwt[3]) {
            exit(header(self::BAD_REQUEST));
        }
        $item = explode('_', base64_decode($jwt));
        
        $verify_jwt = self::$_utility->decodeJWTToken($item[3], $item[0]);
       
        $json = array();
        if ($verify_jwt->valid) {
            if (self::getConnection()) {
                $result = self::findAll(['item_code' => self::$_input_data['item_code']]);                 
                
                if ($result) {                    
                    for ($idx = 0; $idx < count($result); $idx++) {
                        static::$table = "gk_users_tbl";
                        static::$pk = "id";
                        $file['photo'] = "N/A";
                        $user = self::findOne(["id" => $result[$idx]['user_id']]);
                        
                        if ($user !== null && is_array($user)) { 
                            if (!empty($user['profile_path'])) { 
                                $dir = str_replace(' ', '', $user['profile_path']);  
                                $file = self::$_utility::getUploadedImagesFromServer($dir, $user['profile_photo']);
                            }                                                       
                            $user['profile_photo'] = $file['photo'];
                            $user['request_date'] = $result[$idx]['request_date'];
                        } else {
                            $user = "N/A";
                        }
                        
                        array_push($json, $user);
                    }
                    $response = [
                        'statuscode' => 0, 
                        "status" => "Available gift item", 
                        "data"=>$json
                    ];
                } else {
                    $response = [
                        'statuscode' => -1, 
                        "status" => "No available gift item"
                    ];
                }
            } else {
                $response = ['statuscode' => -1, 'status' => 'Connection to server failed'];
            }
        } else {
            exit(header(self::BAD_REQUEST)); 
        }
        return $response;
    }

    /**
     * Get posted items method.
     * Gets all the items received by a given receiver id.
     * 
     * @return array
     */
    public function getGiftItemByReceiverId()
    {
        static::$table = "gk_items_tbl";
        static::$pk = "receiver_id";

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
        
        $response = [];
        
        if ($verify_jwt->valid) {
            if (self::getConnection()) {
                $result = self::findAll(self::$_input_data);  
                $response = [
                    'statuscode' => 0, 
                    "status" => "Available gift item", 
                    "data"=>$result
                ];
            } else {
                $response = ['statuscode' => -1, 'status' => 'Connection to server failed'];
            }
        } else {
            exit(header(self::BAD_REQUEST)); 
        }
        return $response;
    }

    /**
     * Get posted items method.
     * Gets all the items received by a given receiver id.
     * 
     * @return array
     */
    public function getGiftItemByPostedDate()
    {
        static::$table = "gk_items_tbl";
        static::$pk = "posted_date";

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
        $json = array();
        $response = [];
        
        if ($verify_jwt->valid) {
            if (self::getConnection()) {
                $result = self::findAll(self::$_input_data);  
                $response = [
                    'statuscode' => 0, 
                    "status" => "Available gift item", 
                    "data"=>$result
                ];
            } else {
                $response = ['statuscode' => -1, 'status' => 'Connection to server failed'];
            }
        } else {
            exit(header(self::BAD_REQUEST)); 
        }
        return $response;
    }

    /**
     * Get posted items method.
     * Gets all the items received by a given receiver id.
     * 
     * @return array
     */
    public function getGiftItemByCollectedDate()
    {
        static::$table = "gk_items_tbl";
        static::$pk = "collected_date";

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
        $json = array();
        $response = [];
        
        if ($verify_jwt->valid) {
            if (self::getConnection()) {
                $result = self::findAll(self::$_input_data);  
                $response = [
                    'statuscode' => 0, 
                    "status" => "Available gift item", 
                    "data"=>$result
                ];
            } else {
                $response = ['statuscode' => -1, 'status' => 'Connection to server failed'];
            }
        } else {
            exit(header(self::BAD_REQUEST)); 
        }
        return $response;
    }

    /**
     * Get posted items method.
     * Gets all the items received by a given receiver id.
     * 
     * @return array
     */
    public function getGiftItemByItemName()
    {
        static::$table = "gk_items_tbl";
        static::$pk = "item_name";

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
        $json = array();
        $response = [];
        
        if ($verify_jwt->valid) {
            if (self::getConnection()) {
                $result = self::findOne(self::$_input_data);  
                $response = [
                    'statuscode' => 0, 
                    "status" => "Available gift item", 
                    "data"=>$result
                ];
            } else {
                $response = ['statuscode' => -1, 'status' => 'Connection to server failed'];
            }
        } else {
            exit(header(self::BAD_REQUEST)); 
        }
        return $response;
    }

    /**
     * Get current user method
     * Gets details of current user using the system
     * 
     * @return array
     */
    public function getCategories()
    {
        static::$table = "gk_categories_tbl";
        static::$pk = "id";

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
        $response = [];
        
        if ($verify_jwt->valid) {
            if (self::getConnection()) {
                $result = self::findAll();
                $response = ['statuscode' => 0, 'status' => 'Available Gift Items!', 'data' => $result];       
            } else {
                $response = ['statuscode' => -1, 'status' => 'Connection to server failed'];
            }
        } else {
            exit(header(self::BAD_REQUEST)); 
        }
        return $response;
    }

    /**
     * Kollect Items method.
     * Adds a user to a queue for an item if the user had not collected an item already.
     * 
     * @return array
     */
    public function collectAnItem()
    {
        static::$table = "gk_categories_tbl";
        static::$pk = "id";

        if (empty($_SERVER['HTTP_AUTHORIZATION']) || ! preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            exit(header(self::BAD_REQUEST));           
        }
        
        $jwt = $matches[1];
        if (empty($matches) || empty($jwt)) {
            exit(header(self::BAD_REQUEST));
        }
        $item = explode('_', base64_decode($jwt));
        
        $verify_jwt = self::$_utility->decodeJWTToken($item[3], $item[0]);      
        $response = [];
        
        if ($verify_jwt->valid) {
            if (self::getConnection()) {
                $check_collection = self::_checkCollection($item[0]);
                $check_item_status = self::_countRequests(self::$_input_data['item_code']);
                $check_request = self::_checkItemRequest($item[0], self::$_input_data['item_code']);
                if ($check_item_status < 10) {
                    if (!$check_request) {
                        if (!$check_collection) {
                            static::$table = "gk_requests_tbl";
                            self::$_input_data['user_id'] = $item[0];
                            self::$_input_data['request_date'] = date('Y-m-d H:i:s');                    
                            $result = self::save(self::$_input_data);
                            if ($result) {
                                $response = [
                                    'statuscode' => 0, 
                                    'status' => 'You have been added to a waiting list'
                                ];
                            } else {
                                $response = [
                                    'statuscode' => -1, 
                                    'status' => 'This item is no longer avaialable for collection'
                                ];
                            }
                        } else {                    
                            $response = [
                                'statuscode' => -1, 
                                'status' => 'You have been added to a waiting list'
                            ];       
                        }
                    } else {                    
                        $response = [
                            'statuscode' => -1, 
                            'status' => 'You can request for an item only once!'
                        ];       
                    }
                } else {
                    $response = [
                        'statuscode' => -1, 
                        'status' => 'This item is no longer available for collection'
                    ];
                }
            } else {
                $response = [
                    'statuscode' => -1, 
                    'status' => 'Connection to server failed'
                ];
            }
        } else {
            exit(header(self::BAD_REQUEST)); 
        }
        return $response;   
    }
    

    /**
     * Check collection method.
     * Checks if a user already collected an item in the day.
     * 
     * @param string $user requested user id
     * 
     * @return boolean
     */
    private static function _checkCollection($user)
    {
        static::$table = "gk_items_tbl";
        $filter = [
            'receiver_id'=>$user, 
            'item_status' => "collected", 
            "collected_date" => date("Y-m-d H:i:s")
        ];

        $fields = ["item_code" => 'item_code'];

        $result = self::findResults($fields, $filter);
        return (!empty($result))? true:false;        
    }

    /**
     * Check Item status method.
     * Checks the status of an item if it is available or not.
     * 
     * @param string $item_code item code to check.
     * 
     * @return boolean
     */
    private static function _checkItemStatus($item_code)
    {
        static::$table = "gk_items_tbl";
        static::$pk = "item_code";
        $filter = [
            'item_code'=> $item_code,
            'item_status' => "Available"
        ];

        $fields = ["item_status" => 'item_status'];

        $result = self::findResults($fields, $filter);
        return (!empty($result))? true:false;        
    }

    /**
     * Check Item request method.
     * Checks if a user had requested for an item.
     * 
     * @param string $user_id   The user id.
     * @param string $item_code item code to check.
     * 
     * @return boolean
     */
    private static function _checkItemRequest($user_id, $item_code)
    {
        static::$table = "gk_requests_tbl";
        static::$pk = "item_code";
        $filter = [
            'item_code'=> $item_code,
            'user_id' => $user_id
        ];

        $fields = ["request_date" => 'request_date'];

        $result = self::findResults($fields, $filter);
        return (!empty($result))? true:false;        
    }

    /**
     * Resolves values method.
     * Resolves values to their required records.
     * 
     * @param string $table    the table to search.
     * @param string $criteria the criteria the resolve with.
     * @param string $value    the criteria value.
     * 
     * @return array
     */
    private static function _resolveValues($table, $criteria, $value)
    {
        $filter = [
            $criteria=> $value
        ];
        $query = "SELECT * FROM $table WHERE $criteria = $value";    
        $result = self::queryOne($query);
        if ($result) {
            $response = $result;
        } else {
            $response = "N/A";
        }
        return $response;        
    }

    /**
     * Get request queue method.
     * Gets all requests in the queue based on a given Item.
     * 
     * @return array
     */
    public function getRequestQueueById()
    {
        static::$table = "gk_requests_tbl";
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
        $response = [];
        
        if ($verify_jwt->valid) {
            if (self::getConnection()) {
                $result = self::findAll(['item_code' => self::$_input_data['item_code']]);
                if ($result) {
                    static::$table = "gk_users_tbl";
                    static::$pk = "id";
                    for ($idx = 0; $idx < count($result); $idx++) {
                        $result['collector'] = self::findOne(["id" => $result[$idx]['user_id']]);
                        $dir = $result['collector']['profile_path'];
                        $file = self::$_utility::getUploadedImagesFromServer($dir, $result['collector']['profile_photo']);        
                        $result['collector']['profile_photo'] = $file['photo'];
                        unset($result['collector']['password']);
                        unset($result['collector']['confirm_code']);
                    }
                }
                $response = ['statuscode' => 0, 'status' => 'List of requests', 'data' => $result];       
            } else {
                $response = ['statuscode' => -1, 'status' => 'Connection to server failed'];
            }
        } else {
            exit(header(self::BAD_REQUEST)); 
        }
        return $response;
    }

    /**
     * Count requests method.
     * Counts the requests made for a given item.
     * 
     * @param string $item_code the item to count.
     * 
     * @return int the number of requests made
     */
    private function _countRequests(string $item_code)
    {
        static::$table = "gk_requests_tbl";
        static::$pk = "item_code";
        $count = 0;
        $result = self::findAll(["item_code" => $item_code]);

        if (is_array($result)) {
            $count = count($result);
            if ($count === 10) {
                static::$table = "gk_items_tbl";
                $fields = ['item_code' => $item_code, "item_status" => "Closed"];
                self::update($fields);
            }
        }
        return $count;
    }
}