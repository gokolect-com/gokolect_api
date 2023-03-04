<?php

namespace Gokolect\Api;
/**
 * Gokolect Payment class holds all 
 * attributes of payment functionalities
 * 
 * PHP Version 8.1.3
 * 
 * @category Web_Application
 * @package  Gokolect_API
 * @author   Tamunobarasinipiri Samuel Joseph <joseph.samuel@cinfores.com>
 * @license  MIT License
 * @link     https://gokolect.com
 */

use Gokolect\Api\Dal\PaymentDal;
use Gokolect\GUMP;

require_once __DIR__."/JWTConfig.php";

/**
 * PollJota Admin class holds all attributes of the Administration functionalities
 * Admin Class
 * 
 * PHP Version 8.1.3
 * 
 * @category Web_Application
 * @package  Gokolect
 * @author   Tamunobarasinipiri Samuel Joseph <joseph.samuel@cinfores.com>
 * @license  MIT License
 * @link     https://Gokolect.com
 */
class PaymentBll
{

    private static $_input_data;
    private static $_input_file;

    /**
     * Class constructor
     * 
     * @param array $data  An array of user input data
     * @param array $files user uploaded file.
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
     * Get current user method
     * Gets details of current user using the system
     * 
     * @return array
     */
    public function generatePayRef()
    {
        $items = new PaymentDal();
        return $items->generateRef();
    }

    /**
     * Get gift items method.
     * Gets details of all gift items available.
     * 
     * @return array
     */
    public function processPayment()
    {
        $items = new PaymentDal();
        return $items->ProcessPayments();
    }
    
    /**
     * Process payment response Method.
     * Process the response from initiated payment processing.
     * 
     * @return mix
     */
    public function returnResponsePayment()
    {
        $items = new PaymentDal();
        return $items->processPaymentResponse();
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
            'item_code' => 'trim|sanitize_string'
        );
        $mypost = $validator->filter($mypost, $filters);
        
        $rules = array(
            'item_code' => 'required|min_len,3|max_len,200'
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
}