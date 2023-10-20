<?php

namespace eGamings\WLC\Tests;

use eGamings\WLC\Logger;

class LoggerTest extends BaseCase
{
    public function testGetInstanceName()
    {
        $_ENV['INSTANCE_NAME'] = 'env_instance_name';
        $this->assertEquals(Logger::getInstanceName(), 'env_instance_name', 'Should return from $_ENV');

        unset($_ENV['INSTANCE_NAME']);
        $_SERVER['INSTANCE_NAME'] = 'server_instance_name';
        $this->assertEquals(Logger::getInstanceName(), 'server_instance_name', 'Should return from $_SERVER');

        unset($_SERVER['INSTANCE_NAME']);
        $this->assertIsString(Logger::getInstanceName(), 'Should be a string');
    }
}
