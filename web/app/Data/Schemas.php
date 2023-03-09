<?php


namespace Gokolect\Data;
/** 
 * Pre-defines the database schema for auto creation on request.
 * PHP Version 8.1.3
 * 
 * @category Application
 * @package  Gokolect_API_Service
 * @author   Tamunobarasinipiri Samuel Joseph <josephsamuelw1@gmail.com>
 * @license  MIT License
 * @link     https://gokolect.test
 */


/**
 * Database Schema class
 * PHP Version 8.1.3
 * 
 * @category Application
 * @package  Gokolect_API_Service
 * @author   Tamunobarasinpiri Samuel Joseph <josephsamuelw1@gmail.com>
 * @license  GIT License
 * @link     https://gokolect.test
 */
class Schemas
{
    private static $_schema;

    /**
     * Class Constructor
     * 
     * @param string $schema schema to create
     * 
     * @return mix
     */
    public function __construct(string $schema = null)
    {
        self::$_schema = $schema;
    }

    /**
     * Database schemas 
     * 
     * @return mix
     */
    public static function dbschema()
    {
        if (empty(self::$_schema)) {
            return null;
        }
        switch (strtolower(self::$_schema)) {

        case "gk_admins_tbl":
            return "
            CREATE TABLE IF NOT EXISTS gk_admins_tbl(
            id INT AUTO_INCREMENT NOT NULL,
            user_name VARCHAR(200) NULL,
            first_name VARCHAR(200) NOT NULL,
            last_name VARCHAR(200) NOT NULL,
            password VARCHAR(200) NOT NULL,
            email VARCHAR(200) NOT NULL,
            phone_no VARCHAR(20) NULL,
            role VARCHAR(200) NULL,
            position VARCHAR(200) NULL,
            status INT DEFAULT 0,
            created TIMESTAMP,
            PRIMARY KEY (id)
            )ENGINE = InnoDB DEFAULT CHARSET = utf8;
        ";
            break;

        case "countries_tbl":
            return "
            CREATE TABLE IF NOT EXISTS countries_tbl(
            country_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            region VARCHAR(200) DEFAULT NULL,
            country_name VARCHAR(200) NOT NULL,
            country_code VARCHAR(200) DEFAULT NULL,
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )ENGINE = InnoDB DEFAULT CHARSET = utf8;
        ";
            break;

        case "states_tbl":
            return "
            CREATE TABLE IF NOT EXISTS states_tbl(
            state_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            state_name VARCHAR(200) NOT NULL,
            country_id INT DEFAULT NULL,
            FOREIGN KEY fk_country_id(country_id)
            REFERENCES gk_countries_tbl(country_id),
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )ENGINE = InnoDB DEFAULT CHARSET = utf8;
        ";
            break;

        case "lga_tbl":
            return "
            CREATE TABLE IF NOT EXISTS lga_tbl(
            lga_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            lga_name VARCHAR(200) NOT NULL,
            state_id INT DEFAULT NULL,
            country_id INT DEFAULT NULL,
            FOREIGN KEY fk_state_id(state_id)
            REFERENCES states_tbl(state_id),
            FOREIGN KEY fk_country_id(country_id)
            REFERENCES countries_tbl(country_id),
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )ENGINE = InnoDB DEFAULT CHARSET = utf8;
        ";
            break;

        case "gk_users_tbl":
            return "
            CREATE TABLE IF NOT EXISTS gk_users_tbl(
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            first_name VARCHAR(256) DEFAULT NULL,
            last_name VARCHAR(256) DEFAULT NULL,
            other_name VARCHAR(256) DEFAULT NULL,
            nick_name VARCHAR(256) DEFAULT NULL,
            street VARCHAR(256) DEFAULT NULL,
            country VARCHAR(200) DEFAULT NULL,
            state VARCHAR(200) DEFAULT NULL,
            lga VARCHAR(200) DEFAULT NULL,
            city VARCHAR(200) DEFAULT NULL,
            gender VARCHAR(10) DEFAULT NULL,
            dob DATE DEFAULT NULL,
            email VARCHAR(256) DEFAULT NULL,
            mobile VARCHAR(20) DEFAULT NULL,
            status INT(11) UNSIGNED DEFAULT 0,
            is_verified INT(11) DEFAULT 0,
            is_active INT(11) DEFAULT 0,
            is_locked INT(11) DEFAULT 0,
            confirm_code INT(11) DEFAULT NULL,
            twitter VARCHAR(500) DEFAULT NULL,
            facebook VARCHAR(500) DEFAULT NULL,
            instagram VARCHAR(500) DEFAULT NULL,
            tiktok VARCHAR(500) DEFAULT NULL,
            user_name VARCHAR(2048) DEFAULT NULL,
            password VARCHAR(2048) DEFAULT NULL,
            bio LONGTEXT DEFAULT NULL,
            rating DOUBLE DEFAULT NULL,
            profile_photo VARCHAR(500) DEFAULT NULL,
            profile_path VARCHAR(500) DEFAULT NULL,
            created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            modified TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
            ) ENGINE = InnoDB DEFAULT CHARSET = utf8;
            ";
            break;

        case "gk_items_tbl":
            return "
            CREATE TABLE IF NOT EXISTS gk_items_tbl(
            item_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            item_code VARCHAR(12) NOT NULL,
            giver_id INT NOT NULL,
            item_name VARCHAR(500) NOT NULL,
            item_type VARCHAR(500) NOT NULL,
            item_desc LONGTEXT NULL,
            category INT NOT NULL,
            receiver_id INT(11) NULL,
            item_status VARCHAR(20) DEFAULT NULL,
            item_image VARCHAR(1000) DEFAULT NULL,
            item_image_path VARCHAR(1000) DEFAULT NULL,
            posted_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            collected_date TIMESTAMP NULL,
            comment TEXT DEFAULT NULL,
            modified TIMESTAMP NOT NULL,
            PRIMARY KEY(item_id),
            UNIQUE KEY item_code (item_code)
            ) ENGINE = InnoDB DEFAULT CHARSET = utf8;
            ";
            break;

        case "gk_sessions_tbl":
            return "
            CREATE TABLE IF NOT EXISTS gk_sessions_tbl(
            id INT NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(200) NOT NULL,
            user_id VARCHAR(200) NOT NULL,
            session_token VARCHAR(1000) NULL,
            user_ip VARCHAR(200),
            user_agent VARCHAR(500),
            session_time VARCHAR(200),
            session_elapsed INT NULL, 
            session_status INT NULL, 
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
            )ENGINE = InnoDB DEFAULT CHARSET = utf8;
            ";
            break;

        case "gk_locations_tbl":
            return "
            CREATE TABLE IF NOT EXISTS gk_locations_tbl(
            id INT NOT NULL AUTO_INCREMENT, 
            street VARCHAR(200) NOT NULL,
            city VARCHAR(200) NOT NULL,
            state VARCHAR(200) DEFAULT NULL,
            country VARCHAR(200) DEFAULT NULL,
            zip_code VARCHAR(200) DEFAULT NULL,
            longitude VARCHAR(200) DEFAULT NULL,
            latitude VARCHAR(200) DEFAULT NULL,           
            description LONGTEXT,
            user_id INT DEFAULT NULL,
            created TIMESTAMP DEFAULT,
            PRIMARY KEY(id)
            )ENGINE = InnoDB DEFAULT CHARSET = utf8;
            ";
            break;

        case "gk_requests_tbl":
            return "
            CREATE TABLE IF NOT EXISTS gk_requests_tbl(
                request_id INT NOT NULL AUTO_INCREMENT, 
                item_code VARCHAR(200) NOT NULL,
                request_date TIMESTAMP NOT NULL,
                request_status VARCHAR(15) NOT NULL, 
                user_id INT NOT NULL,
                request_location INT DEFAULT NULL,
                comment LONGTEXT,
                PRIMARY KEY(request_id)
            ) ENGINE = InnoDB DEFAULT CHARSET = utf8;
            ";
            break;

        case "gk_categories_tbl":
            return "
            CREATE TABLE IF NOT EXISTS gk_categories_tbl(
                id INT NOT NULL AUTO_INCREMENT,
                category_name VARCHAR(200) DEFAULT NULL,
                sub_category VARCHAR(200) DEFAULT NULL,
                description VARCHAR(200) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY(id)
            ) ENGINE = InnoDB DEFAULT CHARSET =utf8;
            ";
            break;

        case "gk_donations_tbl":
            return "
            CREATE TABLE IF NOT EXISTS gk_donations_tbl(
                id INT NOT NULL AUTO_INCREMENT,
                firstname VARCHAR(200) DEFAULT NULL,
                lastname VARCHAR(200) DEFAULT NULL,
                email VARCHAR(200) DEFAULT NULL,
                phonenumber VARCHAR(200) DEFAULT NULL,
                country VARCHAR(200) DEFAULT NULL,
                amount DECIMAL(12,2) DEFAULT NULL,
                currency VARCHAR(20) DEFAULT NULL,
                transaction_id VARCHAR(200) DEFAULT NULL,
                transaction_country VARCHAR(200) DEFAULT NULL,
                tx_ref VARCHAR(200) DEFAULT NULL,
                flw_ref VARCHAR(200) DEFAULT NULL,
                device_fingerprint VARCHAR(200) DEFAULT NULL,
                charged_amount VARCHAR(200) DEFAULT NULL,
                app_fee VARCHAR(200) DEFAULT NULL,
                merchant_fee VARCHAR(200) DEFAULT NULL,
                processor_response VARCHAR(200) DEFAULT NULL,
                auth_model VARCHAR(200) DEFAULT NULL,
                ip VARCHAR(200) DEFAULT NULL,
                narration VARCHAR(1000) DEFAULT NULL,
                status VARCHAR(200) DEFAULT NULL,
                payment_type VARCHAR(200) DEFAULT NULL,
                account_id VARCHAR(200) DEFAULT NULL,
                amount_settled VARCHAR(200) DEFAULT NULL,
                first_6digits VARCHAR(200) DEFAULT NULL,
                last_4digits VARCHAR(200) DEFAULT NULL,
                issuer VARCHAR(200) DEFAULT NULL,
                type VARCHAR(200) DEFAULT NULL,
                token VARCHAR(1000) DEFAULT NULL,
                expiry VARCHAR(200) DEFAULT NULL,
                customer_id VARCHAR(200) DEFAULT NULL,
                payment_date VARCHAR(200) DEFAULT NULL,
                comment LONGTEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                modified TIMESTAMP,
                PRIMARY KEY(id)
            ) ENGINE = InnoDB DEFAULT CHARSET =utf8;
            ";
            break;
        }
    }
}
