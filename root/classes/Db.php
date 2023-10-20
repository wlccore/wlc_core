<?php

namespace eGamings\WLC;


class Db
{
    protected static $connection = NULL;
    protected static $connClass = "\mysqli";

    public static function connect()
    {
        if (!self::$connection) {
            // Setup custom connection class
            $connClass = self::$connClass;
            @self::$connection = new $connClass(_cfg('dbHost'), _cfg('dbUser'), _cfg('dbPass'), _cfg('dbBase'), _cfg('dbPort'));
            //Adding @ to new mysqli functionality because it's always showing Warning message if not blocked in PHP
            if (self::$connection->connect_error) {
                error_log('DB Connection Error ' . self::$connection->connect_errno . ' ' . self::$connection->connect_error);
                return false;
            }

            self::$connection->query('SET NAMES "utf8"');
        }

        return self::$connection;
    }

    public static function close()
    {
        if (!self::$connection) {
            return true;
        }

        $answer = self::$connection->close();
        self::$connection = NULL;
        return $answer;
    }

    /**
     * @param string $query
     *
     * @return bool|\mysqli_result
     */
    public static function query(string $query)
    {
        if (self::$connection == NULL && !self::connect()) {
            return false;
        }

        $result = self::$connection->query($query);
        if (!$result) {
            Logger::log('DB Query Error: ' . self::$connection->error, 'error', [__FILE__,__LINE__, $query]);
            return false;
        }

        return $result;
    }

    public static function query_fatal($query)
    {
        if (self::$connection == NULL && !self::connect()) {
            return false;
        }

        if (self::query($query) === false) {
            error_log('DB Error: ' . $query);
            die('DB Error');
        }
    }

    public static function multi_query($query)
    {
        if (self::$connection == NULL && !self::connect()) {
            return false;
        }

        return self::$connection->multi_query($query);
    }

    public static function store_result()
    {
        if (self::$connection == NULL) {
            return false;
        }

        return self::$connection->store_result();
    }

    public static function more_results()
    {
        if (self::$connection == NULL) {
            return false;
        }

        return self::$connection->more_results();
    }

    public static function next_result()
    {
        if (self::$connection == NULL) {
            return false;
        }

        return self::$connection->next_result();
    }

    public static function affectedRows()
    {
        if (self::$connection == NULL) {
            return false;
        }

        return self::$connection->affected_rows;
    }

    public static function fetchRow(string $query)
    {
        $result = self::query($query);
        if (!is_object($result)) {
            return false;
        }

        $row = $result->fetch_object();
        $result->free();

        return empty($row) ? false : $row;
    }

    public static function fetchRows($query)
    {
        $array = [];
        $result = self::query($query);
        if (!is_object($result)) {
            return false;
        }

        while ($row = $result->fetch_object()) {
            $array[] = $row;
        }
        $result->free();

        return empty($array) ? false : (object) $array;
    }

    public static function lastId()
    {
        if (self::$connection == NULL) {
            return false;
        }
        
        return self::$connection->insert_id;
    }

    public static function error()
    {
        if (self::$connection == NULL) {
            return false;
        }

        if (self::$connection->errno) {
            return self::$connection->errno . ': ' . self::$connection->error;
        }

        return false;
    }

    public static function escape($variable)
    {
        if (self::$connection == NULL && !self::connect()) {
            return false;
        }

        if (!is_array($variable)) {
            $string = trim($variable);

            return self::$connection->real_escape_string($string);
        } else {
            $array = array();
            foreach ($variable as $f) {
                $f = trim($f);

                $array[] = self::$connection->real_escape_string($f);
            }

            return $array;
        }
    }
}
