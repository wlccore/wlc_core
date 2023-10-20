<?php
namespace eGamings\WLC\Tests;

use eGamings\WLC\Tests\BaseCase;

class CoreBootstrapTest extends BaseCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    public function testBootstrapInit() {
        global $cfg;
        $initFile = require_once($cfg['core'] . "/inc/functions.php");
        $this->assertTrue(function_exists('_cfg'), "Check global _cfg function defined");
    }

    public function testBootstrapVersion() {
        global $cfg;
        require($cfg['core'] . "/version.php");
        $this->assertTrue(defined('WLCCORE_VERSION'), "Check wlc version is defined");
    }
}
