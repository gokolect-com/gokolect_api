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

use Gokolect\Api\ItemsBll;

error_reporting(E_ALL);

// require_once __DIR__."/../api/auth.bll.php";


/**
 * Consultant function allows requests that are for this route only
 * 
 * @param array $data input data object
 * 
 * @return json
 */
function itemsRouter(array $data = null)
{ 
    $file = null;
    if (isset($_FILES)) {
        $file = $_FILES;
    }
    $items_object = new ItemsBll($data, $file);

    switch($data['action']) {
        
    case "get_token_items":
        $response = $items_object->requestSetToken();
        break;

    case "get_categories_items":
        $response = $items_object->getAllCategories();
        break;    
        
    case "get_collectible_items":
        $response = $items_object->getAllGiftItemsForCollection();
        break;    
        
    case "get_collectible_by_id_items":
        $response = $items_object->getAllGiftItemsForCollectionById();
        break;    
        
    case "get_request_persons_items":
        $response = $items_object->getRequestPersonsByItemCode();
        break;    
        
    case "give_out_items":
        $response = $items_object->giveOutGiftItemToReceiver();
        break;    
        
    case "get_request_queue_items":
        $response = $items_object->getRequestListById();
        break;    
        
    case "collect_this_items":
        $response = $items_object->collectThisItem();
        break;    
        
    case "upload_item_users":
        // $response = $items_object->giveOutItems();
        break;    
        
    case "add_categories_users":
        // $response = $items_object->addCategories();
        break;    
        
    default:
        $response = ['statuscode'=>0, "status"=>"Invalid request action"];
        break;
    }
    return $response;
} 
