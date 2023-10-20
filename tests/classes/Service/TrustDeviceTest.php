<?php
namespace eGamings\WLC\Tests\Service;

use eGamings\WLC\Core;
use eGamings\WLC\Domain\TrustDeviceConfiguration\TrustDeviceConfiguration;
use eGamings\WLC\Provider\IUser;
use eGamings\WLC\Provider\Service\ITrustDevice;
use eGamings\WLC\RestApi\ApiException;
use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Tests\RedisMock;
use eGamings\WLC\Tests\UserMock;

final class TrustDeviceTest extends BaseCase
{
    private $coreDI;
    private $user;
    private $_SERVER;

    public function __construct()
    {
        parent::__construct();
        $this->_SERVER = $_SERVER;
    }

    public function setUp(): void
    {
        parent::setUp();

        $builder = new \DI\ContainerBuilder();
        $builder->addDefinitions([
            'repository.trust_device' => \DI\create(\eGamings\WLC\Tests\Repository\TrustDeviceMock::class),
            'redis' => \DI\create(RedisMock::class),
            'fundist_email_template' => \DI\create(\eGamings\WLC\Tests\FundistEmailTemplateMock::class),
        ]);
        $this->coreDI = $builder->build();
        $this->user = new UserMock();
        $userData = new \stdClass();
        $userData->id = 42;
        $userData->email = 'test@example.com';
        $userData->first_name = 'firstName';
        $userData->last_name = 'lastName';
        $this->user->setUserData($userData);

        $this->injectDI($this->coreDI);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->user = null;
        $_SERVER = $this->_SERVER;
        $this->injectDI(null);
    }

    public function testSuccessFetchAllDevices(): void
    {
        $this->assertIsArray($this->getService()->fetchAllDevices());
    }

    public function testSuccessCheckDevice(): void
    {
        $_SERVER['HTTP_X_UA_FINGERPRINT'] = 'fingerprint_hash';
        $_SERVER['HTTP_USER_AGENT'] = 'ua';
        $error = '';
        $this->assertFalse($this->getService()->checkDevice($error));
    }

    public function testFailedCheckDevice(): void
    {
        unset($_SERVER['HTTP_USER_AGENT']);
        $error = '';
        $this->assertFalse($this->getService()->checkDevice($error));
        $this->assertEquals('missing user agent', $error);
    }

    public function testSuccessSendConfirmationEmail(): void
    {
        $this->expectException(ApiException::class);
        Core::DI()->get('redis')::setSetReturn(true);
        $this->assertTrue($this->getService()->sendConfirmationEmail());
    }

    public function testFailedSendConfirmationEmailCantSendEmail(): void
    {
        $this->expectException(ApiException::class);
        Core::DI()->get('redis')::setSetReturn(true);
        Core::DI()->get('fundist_email_template')::setStatus(false);
        $this->assertTrue($this->getService()->sendConfirmationEmail());
    }

    public function testFailedSendConfirmationEmailRedisFailure(): void
    {
        Core::DI()->get('redis')::setSetReturn(false);
        $this->assertFalse($this->getService()->sendConfirmationEmail());
    }

    // hid this test for ticket #386200
    // public function testSuccessRegisterNewDevice(): void
    // {
    //     $this->assertTrue($this->getService()->registerNewDevice(
    //         new TrustDeviceConfiguration(42, 'e@mail.com', 'code', new \DateTime())
    //     ));
    // }

    protected function getService(?IUser $user = null): ITrustDevice
    {
        return new \eGamings\WLC\Service\TrustDevice($user ?? $this->user);
    }

    protected function injectDI(?\DI\Container $di_container): void
    {
        $reflectionProperty = new \ReflectionProperty(Core::class, 'di_container');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($di_container);
    }
}