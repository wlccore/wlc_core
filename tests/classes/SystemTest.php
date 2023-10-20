<?php
namespace eGamings\WLC\Tests;

use eGamings\WLC\System;
use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Core;
use eGamings\WLC\Tests\CoreMock;

class SystemTest extends BaseCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $_SERVER['MY_TEST_RUN'] = true;
    }

    public static function tearDownAfterClass(): void
    {
        unset($_SERVER['MY_TEST_RUN']);
    }

    public function setUp(): void {
        $reflectionCore = new \ReflectionClass(Core::class);
        $reflectionProperty = $reflectionCore->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(new CoreMock());

        DbMock::setConnection(null);
        DbMock::setConnClass('eGamings\WLC\Tests\DbConnectionMock');
        DbConnectionMock::$hasConnectError = false;
    }

    public function tearDown(): void {
        $reflectionCore = new \ReflectionClass(Core::class);
        $reflectionProperty = $reflectionCore->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(null);
        DbMock::setConnection(null);
        unset($_SERVER['REQUEST_URI']);
        foreach ($_GET as $key => $val) {
            unset($_GET[$key]);
        }
    }

    public function testCheckGeoDataWithEnabledGeoCheck() {
        $object = System::getInstance();
        $methodName = "checkGetData";
        $parameters = [];

        _cfg('enableGeoLanguage', true);
        _cfg('userCountry', 'de');
        unset($_GET['language']);

        $result = $this->invokeMethod($object, 'checkGetData', []);
        $this->assertEquals(_cfg('language'), 'de');
    }

    public function testGetTid() {
        $conn = $this->getMockBuilder(DbConnectionMock::class)->setMethods(['query'])
            ->disableOriginalConstructor()->getMock();
        DbMock::setConnection($conn);

        $queryResult = true;
        $conn->expects($this->exactly(1))->method('query')->willReturn($queryResult, $queryResult);
        $conn->insert_id = 1;

        $system = System::getInstance();
        $env = _cfg('env');
        _cfg('env', 'test');
        _cfg('forceApiDBLog', 1);
        $_SESSION['user']['id'] = 1;
        $result = $system->getApiTid('test-api-url');
        $this->assertEquals('test_1', $result);
        unset($_SESSION['user']['id']);
        _cfg('env', $env);
    }

    public function testGetTidFailure() {
        $conn = $this->getMockBuilder(DbConnectionMock::class)->setMethods(['query'])
                    ->disableOriginalConstructor()->getMock();
        DbMock::setConnection($conn);

        $queryResult = false;
        $conn->expects($this->exactly(1))->method('query')->willReturn($queryResult);
        $conn->insert_id = null;

        $system = System::getInstance();
        $env = _cfg('env');
        _cfg('env', 'test');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(_('Transaction value generation failed'));

        $result = $system->getApiTid('test-api-url');
        _cfg('env', $env);
    }

    public function testGetTidUUID() {
        $conn = $this->getMockBuilder(DbConnectionMock::class)->setMethods(['query'])
                    ->disableOriginalConstructor()->getMock();
        DbMock::setConnection($conn);

        $queryResult = true;
        $conn->expects($this->exactly(1))->method('query')->willReturn($queryResult);
        $conn->insert_id = 1;

        $env = _cfg('env');
        _cfg('env', 'prod');
        _cfg('forceApiDBLog', 1);
        $system = System::getInstance();
        $uuid = _cfg('fundistTidUUID');
        _cfg('fundistTidUUID', true);
        _cfg('fundistTidPrefix', '');
        $result = $system->getApiTid('test-api-url');
        $this->assertEquals(strlen($result), 36);
        _cfg('fundistTidUUID', $uuid);
        _cfg('env', $env);
    }

    public function testCheckGeoDataWithEnabledBrowserCheck() {
        $conn = $this->getMockBuilder(DbConnectionMock::class)->setMethods(['query'])
                    ->disableOriginalConstructor()->getMock();
        DbMock::setConnection($conn);

        $queryResult = true;
        $conn->expects($this->any())->method('query')->willReturn($queryResult);
        $conn->insert_id = 1;
        $object = System::getInstance();

        _cfg('enableGeoLanguage', false);
        _cfg('enableBrowserLanguage', true);
        _cfg('userCountry', 'ru');
        unset($_GET['language']);
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru,en;q=0.9';

        $result = $this->invokeMethod($object, 'checkGetData', []);
        $this->assertEquals(_cfg('language'), 'ru');

        $_GET['language'] = 'run';
        $_GET['route'] = 'api/siteconfig';

        $r = $this->invokeMethod($object, 'checkGetData', []);
        $this->assertTrue($r);
    }

    public function testCheckBGCResendNoUrl() {
        $conn = $this->getMockBuilder(DbConnectionMock::class)->setMethods(['query'])
            ->disableOriginalConstructor()->getMock();
        DbMock::setConnection($conn);

        $queryResult = true;
        $conn->expects($this->any())->method('query')->willReturn($queryResult);
        $conn->insert_id = 1;
        $object = System::getInstance();

        $_GET['language'] = 'run';
        $_GET['route'] = 'api/resendbgcdata';

        $r = $this->invokeMethod($object, 'checkGetData', []);
        $this->assertTrue(!empty($r));
    }

    public function testCheckBGCResend() {
        $conn = $this->getMockBuilder(DbConnectionMock::class)->setMethods(['query'])
            ->disableOriginalConstructor()->getMock();
        DbMock::setConnection($conn);

        $queryResult = true;
        $conn->expects($this->any())->method('query')->willReturn($queryResult);
        $conn->insert_id = 1;
        $object = System::getInstance();

        $_GET['language'] = 'run';
        $_GET['route'] = 'api/resendbgcdata';
        _cfg('bgcUrl', 'test/test');

        $r = $this->invokeMethod($object, 'checkGetData', []);
        $this->assertTrue(!empty($r));
    }

    public function testStartGetText() {
        $object = System::getInstance();
        $result = $this->invokeMethod($object, 'startGetText', []);
        $this->assertTrue($result, "Check translation loading");
    }

    public function testGetInstance() {
        $reflectionSystem = new \ReflectionClass(System::class);
        $reflectionProperty = $reflectionSystem->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(null);

        $system1 = System::getInstance();
        $system2 = System::getInstance();
        $this->assertEquals($system1, $system2, 'Check get instance returns equal objects');
    }

    public function testIsCountryForbidden() {
        _cfg('exclude_countries', ['lv']);
        _cfg('whitelist_ip', []);

        $result = System::isCountryForbidden('lv', '1.2.3.4');
        $this->assertTrue($result, 'Check that country is forbidden');
    }

    public function testIsCountryForbiddenWhitelisted() {
        _cfg('exclude_countries', ['lv']);
        _cfg('whitelist_ip', ['1.2.3.4']);

        $result = System::isCountryForbidden('lv', '1.2.3.4');
        $this->assertFalse($result, 'Check that country is not forbidden for whitelisted ip');
    }

    public function testIsCountryForbiddenWhitelistedServer() {
        _cfg('exclude_countries', ['lv']);
        _cfg('whitelist_ip', []);
        $_SERVER['WLC_WHITELIST_IP'] = 1;

        $result = System::isCountryForbidden('lv', '1.2.3.4');
        $this->assertFalse($result, 'Check that country is not forbidden for whitelisted server ip');
        unset($_SERVER['WLC_WHITELIST_IP']);
    }

    public function testRunFundistApi() {
        $conn = $this->getMockBuilder(DbConnectionMock::class)->setMethods(['query'])
                    ->disableOriginalConstructor()->getMock();
        DbMock::setConnection($conn);

        $queryResult = true;
        // Insert into api_request, api_logs, update api_logs
        $conn->expects($this->exactly(2))->method('query')->willReturn($queryResult, true, true);
        $conn->insert_id = 1;

        $system = System::getInstance();
        $env = _cfg('env');
        _cfg('env', 'test');
        $apiUrl = 'test-api-url';
        $tidResult = $system->getApiTid($apiUrl);
        $this->assertEquals($tidResult, 'test_1');
        $apiResult = $system->runFundistApi($apiUrl);
        $this->assertEquals($apiResult, '1,success');
        _cfg('env', $env);
    }

    public function testRunPaycryptosApi() {
        $system = System::getInstance();
        $env = _cfg('env');
        _cfg('env', 'test');
        $apiUrl = 'test-api-url';
        $apiResult = $system->runPaycryptosAPI($apiUrl, false);
        $this->assertEquals($apiResult, '1,success');
        _cfg('env', $env);
    }

    public function testcheckGetDataSortingClearcache() {
        $object = System::getInstance();
        $_SERVER['REQUEST_URI'] = '/run/api/games/sorting/clearcache';
        _cfg('enableGeoLanguage', false);
        _cfg('enableBrowserLanguage', true);
        _cfg('userCountry', 'ru');
        $_GET['language'] = 'run';
        $_GET['route'] = 'api/games/sorting/clearcache';
        $result = $this->invokeMethod($object, 'checkGetData', []);
        $this->assertTrue($result);
    }

    public function testcheckGetDataSortsClearcache() {
        $object = System::getInstance();
        $_SERVER['REQUEST_URI'] = '/run/api/games/sorts/clearcache';
        _cfg('enableGeoLanguage', false);
        _cfg('enableBrowserLanguage', true);
        _cfg('userCountry', 'ru');
        $_GET['language'] = 'run';
        $_GET['route'] = 'api/games/sorts/clearcache';
        $result = $this->invokeMethod($object, 'checkGetData', []);
        $this->assertTrue($result);
    }

    public function testCheckGetDataTemporaryLocksUpdate()
    {
        $conn = $this->getMockBuilder(DbConnectionMock::class)->setMethods(['query'])
            ->disableOriginalConstructor()->getMock();
        DbMock::setConnection($conn);

        $queryResult = true;
        $conn->expects($this->any())->method('query')->willReturn($queryResult);
        $conn->insert_id = 1;
        $object = System::getInstance();

        $_GET['language'] = 'run';
        $_GET['route'] = 'api/temporarylocksupdate';

        $r = $this->invokeMethod($object, 'checkGetData', []);
        $this->assertTrue($r);

    }

    public function testCreateSmsQueue() : void
    {
        $conn = $this->getMockBuilder(DbConnectionMock::class)->setMethods(['query'])
            ->disableOriginalConstructor()->getMock();
        DbMock::setConnection($conn);

        $queryResult = true;
        $conn->expects($this->any())->method('query')->willReturn($queryResult);
        $conn->insert_id = 1;

        $object = System::getInstance();

        $_GET['language'] = 'run';
        $_GET['route'] = 'api/createsmsqueue';
        $_POST['params'] = json_encode([
            [
                'phone' => '+371-23956733',
                'message' => 'test message',
            ]
        ]);

        $result = $this->invokeMethod($object, 'checkGetData', []);
        $this->assertTrue($result);

    }

    public function testInitApiUrl(): void
    {
        $object = System::getInstance();
        $_GET['lang'] = 'en';
        $result = $this->invokeMethod($object, 'initApiUrl', []);
        $this->assertEmpty($result);
    }

    public function testcheckGetDataResetDepositsLimiter() {
        $object = System::getInstance();
        $_GET['language'] = 'run';
        $_GET['route'] = 'api/resetdepositslimiter';
        $result = $this->invokeMethod($object, 'checkGetData', []);
        $this->assertTrue($result);
    }
}
