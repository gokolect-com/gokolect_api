<?php

namespace Gokolect\Data;
/**
 * Crud file
 * Pre-defines crud operations on database for simplification
 * 
 * PHP Version 8.1.3
 * 
 * @category Micro_Service_Application
 * @package  Gokolect_API_Service
 * @author   Tamunobarasinpiri Samuel Joseph <josephsamuelw1@gmail.com>
 * @license  MIT License
 * @link     https://gokolect.test
 */

error_reporting(E_ALL & E_NOTICE);

use Gokolect\Data\Database;
use Gokolect\Data\Schemas;
/**
 * Crud file
 * Pre-defines crud operations on database for simplification
 * inherits the database connection class and is a suppercalss of the DAL class
 * 
 * PHP Version 8.1.3
 * 
 * @category Micro_Service_Application
 * @package  Gokolect_API_Service
 * @author   Tamunobarasinpiri Samuel Joseph <josephsamuelw1@gmail.com>
 * @license  MIT License
 * @link     https://gokolect.test
 */
class DataOps extends Database
{
    public static $data;

    private static $_stmt;

    private static $_params;

    private static $_where;

    private static $_count;

    private static $_lastId;

    public static $pk;

    public static $table;

    private static $_column;

    public static $dbSchemas;

    /**
     * Class constructor
     */
    public function __construct()
    {
        static::$table;
        self::$pk = "";
        self::$_params = [];
        self::$_column = [];
        self::$_lastId = "";
        self::$data = array();
        self::$_stmt;
        self::$_count = "";
        self::$dbSchemas;       
    }

    /**
     * Parameters
     * 
     * @param object $data user paramters
     * 
     * @return array
     */
    private static function _param($data = null)
    {   
        if (empty($data)) {
            $data = self::$data['conditions'];
        }
        
        foreach ($data as $key => $value) {
            self::_bind(":{$key}", $value);
        }
    }

    /**
     * Bind operation
     * 
     * @param array $param user parameters
     * @param array $value parameter values
     * @param array $type  Type of operation
     * 
     * @return mix
     */
    private static function _bind($param, $value, $type = null)
    {
        if (is_null($type)) {
            switch (true) {
            case is_int($value):
                $type = \PDO::PARAM_INT;
                break;
            case is_bool($value):
                $type = \PDO::PARAM_BOOL;
                break;
            case is_null($value):
                $type = \PDO::PARAM_NULL;
                break;
            default:
                $type = \PDO::PARAM_STR;
            }
        }
        
        self::$_stmt->bindValue($param, $value, $type);
    }

    /**
     * Input fields
     * 
     * @param array $data input fields
     * 
     * @return mix
     */
    private static function _fields($data = null)
    {
        if (empty($data) && isset(self::$data['fields'])) {
            return implode(',', self::$data['fields']);
        }
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $fields[] = $key;
            }
            return implode(',', $fields);
        }
        
        return '*';
    }

    /**
     * Input values
     * 
     * @return mix
     */
    private static function _values()
    {
        foreach (self::$data as $key => $value) {

            $values[] = ":{$key}";
        }

        return implode(',', $values);
    }

    /**
     * Conditions
     * 
     * @param array $separator field separators
     * 
     * @return mix
     */
    private static function _conditions($separator)
    {
        $param = [];

        foreach (self::$data['conditions'] as $key => $value) {

            $param[] = "{$key} = :{$key}";
        }

        return implode($separator, $param);
    }

    /**
     * Where clause
     * 
     * @return mix
     */
    private static function _where()
    {
        return self::$_where = (isset(self::$data['conditions']))
            ? 'WHERE ' . self::_conditions(' AND ')
            : '';
    }

    /**
     * Update with where clause
     * 
     * @param array $data query data
     * 
     * @return mix
     */
    private static function _updateWhere($data)
    {
        self::$data['conditions'] = [static::$pk => $data[static::$pk]];

        $where = 'WHERE ' . self::_conditions('');

        unset($data[static::$pk]);

        return $where;
    }

    /**
     * Update query string 
     * 
     * @param array $data records with parameters to update.
     * 
     * @return mix
     */
    public static function updateQueryString($data)
    {
        if (array_key_exists(static::$pk, $data)) {
            unset($data[static::$pk]);
        }

        self::$data['conditions'] = $data;

        $fields = self::_conditions(',');
        return "UPDATE " . static::$table . " SET {$fields} " . self::$_where;
    }

    /**
     * Get filtered results by given parameters.
     * 
     * @param array $data   values to return.
     * @param array $params filter parameters.
     * @param array $range  filter parameters.
     * 
     * @return array
     */
    public static function findResults(array $data = null, array $params = null, ?string $range = null)
    {
        self::$data['conditions'] = $params;
        self::$data['fields'] = $data;
        if (!empty($range)) {
            $response = self::_find()->_get();
        } else {
            $response = self::_find()->_first();
        }
        return $response;
    }

    /**
     * Insert query string
     * 
     * @param array  $data  user data
     * @param string $table schema to insert data into
     * 
     * @return mix
     */
    private static function _insertQueryString($data, $table)
    {
        $fields = self::_fields(self::$data);

        $values = self::_values();

        return "INSERT INTO " . $table . " ({$fields}) VALUES ({$values})";
    }

    /**
     * Find records
     * 
     * @return mix
     */
    private static function _find()
    { 
        try {
            self::$dbSchemas = new Schemas(static::$table);

            $schema = self::getConnection()->prepare(
                static::$dbSchemas->dbschema()
            );            
            $schema->execute();

            $sql = "SELECT " . 
            self::_fields() . " FROM " . 
            static::$table . " " . self::_where();      
            
            self::$_stmt = self::getConnection()->prepare($sql);
            
            if (!empty(self::$_where)) {
                self::_param();
            }
            return new static();

        } catch (\PDOException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Get records
     * 
     * @return mix
     */
    private static function _get()
    {
        // Execute query
        // self::$_stmt->execute(self::$_params);
        self::$_stmt->execute();
        
        $total = self::$_stmt->rowCount();
      
        // Check if more than 0 record found
        $result = array();

        if ($total > 0) {

            while ($row = self::$_stmt->fetch(\PDO::FETCH_ASSOC)) {
                array_push($result, $row);
            }
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Find first record
     * 
     * @return mix
     */
    private static function _first()
    {
        // Execute query
        // self::$_stmt->execute(self::$_params);
        self::$_stmt->execute();

        $total = self::$_stmt->rowCount();

        // Check if more than 0 record found
        $result = array();

        if ($total > 0) {
            while ($row = self::$_stmt->fetch(\PDO::FETCH_ASSOC)) {
                array_push($result, $row);
            }
            return $result[0];
        } else {
            return false;
        }
    }

    /**
     * Count records
     * 
     * @return mix
     */
    private function _count()
    {
        try {
            // Execute query
            self::$_stmt->execute();
            return self::$_stmt->rowCount();
        } catch (\PDOException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Confirms if records exists
     * 
     * @param $data record parameter
     * 
     * @return mix
     */
    public static function exists(array $data)
    {
        self::$dbSchemas = new Schemas(static::$table);
        $schema = self::getConnection()->prepare(
            static::$dbSchemas->dbschema()
        );        
        
        $schema->execute();  

        self::$data['conditions'] = $data;
        self::$_count = self::_find()->_count();
        if (self::$_count > 0) {
            return true;
        }
        return false;
    }

    /**
     * Confirm records exists by record id
     * 
     * @param string $id record id
     * 
     * @return mix
     */
    public static function existsById($id)
    {
        self::$dbSchemas = new Schemas(static::$table);
        $schema = self::getConnection()->prepare(
            static::$dbSchemas->dbschema()
        );

        $schema->execute();

        if (!self::exists(['id' => $id])) {
            return false;
        }
        return true;
    }

    /**
     * Find record by id
     * 
     * @param string $id record id
     * 
     * @return mix
     */
    public static function findById($id)
    {
        self::$dbSchemas = new Schemas(static::$table);
        $schema = self::getConnection()->prepare(
            static::$dbSchemas->dbschema()
        );

        $schema->execute();

        if (!self::exists(['id' => $id])) {
            return false;
        }
        return self::findOne(['id' => $id]);
    }

    /**
     * Find only one record
     * 
     * @param string $data criteria to find 
     * 
     * @return mix
     */
    public static function findOne($data)
    {
        self::$dbSchemas = new Schemas(static::$table);
        $schema = self::getConnection()->prepare(
            static::$dbSchemas->dbschema()
        );

        $schema->execute();

        self::$data['conditions'] = $data;
        return self::_find()->_first();
    }

    /**
     * Find all record
     * 
     * @param string $data search criteria
     * 
     * @return array
     */
    public static function findAll($data = null)
    {
        
        self::$dbSchemas = new Schemas(static::$table);
        $schema = self::getConnection()->prepare(
            static::$dbSchemas->dbschema()
        );

        $schema->execute();
        
        self::$data['conditions'] = $data;

        return self::_find()->_get();
    }

    /**
     * Find and count all record
     * 
     * @param string $data search criteria
     * 
     * @return array
     */
    public static function countAll($data = null)
    {
        
        self::$dbSchemas = new Schemas(static::$table);
        $schema = self::getConnection()->prepare(
            static::$dbSchemas->dbschema()
        );

        $schema->execute();
        
        self::$data['conditions'] = $data;

        return self::_find()->_count();
    }

    /**
     * Query the schema for one record
     * 
     * @param string $sql Query string
     * 
     * @return mix
     */
    public static function queryOne($sql)
    {
        try {
            self::$dbSchemas = new Schemas(static::$table);
            $schema = self::getConnection()->prepare(
                static::$dbSchemas->dbschema()
            );

            $schema->execute();

            self::$_stmt = self::getConnection()->prepare($sql);

            self::$_stmt->execute();

            $total = self::$_stmt->rowCount();

            // Check if more than 0 record found
            $result = array();

            if ($total > 0) {

                while ($row = self::$_stmt->fetch(\PDO::FETCH_ASSOC)) {
                    array_push($result, $row);
                }

                return $result[0];
            } else {

                return null;
            }
        } catch (\PDOException $e) {

            throw new \Exception($e);
        }
    }

    /**
     * Query by parameters
     * 
     * @param string $sql   query string
     * @param array  $param query parameters
     * 
     * @return mix
     */
    public static function queryBy($sql, $param)
    {
        try {
            self::$dbSchemas = new Schemas(static::$table);
            $schema = self::getConnection()->prepare(
                static::$dbSchemas->dbschema()
            );
            
            $schema->execute();

            self::$_stmt = self::getConnection()->prepare($sql);
            self::_param($param);
            self::$_stmt->execute();

            if (self::$_stmt) {
                return true;
            } else {
                return null;
            }
        } catch (\PDOException $e) {

            throw new \Exception($e);
        }
    }

    /**
     * Query all records
     * 
     * @param string $sql Query string
     * 
     * @return mix
     */
    public static function queryAll($sql)
    {
        try {
            self::$dbSchemas = new Schemas(static::$table);
            $schema = self::getConnection()->prepare(
                static::$dbSchemas->dbschema()
            );

            $schema->execute();

            self::$_stmt = self::getConnection()->prepare($sql);

            self::$_stmt->execute();

            $total = self::$_stmt->rowCount();

            // Check if more than 0 record found
            $result = array();

            if ($total > 0) {

                while ($row = self::$_stmt->fetch(\PDO::FETCH_ASSOC)) {

                    array_push($result, $row);
                }

                return $result;
            } else {

                return null;
            }
        } catch (\PDOException $e) {

            throw new \Exception($e);
        }
    }

    /**
     * Run a query
     * 
     * @param string $sql  Query string
     * @param string $data query parameter
     * 
     * @return mix
     */
    public static function query($sql, $data = null)
    {
        try {
            self::$dbSchemas = new Schemas(static::$table);
            $schema = self::getConnection()->prepare(
                static::$dbSchemas->dbschema()
            );

            $schema->execute();
            // Prepare query statement
            self::$_stmt = self::getConnection()->prepare($sql);

            if (!empty($data)) {

                self::_param($data);
            }

            return new static();
        } catch (\PDOException $e) {

            throw new \Exception($e);
        }
    }

    /**
     * Run query
     * 
     * @return mix
     */
    public static function run()
    {
        try {

            return self::$_stmt->execute();
        } catch (\PDOException $e) {

            throw new \Exception($e);
        }
    }

    /**
     * Create records
     * 
     * @param array $data records
     * 
     * @return mix
     */
    public static function create($data)
    {

        try {
            self::$dbSchemas = new Schemas(static::$table);
            $schema = self::getConnection()->prepare(
                static::$dbSchemas->dbschema()
            );

            $schema->execute();

            $columns = array_keys($data);

            $fields = implode(',', $columns);

            $values = ':' . implode(',:', $columns);
            $sql = "INSERT INTO " . static::$table . " ($fields) VALUES ($values)";
            
            self::$_stmt = self::getConnection()->prepare($sql);
            
            self::_param($data);

            return self::$_stmt->execute();
        } catch (\PDOException $e) {
            throw new \Exception($e);
        }
    }
    /**
     * Update method
     * 
     * @param array $data An array of update parameters
     * 
     * @return mix
     */
    public static function update($data)
    {
        self::$_column = $data[static::$pk];
        
        if (!array_key_exists(static::$pk, $data)) {
            return false;
        }        
                
        try {
            $param = $data;
            self::$data['conditions'] = [static::$pk => $data[static::$pk]];
            self::$_column = array_keys($data);
            $columns = array_map(
                function ($item) {
                    if (array_key_exists(static::$pk, self::$_column)) {
                        unset(self::$_column[self::$pk]);
                    }
                    return $item . '=:' . $item;
                }, 
                self::$_column
            );

            $fields = implode(',', $columns);

            $where = 'WHERE ' . self::_conditions('');

            $sql = "UPDATE " . static::$table . " SET {$fields} {$where}";

            self::$_stmt = self::getConnection()->prepare($sql);

            self::_param($param);

            //execute the query
            if (self::$_stmt->execute()) {
                return true;
            }

            return false;
        } catch (\PDOException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Delete record
     * 
     * @param string $data delete parameter
     * 
     * @return mix
     */
    public static function delete($data)
    {
        try {

            self::$data['conditions'] = $data;            

            $sql = "DELETE FROM " . static::$table . " " . self::_where();

            self::$_stmt = self::getConnection()->prepare($sql);

            if (!empty(self::$_where)) {
                self::_param();
            }
            // execute the query
            if (self::$_stmt->execute()) {

                return true;
            }

            return false;
        } catch (\PDOException $e) {

            throw new \Exception($e);
        }
    }

    /**
     * Save records
     * 
     * @param array $data records to be saved
     * 
     * @return mix
     */
    public static function save($data)
    {       
        if (array_key_exists(static::$pk, $data)) {

            self::$_count = self::findOne([static::$pk => $data[static::$pk]]);

            self::$_lastId = $data[static::$pk];
        }
        if (!empty(self::$_count)) {

            return self::update($data);
        }
        return self::create($data);
    }

    /**
     * Last saved record id
     * 
     * @return mix
     */
    public static function lastSavedId()
    {
        $id = (int) self::getConnection()->lastInsertId();
        
        if (!empty($id)) {
            return $id;
        }
        // return ($id) ? $id : self::$_lastId;
        return false;
    }
}