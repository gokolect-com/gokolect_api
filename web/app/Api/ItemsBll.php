<?php

namespace Gokolect\Api;
/**
 * PollJota Administration class holds all 
 * attributes of administration functionalities
 * 
 * PHP Version 8.1.3
 * 
 * @category Web_Application
 * @package  PollJotaAPI
 * @author   Tamunobarasinipiri Samuel Joseph <joseph.samuel@cinfores.com>
 * @license  MIT License
 * @link     https://PollJota.idea.cinfores.com
 */

use Gokolect\Api\Dal\ItemsDal;
use Gokolect\GUMP;

require_once __DIR__."/JWTConfig.php";

/**
 * PollJota Admin class holds all attributes of the Administration functionalities
 * Admin Class
 * 
 * PHP Version 8.1.3
 * 
 * @category Web_Application
 * @package  PollJotaAPI
 * @author   Tamunobarasinipiri Samuel Joseph <joseph.samuel@cinfores.com>
 * @license  MIT License
 * @link     https://PollJota.idea.cinfores.com
 */
class ItemsBll
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
    public function getAllCategories()
    {
        $items = new ItemsDal();
        return $items->getCategories();
    }

    /**
     * Get gift items method.
     * Gets details of all gift items available.
     * 
     * @return array
     */
    public function getAllGiftItemsForCollection()
    {
        $items = new ItemsDal();
        return $items->getGiftItemsForCollection();
    }

    /**
     * Get gift items method.
     * Gets details of all gift items available based on given giver id.
     * 
     * @return array
     */
    public function getAllGiftItemsForCollectionById()
    {
        if (!isset(self::$_input_data['giver_id']) || empty(self::$_input_data['giver_id'])) {
            $response = ['statuscode' => -1, "status" => "The Giver's id is required"];
        } else {
            $items = new ItemsDal(self::$_input_data);
            $response = $items->getGiftItemByGiverId();
        }
        return $response;
    }

    /**
     * Get request persons method.
     * Gets details of persons that requested an item.
     * 
     * @return array
     */
    public function getRequestPersonsByItemCode()
    {
        $items = new ItemsDal(self::$_input_data);
        return $items->getRequestPersonsByItemCode();
    }

    /**
     * Give out item method.
     * Manually Gives gift item to receiver of choice by the giver.
     * 
     * @return array
     */
    public function giveOutGiftItemToReceiver()
    {
        $items = new ItemsDal(self::$_input_data);
        return $items->giveOutItemToReceiver();
    }

    /**
     * Collect gift items method.
     * Adds an item collection to a request queue.
     * 
     * @return array
     */
    public function collectThisItem()
    {
        $validated = $this->_validateInputData((array) self::$_input_data);
        if ($validated['error']) {
            $response = ['statuscode' => -1, 'status' => $validated['errormsg']];
        } else {
            $data = $validated['my_post'];
            $items = new ItemsDal($data);
            $response = $items->collectAnItem();
        }
        
        return $response;
    }

    /**
     * Get request queue method.
     * Gets all requests in a queue for an item.
     * 
     * @return array
     */
    public function getRequestListById()
    {
        $validated = $this->_validateInputData((array) self::$_input_data);
        if ($validated['error']) {
            $response = ['statuscode' => -1, 'status' => $validated['errormsg']];
        } else {
            $data = $validated['my_post'];
            $items = new ItemsDal($data);
            $response = $items->getRequestQueueById();
        }
        return $response;
    }
    
    /**
     * Generate General token method.
     * Requests for a general purpose token.
     * 
     * @return array
     */
    public function requestSetToken()
    {
        $items = new ItemsDal();
        return $items->setGeneralToken();
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