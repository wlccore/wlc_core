<?php
namespace eGamings\WLC\Tests;

use eGamings\WLC\Core;

class CoreMock extends Core {
    protected static $_sessionStarted = false;
    public function __construct() {}
    public function redisCache() {
        return new RedisMock();
    }

    public function setSessionStartedFlag(bool $state): void
    {
        self::$_sessionStarted = $state;
    }

    public function sessionDestroy()
    {
        return true;
    }
}
