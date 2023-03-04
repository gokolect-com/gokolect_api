<?php

/**
 * Service bootstrap page.
 * 
 * PHP Version: 8.1.3
 * 
 * @category MicroService_API_Application
 * @package  Gokolect_API_Service
 * @author   Joseph Samuel Tamunobarasinipiri <josephsamuelw1@gmail.com>
 * @license  MIT http://www.mit.com/licenses/
 * @link     https://www.gokolect.com
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv as Dotenv;

$dotenv = Dotenv::createUnsafeImmutable(__DIR__);

$dotenv->load();