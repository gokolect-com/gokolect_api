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

session_start();
// session_destroy();
// Prevent direct access to this class

define("BASEPATH", 1);

use Gokolect\Api\Utility;
use Gokolect\Data\DataOps;

use Flutterwave\EventHandlers\EventHandlerInterface;
use Flutterwave\Rave;



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
    private static $_input_file;
    private $_host_url = null;
    private static $_utility = null;
    private const BAD_REQUEST = "HTTP/1.0 400 Bad Request";
    private static $_payment = null;
    private static $_prefix = "GK"; // Change this to the name of your business or app
    private static $_overrideRef = false;

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

        $this->_host_url = $_SERVER['HTTP_HOST']."/gokolect_api/";
        self::$_utility = new Utility();
        if (!is_null($_FILES)) {
            self::$_input_file = $_FILES;
        }        
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
    public function generateRef()
    {   
        static::$table = "gk_donations_tbl";
        static::$pk = "id";
        $count = self::countAll();
        $lastId = self::lastSavedId();
        if ($count <= 0) {
            $count = 0000001;
        } else {
            $count = $count + 1;
        }
        $payRef = date('dmYHis').$count;
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

            $URL = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

            $getData = $_GET;
            $postData = $_POST;
            $publicKey = $_SERVER['PUBLIC_KEY'];
            $secretKey = $_SERVER['SECRET_KEY'];
            if (isset($_POST) && isset($postData['successurl']) && isset($postData['failureurl'])) {
                $success_url = $postData['successurl'];
                $failure_url = $postData['failureurl'];
            }

            $env = $_SERVER['ENV'];

            if (isset($postData['amount'])) {
                $_SESSION['publicKey'] = $publicKey;
                $_SESSION['secretKey'] = $secretKey;
                $_SESSION['env'] = $env;
                $_SESSION['successurl'] = $success_url;
                $_SESSION['failureurl'] = $failure_url;
                $_SESSION['currency'] = $postData['currency'];
                $_SESSION['amount'] = $postData['amount'];
            }            

            // Uncomment here to enforce the useage of your own ref else a ref will be generated for you automatically
            if (isset($postData['ref'])) {
                self::$_prefix = $postData['ref'];
                self::$_overrideRef = true;
            }
            
            self::$_payment = new Rave($_SESSION['secretKey'], self::$_prefix, self::$_overrideRef);
            
            if (isset($postData['amount'])) {
                // Make payment
                // $rof = $postData['amount'] - (10.05 * 100);
                self::$_payment
                    ->eventHandler(new MyEventHandler)
                    ->setAmount($postData['amount'])
                    ->setPaymentOptions($postData['payment_options']) // value can be card, account or both
                    ->setDescription($postData['description'])
                    ->setLogo($postData['logo'])
                    ->setTitle($postData['title'])
                    ->setCountry($postData['country'])
                    ->setCurrency($postData['currency'])
                    ->setEmail($postData['email'])
                    ->setFirstname($postData['firstname'])
                    ->setLastname($postData['lastname'])
                    ->setPhoneNumber($postData['phonenumber'])
                    ->setPayButtonText($postData['pay_button_text'])
                    ->setRedirectUrl($URL)
                    // ->setMetaData(array('rof' => $rof, 'badge' => 'Awesome')) // can be called multiple times. Uncomment this to add meta datas
                    // ->setMetaData(array('metaname' => 'SomeOtherDataName', 'metavalue' => 'SomeOtherValue')) // can be called multiple times. Uncomment this to add meta datas
                    ->initialize();
                    // $response = self::$_payment;
            } else {
                if (isset($getData['cancelled'])) {
                    // Handle canceled payments
                    self::$_payment
                        ->eventHandler(new MyEventHandler)
                        ->paymentCanceled($getData['cancelled']);
                } elseif (isset($getData['tx_ref'])) {
                    // Handle completed payments
                    self::$_payment->logger->notice('Payment completed. Now requerying payment.');
                    self::$_payment
                        ->eventHandler(new MyEventHandler)
                        ->requeryTransaction($getData['transaction_id']);
                } else {
                    self::$_payment->logger->warn('Stop!!! Please pass the txref parameter!');
                    // $response = ["statuscode" => -1, "status" => 'Stop!!! Please pass the txref parameter!'];
                    echo "na so";
                }
            }
        } else {
            header("HTTP/1.1 500 Internal Server Error");
            exit(0);
        }
        // return $response;
    }

    /**
     * Process payment response
     * Process the payment response from initiated payment
     * 
     * @return array
     */
    public function processPaymentResponse()
    {
        $handle_event = new MyEventHandler;
        $data = self::$_input_data;
        if ($data['status'] === "successful") {
            $response = $handle_event->onSuccessful($data);
        }

        return $response;
    }
    
}

/**
 * This is where you set how you want to handle the transaction at different stages.
 * 
 * @category Payment
 * @package  Payment
 * @author   Flutter_wave <info@flutter.com>
 * @license  MIT License (http://www.opensource.org/licenses/)
 * @link     https://github.com/flutter/wave/blob/master
 */
class MyEventHandler implements EventHandlerInterface
{
    /**
     * This is called when the Rave class is initialized
     * 
     * @param array $initializationData The users initialization data.
     * 
     * @return array
     * */
    function onInit($initializationData) {
        // Save the transaction to your DB.
        $dataOps = new DataOps();
        if ($dataOps->getConnection()) {
            $result = dataOps::save($initializationData);
            if ($result) {
                $response = true;
            } else {
                $response = false;
            }
        }
        return $response;
    } 

    /**
     * This is called only when a transaction is successful
     * 
     * @param object $transactionData The transaction data
     * 
     * @return array
     * */
    function onSuccessful($transactionData) {
        // Get the transaction from your DB using the transaction reference (txref)
        // Check if you have previously given value for the transaction. If you have, redirect to your successpage else, continue
        // Comfirm that the transaction is successful
        // Confirm that the chargecode is 00 or 0
        // Confirm that the currency on your db transaction is equal to the returned currency
        // Confirm that the db transaction amount is equal to the returned amount
        // Update the db transaction record (includeing parameters that didn't exist before the transaction is completed. for audit purpose)
        // Give value for the transaction
        // Update the transaction to note that you have given value for the transaction
        // You can also redirect to your success page from here
        if ($transactionData->status === 'successful') {
            if ($transactionData->currency == $_SESSION['currency'] && $transactionData->amount == $_SESSION['amount']) {

                if ($_SESSION['publicKey']) {
                    header('Location: ' . getURL($_SESSION['successurl'], array('event' => 'successful')));
                    $_SESSION = array();
                    session_destroy();
                }
            } else {
                if ($_SESSION['publicKey']) {
                    header('Location: ' . getURL($_SESSION['failureurl'], array('event' => 'suspicious')));
                    $_SESSION = array();
                    session_destroy();
                }
            }
        } else {
            $this->onFailure($transactionData);
        }
    }

    /**
     * This is called only when a transaction failed
     * 
     * @param object $transactionData Returned transaction data
     * 
     * @return void
     * */
    function onFailure($transactionData) 
    {
        // Get the transaction from your DB using the transaction reference (txref)
        // Update the db transaction record (includeing parameters that didn't exist before the transaction is completed. for audit purpose)
        // You can also redirect to your failure page from here
        if ($_SESSION['publicKey']) {
            header('Location: ' . getURL($_SESSION['failureurl'], array('event' => 'failed')));
            $_SESSION = array();
            session_destroy();
        }
    }

    /**
     * This is called when a transaction is requeryed from the payment gateway
     * */
    function onRequery($transactionReference) {
        // Do something, anything!
    }

    /**
     * This is called a transaction requery returns with an error
     * */
    function onRequeryError($requeryResponse) {
        echo 'the transaction was not found';
    }

    /**
     * This is called when a transaction is canceled by the user
     * */
    function onCancel($transactionReference) {
        // Do something, anything!
        // Note: Somethings a payment can be successful, before a user clicks the cancel button so proceed with caution
        if ($_SESSION['publicKey']) {
            header('Location: ' . getURL($_SESSION['failureurl'], array('event' => 'canceled')));
            $_SESSION = array();
            session_destroy();
        }
    }

    /**
     * This is called when a transaction doesn't return with a success or a failure response. This can be a timedout transaction on the Rave server or an abandoned transaction by the customer.
     * */
    function onTimeout($transactionReference, $data) {
        // Get the transaction from your DB using the transaction reference (txref)
        // Queue it for requery. Preferably using a queue system. The requery should be about 15 minutes after.
        // Ask the customer to contact your support and you should escalate this issue to the flutterwave support team. Send this as an email and as a notification on the page. just incase the page timesout or disconnects
        if ($_SESSION['publicKey']) {
            header('Location: ' . getURL($_SESSION['failureurl'], array('event' => 'timedout')));
            $_SESSION = array();
            session_destroy();
        }
    }
}
