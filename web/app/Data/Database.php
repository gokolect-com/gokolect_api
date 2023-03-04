<?php

namespace Gokolect\Data;
error_reporting(E_ALL);
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

use Exception;
use PDO;
use PDOException;

/**
 * Database connection class
 * 
 * @category Micro_Service_Application
 * @package  Gokolect_API_Service
 * @author   Tamunobarasinipiri Joseph Samuel <josephsamuelw1@gmail.com>
 * @license  MIT license 
 * @link     https://gokolect.test
 */
abstract class Database
{
    private static $_conn = null;
    /**
     * Connection Class constructor.
     */
    function __construct()
    {
        // self::$_DB_HOST = getenv('DB_HOST'); 
        // self::$_DB_USER = getenv('DB_USERNAME'); 
        // self::$_DB_KEY = getenv('DB_PASSWORD'); 
        // self::$_DB_NAME = getenv('DB_DATABASE'); 
    }

    /**
     * Create database connection
     * 
     * @return object
     * @throws Exception
     */
    public static function getConnection()
    {
        
        $conn = null; 
        $_DB_HOST = getenv('DB_HOST'); 
        $_DB_USER = getenv('DB_USERNAME'); 
        $_DB_KEY = getenv('DB_PASSWORD'); 

        self::_createDataBaseUser();   
        
        $DB_DATABASE = ltrim(self::_checkDatabase());
        
        $OPTIONS = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true,
        ];

        try {            
            $conn = new PDO("mysql:host=".$_DB_HOST.";dbname=".$DB_DATABASE, $_DB_USER, $_DB_KEY, $OPTIONS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new PDOException('Connection failed! ' . $e->getMessage(), 1);
        }        
        return $conn;
    }

    /**
     * Mysqli database connection method.
     * An optional method to connect to the database using mysqli connection.
     * 
     * @return mix
     */
    public static function dbConnect()
    {
        $host = getenv('DB_HOST');
        $user = getenv('DB_USERNAME');
        $pwd = getenv('DB_PASSWORD');
        $dbase = getenv('DB_DATABASE');
        $response = null;

        $mysqli = new \mysqli(
            $host, 
            $user, 
            $pwd, 
            $dbase
        );
        
        $mysqli->query("SET time_zone = '+01:00'");

        $success=false;

        if ($mysqli->connect_errno) {
            $response = "Connection failed:... ". $mysqli->connect_error;
            @$mysqli = new \mysqli($host, $user, $pwd);

            if ($mysqli->connect_errno) {
                $response = "Connection failed: ". $mysqli->connect_error;
            } else {
                if ($mysqli->query("CREATE DATABASE IF NOT EXISTS {$dbase}") === true) {
                    $mysqli->select_db($dbase);
                    
                    if (!$mysqli->query("CREATE SCHEMA {$dbase}")) {
                        $response = "Error: ". $mysqli->error;
                    } else {
                        $mysqli->query("ALTER SCHEMA {$dbase} OWNER TO $user");
                    }
                    $response=true;
                } else {
                    $response = "Error: ". $mysqli->error;
                }
            }
        } else {
            $response = true;
        }
        
        return $response;
    }

    /**
     * Checks if the database exists, and create it if it does not exist 
     * then returns the database name for connection.
     *
     * @return string
     * @throws Exception
     */
    private static function _checkDatabase()
    {
        $conn = null;
        $_DB_HOST = getenv('DB_HOST'); 
        $_DB_USER = getenv('DB_USERNAME'); 
        $_DB_KEY = getenv('DB_PASSWORD'); 
        $_DB_NAME = getenv('DB_DATABASE');
        try {

            $conn = new PDO("mysql:host=".$_DB_HOST, $_DB_USER, $_DB_KEY);

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $res = $conn->prepare(
                "SELECT IF(
                    EXISTS 
                (
                    SELECT SCHEMA_NAME FROM 
                    INFORMATION_SCHEMA.SCHEMATA 
                    WHERE SCHEMA_NAME = ?
                ), 
                'Yes','No'
                )"
            );

            $res->execute([$_DB_NAME]);

            $row1 = $res->fetch();

            if ($row1[0] === 'No') {

                $sql = "CREATE DATABASE IF NOT EXISTS {$_DB_NAME} 
                CHARACTER SET utf8 COLLATE utf8_general_ci";

                // use exec() because no results are returned
                $result = $conn->exec($sql);

                if ($result) {

                    $res = $conn->prepare(
                        "SELECT SCHEMA_NAME 
                        FROM INFORMATION_SCHEMA.SCHEMATA 
                        WHERE SCHEMA_NAME = ?"
                    );

                    $res->execute([$_DB_NAME]);

                    $row = $res->fetch();

                    return $row[0];
                }
            } else {
                $res = $conn->prepare(
                    "SELECT SCHEMA_NAME 
                    FROM INFORMATION_SCHEMA.SCHEMATA 
                    WHERE SCHEMA_NAME = ?"
                );

                $res->execute([$_DB_NAME]);

                $row = $res->fetch();

                return $row[0];
            }
        } catch (PDOException $e) {
            throw new \Exception($e->getMessage(), 1);
        }
        $conn = null;
    }

    /**
     * Create database user with privileges
     * 
     * @return mix
     */
    private static function _createDataBaseUser()
    {
        $_DB_HOST = getenv('DB_HOST'); 
        $_DB_USER = getenv('DB_USERNAME'); 
        $_DB_KEY = getenv('DB_PASSWORD'); 
        try {
            self::$_conn = new PDO("mysql:host=".$_DB_HOST, 'root', '');
            
            // set the PDO error mode to exception
            self::$_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "CREATE USER IF NOT EXISTS '{$_DB_USER}'@'%' IDENTIFIED BY '{$_DB_KEY}'; GRANT ALL PRIVILEGES ON *.* TO '{$_DB_USER}'@'%'";

            // $sql = "CREATE USER IF NOT EXISTS 'db_gokolect_user_admin'@'%' IDENTIFIED BY 'GK_dbpwd2022'; GRANT ALL PRIVILEGES ON *.* TO 'db_gokolect_user_admin'@'%' REQUIRE NONE WITH GRANT OPTION MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0";

            // $sql = "CREATE USER 'db_gokolect_user_admin'@'%' IDENTIFIED BY 'GK_dbpwd2022'; GRANT ALL PRIVILEGES ON *.* TO 'db_gokolect_user_admin'@'%' REQUIRE NONE WITH GRANT OPTION MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0; GRANT ALL PRIVILEGES ON 'root'@'_%'.* TO db_gokolect_user_admin'@'%'";

            $response = self::$_conn->exec($sql);

        } catch (\PDOException $e) {
            $response = $e->getMessage();
        }
        return $response;
    }
}
