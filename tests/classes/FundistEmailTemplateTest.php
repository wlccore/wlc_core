<?php
namespace eGamings\WLC\Tests;

use eGamings\WLC\Logger;
use eGamings\WLC\System;
use eGamings\WLC\FundistEmailTemplate;

class FundistEmailTemplateTest extends BaseCase {
    private $systemProp = null;
    private $loggerProp = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    function setUp(): void {
        $reflectionSystemSystem = new \ReflectionClass(System::class);
        $reflectionPropertySystem = $reflectionSystemSystem->getProperty('instance');
        $reflectionPropertySystem->setAccessible(true);
        $this->systemProp = $reflectionPropertySystem;

        $reflectionSystemLogger = new \ReflectionClass(Logger::class);
        $reflectionPropertyLogger = $reflectionSystemLogger->getProperty('logger');
        $reflectionPropertyLogger->setAccessible(true);
        $this->loggerProp = $reflectionPropertyLogger;

        $reflectionPropertyLevels = $reflectionSystemLogger->getProperty('levels');
        $reflectionPropertyLevels->setAccessible(true);
        $reflectionPropertyLevels->setValue([
            'ERROR' => 1
        ]);
    }

    function tearDown(): void {
        $this->systemProp->setValue(null);
        $this->loggerProp->setValue(null);
    }

    function testSendRegistration() {
        $systemMock = $this->getMockBuilder(System::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $systemMock->method('getApiTID')->willReturn('test_123');
        $systemMock->expects($this->exactly(1))->method('runFundistAPI')->willReturn('1,DummySuccess');
        
        $this->systemProp->setValue($systemMock);

        $tpl = new FundistEmailTemplate();
        $result = $tpl->sendRegistration([
            'firstName' => 'name',
            'lastName' => 'surname',
            'password' => 'testpassword',
            'email' => 'test@wlc.test',
            'currency' => 'EUR',
            'code' => 'registration-code',
            'reg_ip' => '127.0.0.1',
            'reg_site' => 'https://wlc-site.test',
            'reg_lang' => 'en',
        ]);

        $this->assertTrue($result);
    }

    function testSendRegistrationFailure() {
        $systemMock = $this->getMockBuilder(System::class)
        ->setMethods(['getApiTID', 'runFundistAPI'])
        ->disableOriginalConstructor()
        ->getMock();
        
        $systemMock->method('getApiTID')->willReturn('test_123');
        $systemMock->method('runFundistAPI')->willReturn('0,DummyFailure');

        $this->systemProp->setValue($systemMock);
        
        $tpl = new FundistEmailTemplate();
        $result = $tpl->sendRegistration([
            'firstName' => 'name',
            'lastName' => 'surname',
            'password' => 'testpassword',
            'email' => 'test@wlc.test',
            'currency' => 'EUR',
            'code' => 'registration-code',
            'reg_ip' => '127.0.0.1',
            'reg_site' => 'https://wlc-site.test',
            'reg_labg' => 'en',
        ]);
        
        $this->assertEquals($result, '0;DummyFailure');
    }

    function testsendRegistrationReminder() {
        $systemMock = $this->getMockBuilder(System::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $systemMock->expects($this->exactly(1))->method('getApiTID')->willReturn('test_123');
        $systemMock->expects($this->exactly(1))->method('runFundistAPI')->willReturn('1,DummySuccess');
        
        $this->systemProp->setValue($systemMock);

        $tpl = new FundistEmailTemplate();
        $result = $tpl->sendRegistrationReminder([
            'firstName' => 'name',
            'lastName' => 'surname',
            'password' => 'testpassword',
            'email' => 'test@wlc.test',
            'currency' => 'EUR',
            'code' => 'registration-code',
            'reg_ip' => '127.0.0.1',
            'reg_site' => 'https://wlc-site.test',
        ]);

        $this->assertTrue($result);
    }

    public function testSuccessSendTrustDeviceConfirmationEmail(): void
    {
        $systemMock = $this->getMockBuilder(System::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        $systemMock->expects($this->exactly(1))->method('getApiTID')->willReturn('test_123');
        $systemMock->expects($this->exactly(1))->method('runFundistAPI')->willReturn('1,DummySuccess');

        $this->systemProp->setValue($systemMock);

        $tpl = new FundistEmailTemplate();
        $result = $tpl->sendTrustDeviceConfirmationEmail([
            'firstName' => 'name',
            'lastName' => 'surname',
            'email' => 'test@wlc.test',
            'currency' => 'EUR',
            'code' => 'registration-code'
        ]);

        $this->assertTrue($result);
    }

    public function testFailedSendTrustDeviceConfirmationEmailWrongFundistResponse(): void
    {
        $systemMock = $this->getMockBuilder(System::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        $loggerMock = $this->getMockBuilder(Logger::class)
            ->setMethods(['addRecord'])
            ->disableOriginalConstructor()
            ->getMock();

        $systemMock->expects($this->exactly(1))->method('getApiTID')->willReturn('test_123');
        $systemMock->expects($this->exactly(1))->method('runFundistAPI')->willReturn('0,DummyFailure');

        $loggerMock->expects($this->exactly(1))->method('addRecord')->willReturn(true);

        $this->systemProp->setValue($systemMock);
        $this->loggerProp->setValue($loggerMock);

        $tpl = new FundistEmailTemplate();
        $result = $tpl->sendTrustDeviceConfirmationEmail([
            'TEST' => 1,
            'firstName' => 'name',
            'lastName' => 'surname',
            'email' => 'test@wlc.test',
            'currency' => 'EUR',
            'code' => 'registration-code'
        ]);

        $this->assertFalse($result);
    }
}
