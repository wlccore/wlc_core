<?php
namespace eGamings\WLC\Tests\Repository;

use eGamings\WLC\Core;
use eGamings\WLC\Domain\TrustDeviceConfiguration\TrustDeviceConfiguration;
use eGamings\WLC\Repository\TrustDevice;
use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Tests\DbMock;

final class TrustDeviceTest extends BaseCase
{
    private $coreDI;

    public function setUp(): void
    {
        parent::setUp();

        $builder = new \DI\ContainerBuilder();
        $builder->addDefinitions([
            'db' => function() {
                return \eGamings\WLC\Tests\DbMock::class;
            }
        ]);
        $this->coreDI = $builder->build();

        $this->injectDI($this->coreDI);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->injectDI(null);
    }

    public function testSuccessIssetDevice(): void
    {
        $repo = new TrustDevice();
        /** @var DbMock $dbMock */
        $dbMock = Core::DI()->get('db');
        $dbMock::setQueryResult([]);

        $this->assertIsArray($repo->issetDevice(1, 'f', 'ua'));
    }

    public function testSuccessRegisterNewDevice(): void
    {
        $repo = new TrustDevice();
        /** @var DbMock $dbMock */
        $dbMock = Core::DI()->get('db');
        $dbMock::setQueryResult(false);
        $dbMock::setQueryResult(true);
        $config = new TrustDeviceConfiguration(
            1, 'test@email.com', 'code', new \DateTime()
        );

        $this->assertTrue($repo->registerNewDevice($config, 'f', 'ua'));
    }

    public function testFailedRegisterNewDeviceAlreadyExists(): void
    {
        $repo = new TrustDevice();
        $existEntry = new \stdClass();
        $existEntry->id = 42;

        /** @var DbMock $dbMock */
        $dbMock = Core::DI()->get('db');
        $dbMock::setQueryResult($existEntry);
        $dbMock::setQueryResult(true);
        $dbMock::setAffectedRows(0);

        $config = new TrustDeviceConfiguration(
            1, 'test@email.com', 'code', new \DateTime()
        );

        $this->assertFalse($repo->registerNewDevice($config, 'f', 'ua'));
    }

    public function testSuccessGetAllDevices(): void
    {
        $repo = new TrustDevice();
        $device = new \stdClass();
        $device->id = '42';
        $device->fingerprint_hash = 'hash';
        $device->user_agent = 'ua';
        $device->is_trusted = 1;
        $device->updated = '2021-10-05 11:49:26';

        $entries = [
            $device
        ];

        /** @var DbMock $dbMock */
        $dbMock = Core::DI()->get('db');
        $dbMock::setQueryResult($entries);

        $result = $repo->getAllDevices(42);

        $this->assertIsArray($result);

        $result[0]->id = 42;
        $result[0]->is_trusted = true;

        $this->assertEquals($entries, $result);
    }

    public function testSuccessGetAllDevicesEmptyArray(): void
    {
        $repo = new TrustDevice();

        /** @var DbMock $dbMock */
        $dbMock = Core::DI()->get('db');
        $dbMock::setQueryResult(false);

        $result = $repo->getAllDevices(42);

        $this->assertEquals($result, []);
    }

    public function testSuccessSetDeviceTrustStatus(): void
    {
        $repo = new TrustDevice();
        /** @var DbMock $dbMock */
        $dbMock = Core::DI()->get('db');
        $dbMock::setQueryResult(true);
        $dbMock::setAffectedRows(1);

        $this->assertTrue($repo->setDeviceTrustStatus(42, true));
    }

    protected function injectDI(?\DI\Container $di_container): void
    {
        $reflectionProperty = new \ReflectionProperty(Core::class, 'di_container');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($di_container);
    }
}