<?php

namespace eGamings\WLC\Tests;

/**
 * Class RedisMock
 * @package eGamings\WLC\Tests
 *
 * @method static void setSetReturn(bool $value)
 * @method static void setGetReturn(bool $value)
 * @method static void setDeleteReturn(bool $value)
 * @method static void setExistsReturn(bool $value)
 * @method static void setScanReturn(array $value)
 */
class RedisMock extends \Redis
{
    /**
     * @var bool
     */
    private static $SetReturn = true;

    /**
     * @var bool
     */
    private static $GetReturn = true;

    /**
     * @var bool
     */
    private static $DeleteReturn = true;

    /**
     * @var bool
     */
    private static $ExistsReturn = false;

    /**
     * @var array
     */
    private static $ScanReturn = [];

    public function __construct() {}

    public function exists($key, ...$keys)
    {
        return self::$ExistsReturn;
    }

    public function set($key, $value, $timeout = 0)
    {
        return self::$SetReturn;
    }

    public function get($key)
    {
        return self::$GetReturn;
    }

    public function delete($key1, ...$other_keys)
    {
        return self::$DeleteReturn;
    }

    public function scan(&$iterator, $pattern = NULL, $count = NULL)
    {
        return self::$ScanReturn;
    }

    public function redis() {
        return null;
    }

    public static function __callStatic($name, $arguments)
    {
        $type = substr($name, 0, 3);
        $key = substr($name, 3);

        if ($type === 'set') {
            self::$$key = $arguments[0];
        }
    }
}
