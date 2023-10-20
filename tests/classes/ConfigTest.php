<?php
namespace eGamings\WLC\Tests;

use eGamings\WLC\Core;
use eGamings\WLC\System;
use eGamings\WLC\Config;
use eGamings\WLC\Tests\BaseCase;

class ConfigTest extends BaseCase
{
    public function setUp(): void {
        $reflectionCore = new \ReflectionClass(Core::class);
        $reflectionProperty = $reflectionCore->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(new CoreMock());
    }

    public function tearDown(): void {
        $reflectionCore = new \ReflectionClass(Core::class);
        $reflectionProperty = $reflectionCore->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(null);
    }

    public function testFetchSiteConfig(): void {
        global $cfg;

        $backUpExcludeCurrencies = isset($cfg['exclude_currencies']) ? $cfg['exclude_currencies'] : [];

        $cfg['exclude_currencies'] = ['INR'];

        $mock = $this->getMockBuilder(System::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        $reflectionSystem = new \ReflectionClass(System::class);
        $reflectionProperty = $reflectionSystem->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $mock->expects($this->exactly(1))->method('getApiTID')->willReturn('test_123');
        $mock->expects($this->exactly(1))->method('runFundistAPI')->willReturn('1,{"currencies": {}, "registrationCurrencies": {"1":{"ID":"1","Name":"EUR","ExRate":"1.00000000","Alias":"EUR"},"104":{"ID":"104","Name":"INR","ExRate":"88.43296781","Alias":"INR"}}}');

        $result = Config::fetchSiteConfig(true);//var_dump($result);exit;
        $reflectionProperty->setValue(null);
        $cfg['exclude_currencies'] = $backUpExcludeCurrencies;
        $this->assertTrue($result, 'Currencies update must true');
    }

    public function testFetchCountryList() {
        $mock = $this->getMockBuilder(System::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        $reflectionSystem = new \ReflectionClass(System::class);
        $reflectionProperty = $reflectionSystem->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $mock->expects($this->exactly(1))->method('getApiTID')->willReturn('test_123');
        $mock->expects($this->exactly(1))->method('runFundistAPI')->willReturn('0,DummyFalse');

        $result = Config::fetchSiteConfig(true);
        $reflectionProperty->setValue(null);
        $this->assertFalse($result, 'Country update must fail');
    }

    public function testGetSiteConfig() {
        $config = Config::getSiteConfig();
        $this->assertTrue(is_array($config), 'Config must be an array');
        $this->assertTrue(!empty($config), 'Config must be not empty');
    }


    public function testCheckFileTime () {
        $mock = $this->getMockBuilder(System::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        $reflectionSystem = new \ReflectionClass(System::class);
        $reflectionProperty = $reflectionSystem->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $mock->expects($this->exactly(1))->method('getApiTID')->willReturn('test_123');
        $mock->expects($this->exactly(1))->method('runFundistAPI')->willReturn('0,DummyFalse');

        $file = _cfg('cache') . DIRECTORY_SEPARATOR . Config::$_siteConfigFile;
        $_SERVER['TEST_RUN'] = false; 
        $result = Config::checkFileTime($file, 1);
        $_SERVER['TEST_RUN'] = true;
        $this->assertFalse($result);
        $reflectionProperty->setValue(null);
    }

    public function testLoadDir() {
        $testDataDir = Config::get('testData');
        $this->assertTrue(!empty($testDataDir));
        $_ENV['WLC_CONFIG_OVERRIDE'] = implode(DIRECTORY_SEPARATOR, [$testDataDir, 'config']);

        $result = Config::load();
        $this->assertTrue($result);
        $cfgHost = Config::get('test.db.host');
        $this->assertTrue(is_string($cfgHost));
        $cfgHost = Config::get('testDbHost');
        $this->assertTrue(is_string($cfgHost));
        unset($_ENV['WLC_CONFIG_OVERRIDE']);
    }

    public function testLoadFile() {
        $testDataDir = Config::get('testData');
        $this->assertTrue(!empty($testDataDir));
        $_ENV['WLC_CONFIG_OVERRIDE'] = implode(DIRECTORY_SEPARATOR, [$testDataDir, 'config', 'test.config.yaml']);

        $result = Config::load();
        $this->assertTrue($result);
        $cfgHost = Config::get('test.db.host');
        $this->assertTrue(is_string($cfgHost));
        $cfgHost = Config::get('testDbHost');
        $this->assertTrue(is_string($cfgHost));
        unset($_ENV['WLC_CONFIG_OVERRIDE']);
    }

    public function testLoadFileServer() {
        $testDataDir = Config::get('testData');
        $this->assertTrue(!empty($testDataDir));
        $_SERVER['WLC_CONFIG_OVERRIDE'] = implode(DIRECTORY_SEPARATOR, [$testDataDir, 'config', 'test.config.yaml']);

        $result = Config::load();
        $this->assertTrue($result);
        $cfgHost = Config::get('test.db.host');
        $this->assertTrue(is_string($cfgHost));
        $cfgHost = Config::get('testDbHost');
        $this->assertTrue(is_string($cfgHost));
        unset($_SERVER['WLC_CONFIG_OVERRIDE']);
    }

    public function testGet() {
        global $cfg;

        $cfgName = 'ConfigTestValue';
        $cfg[$cfgName] = true;
        $result = Config::get($cfgName);
        $this->assertTrue($result, 'Config get value must be true');
    }

    public function testGetArrayName() {
        global $cfg;

        $cfgId = ['test', 'name'];
        $cfgName = 'test.name';
        $cfg[$cfgName] = '123';
        $result = Config::get($cfgId);
        $this->assertEquals($cfg[$cfgName], $result);
    }

    public function testGetNotExistent() {
        $cfgName = 'ConfigTestValueNotExists';
        $result = Config::get($cfgName);
        $this->assertNull($result, 'Not existent value must return null');
    }

    public function testSet() {
        global $cfg;

        $cfgName = 'ConfigTestValue';
        $cfgValue = 'testSet';
        $result = Config::set($cfgName, $cfgValue);
        $this->assertEquals($cfgValue, $result);
        $this->assertEquals($cfg[$cfgName], $cfgValue);
    }

    public function testSetValueNull() {
        global $cfg;

        $cfgName = 'ConfigTestValue';
        $cfgValue = null;
        $result = Config::set($cfgName, $cfgValue);
        $this->assertNull($result);
        $this->assertTrue(!isset($cfg[$cfgName]));
    }

    public function testSetArrayName() {
        global $cfg;

        $cfgIds = ['test', 'name'];
        $cfgName = Config::getName($cfgIds);
        $cfgValue = 'testSet';
        $result = Config::set($cfgName, $cfgValue);
        $this->assertEquals($cfgValue, $result);
        $this->assertEquals($cfg[$cfgName], $cfgValue);
    }
    
    public function testPropertiesDefined() {
        global $cfg;
        $env = $cfg['env'];
        
        require_once $siteconfig_inc = $cfg['inc']."/siteconfig-qa.php";
        $cfg['env'] = $env;
        $this->assertTrue(isset($cfg['errors_email_url']));
        
        require_once $siteconfig_inc = $cfg['inc'].'/siteconfig-dev.php';
        $cfg['env'] = $env;
        $this->assertTrue(isset($cfg['errors_email_url']));
        
        require_once $siteconfig_inc = $cfg['inc'].'/siteconfig-test.php';
        $cfg['env'] = $env;
        $this->assertTrue(isset($cfg['errors_email_url']));
        
        require_once $siteconfig_inc = $cfg['inc'].'/siteconfig-prod.php';
        $cfg['env'] = $env;
        $this->assertTrue(isset($cfg['errors_email_url']));
    }
}
