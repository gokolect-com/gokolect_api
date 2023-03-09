<?php

namespace Gokolect\Api\Dal;
/**
 * Gokolect Payment Class. 
 * Holds all attributes and methods of class.
 * 
 * PHP Version 8.1.3
 * 
 * @category Web_Application
 * @package  Gokolect_API_Service
 * @author   Tamunobarasinipiri Samuel Joseph <joseph.samuel@cinfores.com>
 * @license  MIT License
 * @link     https://gokolect.com
 */

session_start();
// session_destroy();
// Prevent direct access to this class

define("BASEPATH", 1);

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
class PaymentDal extends DataOps
{
    private static $_input_data;
    private $_host_url = null;
    private static $_utility = null;
    private const BAD_REQUEST = "HTTP/1.0 400 Bad Request";
    private static $_payment = null;
    private static $_prefix = "gkt_"; // Change this to the name of your business or app
    private static $_overrideRef = false;

    /**
     * Class constructor
     * 
     * @param array $data  An array of user input data.
     * @param array $files file to be uploaded.
     * 
     * @return array
     */
    public function __construct(array $data = null)
    {        
        if (!is_null($data)) {
            unset($data['action']);
            self::$_input_data = $data;
        }

        $this->_host_url = $_SERVER['HTTP_HOST']."/gokolect_api/";
        self::$_utility = new Utility();                
    }

    /**
     * Get payment url method.
     * Gets the url for a given payment.
     * 
     * @param string $url  the payment url.
     * @param array  $data require payment fields
     * 
     * @return string
     */
    public static function getURL($url, $data = array())
    {
        $urlArr = explode('?', $url);
        $params = array_merge($_GET, $data);
        $new_query_string = http_build_query($params) . '&' . $urlArr[1];
        $newUrl = $urlArr[0] . '?' . $new_query_string;
        return $newUrl;
    }

    /**
     * Generate payment reference method.
     * Generates a reference for donations.
     * 
     * @return array
     */
    public static function generateRef()
    {   
        static::$table = "gk_donations_tbl";
        static::$pk = "id";
        $count = self::countAll();
        $lastId = self::lastSavedId();
        if ($count <= 0) {
            $count = 1;
        } else {
            $count = $count + 1;
        }
        $payRef = uniqid(static::$_prefix).$count;
        return [
            'statuscode' => 0, 
            'status' => 'token created '. $lastId, 
            'data' => $payRef
        ];
    } 

    
    /**
     * Process payment for donations method.
     * Processes the donation payment on request
     * 
     * @return array
     */
    public function processPayments()
    {
        static::$table = "gk_donations_tbl";
        static::$pk = "id";
        $response = array();
        $ref = self::generateRef();

        // $rof = $postData['amount'] - (10.05 * 100);

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

            $post_url = "https://api.flutterwave.com/v3/payments";

            $name = self::$_input_data["lastname"]." ".self::$_input_data["firstname"];

            $post_data = array(
                "tx_ref"=>$ref["data"],
                "currency"=> self::$_input_data["currency"],
                "amount"=>self::$_input_data["amount"],
                "customer"=>array(
                    "name"=>$name,
                    "email"=>self::$_input_data["email"],
                    "phone_number"=>self::$_input_data["phonenumber"]
                ),
                "customizations"=>array(
                    "title"=>"Gokolect Donations Window",
                    "description"=>"Your support to social kindness and global charity",
                    "logo"=>"https://bootqlass.com/gokolect_api/assets/img/Gokolectlogo 1.png"
                ),

                "meta"=>array(
                    "first_name"=>self::$_input_data["firstname"],
                    "last_name"=>self::$_input_data["lastname"],
                    "reason"=> "Making the world a better place by engaging people in social kindness.",
                    "comment"=> self::$_input_data["comment"]
                ),
                "redirect_url"=>"https://gokolectapp.bootqlass.com/?action=verify_payment"
            );

            $result = self::handleCURL($post_data, $post_url);
            
            $saveable_data = [
                "firstname"=>self::$_input_data["firstname"],
                "lastname"=>self::$_input_data["lastname"],
                "email"=>self::$_input_data["email"],
                "phonenumber"=>self::$_input_data["phonenumber"],
                "country"=>self::$_input_data["country"],
                "currency"=>self::$_input_data["currency"],
                "amount"=>self::$_input_data["amount"],
                "comment"=>self::$_input_data["comment"],
                "tx_ref"=>$ref["data"]
            ];
            $save = self::save($saveable_data);
            $response = $result->data->link;        
        } else {
            exit(header("HTTP/1.1 500 Internal Server Error <br> You are not allowed to access this page"));
        }
        return $response;
    }

    /**
     * Process payment response
     * Process the payment response from initiated payment
     * 
     * @return array
     */
    public function verifyPayments()
    {
        static::$table = "gk_donations_tbl";
        static::$pk = "tx_ref";
        $response = array();
        // if (! preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
        //     exit(header(self::BAD_REQUEST));          
        // }
        
        // $jwt = $matches[1];
        // if (empty($matches) || empty($jwt)) {
        //     exit(header(self::BAD_REQUEST));
        // }
        // $item = explode('_', base64_decode($jwt));
        // $verify_jwt = self::$_utility->decodeJWTToken($item[3], $item[0]);
        // if ($verify_jwt->valid) {
                
            $data = self::$_input_data;
            if ($data['status'] === "successful") {
                $url = "https://api.flutterwave.com/v3/transactions/{$data["transaction_id"]}/verify";

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 2);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");

                curl_setopt($curl, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer {$_SERVER["SECRET_KEY"]}",
                    "Content-Type: application/json"
                ]);

                $result = curl_exec($curl);

                $error = curl_error($curl);
                if ($error) {
                    $response = json_decode($error);
                }
                curl_close($curl);
                $response_data = json_decode($result);
                if ($response_data->status === "success" && $response_data->data->status === "successful") {                    
                    $saveable_data = [
                        "transaction_id"=>$response_data->data->id,
                        "tx_ref"=>$response_data->data->tx_ref,
                        "flw_ref"=>$response_data->data->flw_ref,
                        "device_fingerprint"=>$response_data->data->device_fingerprint,
                        "charged_amount"=>$response_data->data->charged_amount,
                        "app_fee"=>$response_data->data->app_fee,
                        "merchant_fee"=>$response_data->data->merchant_fee,
                        "processor_response"=>$response_data->data->processor_response,
                        "auth_model"=>$response_data->data->auth_model,
                        "ip"=>$response_data->data->ip,
                        "narration"=>$response_data->data->narration,
                        "status"=>$response_data->data->status,
                        "payment_type"=>$response_data->data->payment_type,
                        "account_id"=>$response_data->data->account_id,
                        "amount_settled"=>$response_data->data->amount_settled,
                        "first_6digits"=>$response_data->data->card->first_6digits,
                        "last_4digits"=>$response_data->data->card->last_4digits,
                        "issuer"=>$response_data->data->card->issuer,
                        "type"=>$response_data->data->card->type,
                        "token"=>$response_data->data->card->token,
                        "expiry"=>$response_data->data->card->expiry,
                        "transaction_country"=>$response_data->data->card->country,
                        "don_id"=>$response_data->data->id,
                        "payment_date"=>$response_data->data->created_at,
                        "customer_id"=>$response_data->data->customer->id
                    ];
                    $check = static::findOne(['tx_ref'=> $response_data->data->tx_ref]);
                    if ($check) {
                        if (self::update($saveable_data)) {
                            $response = ["statuscode" => 0, "status" => "Thank you for your donation"];
                        } else {
                            $response = ["statuscode" =>-1, "status" => "Unable to complete your donation at the moment"];
                        }
                    } else {                        
                        if (self::save($saveable_data)) {
                            $response = ["statuscode" => 0, "status" => "Thank you for your donation"];
                        } else {
                            $response = ["statuscode" =>-1, "status" => "Unable to complete your donation at the moment"];
                        }
                    }
                } else {
                    $response = ["statuscode" =>-1, "status" => "error", "data" =>$response_data];
                }
            } else {
                $response = ["statuscode" =>-1, "status" => "error", "data" =>$data];
            }
        // } else {
        //     exit(header("HTTP/1.1 500 Internal Server Error <br> You are not allowed to access this page"));
        // }
        return $response;
    }


    /**
     * Handle CURL Operation Method.
     * Handles CURL Operation and returns response based on given parameters
     * 
     * @param array $post_data
     * @param string $post_url
     * 
     * @return array
     */
    private static function handleCURL(Array $post_data, String $post_url)
    {
        $curl = curl_init();
        // die(var_dump($_SERVER, $_SERVER["SECRET_KEY"]));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        
        curl_setopt($curl, CURLOPT_URL, $post_url);
        
        curl_setopt($curl, CURLOPT_POST, 1);
        
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post_data));

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 200);

        curl_setopt($curl, CURLOPT_TIMEOUT, 200);

        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer ". $_SERVER["SECRET_KEY"],
            "Content-Type: application/json",
            "Cache-Control: no-cache"
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }    
}