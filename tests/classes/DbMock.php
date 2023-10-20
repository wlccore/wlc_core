<?php
namespace eGamings\WLC\Tests;

use eGamings\WLC\Db;
use eGamings\WLC\Provider\IDb;

class DbMock extends Db implements IDb {
    private static $queryResult = [];
    private static $affectedRows = 0;

    public static function getConnClass() {
        return self::$connClass;
    }

    public static function fetchRow(string $query) {
        if (is_array(self::$queryResult)) {
            return array_shift(self::$queryResult);
        }

        return self::$queryResult;
    }

    public static function query(string $query)
    {
        return self::fetchRow($query);
    }

    public static function fetchRows($query)
    {
        return self::fetchRow($query);
    }

    public static function escape($variable)
    {
        return $variable;
    }

    public static function setConnClass($class) {
        self::$connClass = $class;
    }

    public static function getConnection() {
        return self::$connection;
    }

    public static function setConnection($connection) {
        self::$connection = $connection;
    }

    /**
     * @return mixed
     */
    public static function getQueryResult()
    {
        return self::$queryResult;
    }

    /**
     * @param mixed $queryResult
     */
    public static function setQueryResult($queryResult): void
    {
        if (!is_array(self::$queryResult)) {
            self::$queryResult = [];
        }

        self::$queryResult[] = $queryResult;
    }

    // Alias for the rows getter
    public static function affectedRows() {
        return self::getAffectedRows();
    }

    /**
     * @return int
     */
    public static function getAffectedRows(): int
    {
        return self::$affectedRows;
    }

    /**
     * @param int $affectedRows
     */
    public static function setAffectedRows(int $affectedRows): void
    {
        self::$affectedRows = $affectedRows;
    }
}
