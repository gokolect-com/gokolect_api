<?php


DEFINE("BOOTSTRAP", "/../../../bootstrap.php");
DEFINE("ROUTE_FILE", "_route.php");
/**
 * An access route to the staffs functionalities
 * PHP Version 8.1.3
 *
 * @category Web_Application
 * @package  Gokolect_API_Service
 * @author   Tamunobarasinipiri Samuel Joseph <joseph.samuel@cinfores.com>
 * @license  MIT License
 * @link     https://gokolect.test
 */
error_reporting(E_ALL);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER["REQUEST_METHOD"];
$uriParts = explode('/', $uri);
$response = null;
$path = null;
$route_path = [];

if (is_dir(__DIR__."/")) {
    foreach (scandir(__DIR__) as $files) {
        if ($files == "." || $files == ".." || $files == "route_index.php") {
            $r[] = $files;
        } else {
            $explode = explode('_', $files);
            $path[] = reset($explode);
        }
    }
} else {
    header("HTTP/1.1 401 Unauthorized");
    exit('Unauthorized');
}
die(var_dump($requestMethod));
if ($requestMethod == "POST") {
    $data = $_POST;
    $explode = explode("_", $data['action']);
    $route = end($explode);
    
    if (is_file(__DIR__."/". $route. ROUTE_FILE)) {
        include_once __DIR__ .BOOTSTRAP;
        include_once __DIR__."/". $route. ROUTE_FILE;
        if (!is_null($route) && in_array($route, $path)) {
            $func = $route.'Router';
            $response = call_user_func($func, $data);
        } else {
            $response = ["statuscode"=>-1, "status"=>"Posted an invalid request action"];
        }
    } else {
        $response = "HTTP/1.1 401 Unauthorized hacker detected!!!";
    }
    echo json_encode($response);
} else if ($requestMethod == "GET") {
    $data = $_GET;    
    $expld = explode('_', $data['action']);
    $route = end($expld);
    if (is_file(__DIR__."/". $route. ROUTE_FILE)) {
        include_once __DIR__ .BOOTSTRAP;
        include_once __DIR__."/". $route. ROUTE_FILE;
        if (!is_null($route) && in_array($route, $path)) {
            $func = $route.'Router';
            $response = call_user_func($func, $data);
        } else {
            $response = ["statuscode"=>-1, "status"=>"Got an invalid request action"];
        }
    } else {
        $response = "HTTP/1.1 401 Unauthorized Hacker detected!";
    }
    echo json_encode($response);

} else if ($requestMethod == "FILES") {
    $file = $_FILES;
    $explode = explode('_', $data['action']);
    $route = end($explode);
    if (is_file(__DIR__. $route. ROUTE_FILE)) {
        include_once __DIR__ .BOOTSTRAP;
        include_once __DIR__. $route. ROUTE_FILE;
        if (!is_null($route) && in_array($route, $path)) {
            $func = $route.'Router';
            $response = call_user_func($func, $file);
        } else {
            $response = ["statuscode"=>-1, "status"=>"Invalid request action"];
        }
    } else {
        $response = "HTTP/1.1 401 Unauthorized";
    }
    echo json_encode($response);
} else {
    $response = ["statuscode"=>-1, "status"=>"Unauthorized request"];
    echo json_encode($response);
}