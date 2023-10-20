<?php

namespace eGamings\WLC\Tests;

use eGamings\WLC\EmailQueue;

/**
 * Class EmailQueueMock
 * @package eGamings\WLC\Tests
 *
 * @method static void setSendResult(bool $value)
 * @method static void setThrowException(bool $value)
 */
class EmailQueueMock extends EmailQueue
{
    /**
     * @var bool
     */
    private static $SendResult = true;

    /**
     * @var bool
     */
    private static $ThrowException = false;

    protected static function send($email, $subject, $message, $smtp = [])
    {

        if (self::$ThrowException) {
            throw new \Swift_SwiftException('UnitTest Swift Exception');
        }

        return self::$SendResult;
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
