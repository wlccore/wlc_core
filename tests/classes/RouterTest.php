<?php
namespace eGamings\WLC\Tests;

use eGamings\WLC\Router;

class RouterTest extends BaseCase {

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
    }

    public function testRouterContext() {
        $r = new Router();
        $ctx = $this->invokeMethod($r, 'getContext');

        $this->assertTrue(!empty($ctx), 'Route context not empty');
        $this->assertTrue(!empty($ctx['app']), 'Route app context not empty');
    }

    public function testRouterEmptyContext() {
        global $_context;

        $lctx = $_context;
        $_context = ['testCtxVar' => true];

        $r = new Router();
        $ctx = $this->invokeMethod($r, 'getContext');

        $this->assertTrue(!empty($ctx), 'Route context not empty');
        $this->assertTrue(!empty($ctx['app']), 'Route app context not empty');
        $this->assertTrue(!empty($ctx['testCtxVar']), 'Route initial context variable not empty');

        $_context = $lctx;
    }

//    public function testGetPage(): void {
//        global $cfg;
//
//        $cfgBackup = $cfg;
//        $ipBackup = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '';
//        $configFileName = 'siteconfig.json';
//
//        $cfg['enableForbidden'] = true;
//        $cfg['exclude_countries'] = ['usa'];
//        $cfg['userCountry'] = 'usa';
//
//        file_put_contents(__DIR__ . '/' . $configFileName, '{"siteconfig": {"useCustomLogo": "<img src=\'/some/path/to/img.png\' />"}}');
//
//        $cfg['root'] = __DIR__;
//        $_SERVER['HTTP_CF_CONNECTING_IP'] = '69.192.66.35';
//
//        $this->assertIsArray(Router::getPage(), "Should be an array with forbidden tpl");
//
//        $cfg = $cfgBackup;
//        $_SERVER['HTTP_CF_CONNECTING_IP'] = $ipBackup;
//
//        @unlink(__DIR__ . '/' . $configFileName);
//    }
}
