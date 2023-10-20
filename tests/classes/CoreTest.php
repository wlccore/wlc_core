<?php
namespace eGamings\WLC\Tests;

use eGamings\WLC\Core;

class CoreTest extends BaseCase {
    public function testGetInstance() {
        $core = Core::getInstance();
        $this->assertTrue(is_object($core), 'Core instance must be object');
    }

    public function testCheckAffilates(): void {
        $core = Core::getInstance();
        $_SESSION['user'] = [];
        $this->assertNull($core->checkAffilates(), 'Cannot be a null');
    }
}
