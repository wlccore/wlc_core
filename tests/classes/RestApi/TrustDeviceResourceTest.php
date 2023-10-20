<?php

namespace eGamings\WLC\Tests\RestApi;

use eGamings\WLC\Core;
use eGamings\WLC\RestApi\ApiException;
use eGamings\WLC\RestApi\TrustDevicesResource;
use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Tests\UserMock;

final class TrustDeviceResourceTest extends BaseCase
{
    private $coreDI;
    private $user = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = (new UserMock())->setUser([]);

        $builder = new \DI\ContainerBuilder();
        $builder->addDefinitions([
            'service.trust_device' => \DI\create(\eGamings\WLC\Tests\Service\TrustDeviceMock::class),
            'user' => function () {
                return $this->user;
            },
        ]);
        $this->coreDI = $builder->build();

        $this->injectDI($this->coreDI);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->injectDI(null);
    }

    public function testGetSuccess(): void
    {
        $this->assertIsArray($this->performTest('get'));
    }

    public function testGetException(): void
    {
        $this->expectException(ApiException::class);

        $this->user = (new UserMock())->setUser(null);

        $this->assertIsArray($this->performTest('get'));
    }

    public function testPostSuccess(): void
    {
        $this->assertIsArray($this->performTest('post', [
            'code' => '123456', 
            'login' => 'test@email.com'
        ], []));
    }

    public function testPostEmptyCode(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage(_('Empty required parameter'));

        $this->performTest('post');
    }

    public function testPostCodeIsIncorrect(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage(_('Code is incorrect'));

        $this->performTest('post', [
            'code' => 'not a code',
            'login' => 'not a login'
        ], []);
    }

    public function testDeleteSuccess(): void
    {
        $this->assertIsArray($this->performTest('delete', [], [
            'deviceId' => '123456'
        ]));
    }

    public function testDeleteEmptyDeviceId(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage(_('Empty required parameter'));

        $this->performTest('delete');
    }

    public function testPatchSuccess(): void
    {
        $this->assertIsArray($this->performTest('patch', [], [
            'deviceId' => '123456'
        ]));
    }

    public function testPatchEmptyDeviceId(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage(_('Empty required parameter'));

        $this->performTest('patch');
    }

    public function performTest(string $method, array $request = [], array $query = [], array $params = []) {
        $trustDeviceResource = new TrustDevicesResource();

        return $trustDeviceResource->$method($request, $query, $params);
    }

    protected function injectDI(?\DI\Container $di_container): void
    {
        $reflectionProperty = new \ReflectionProperty(Core::class, 'di_container');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($di_container);
    }
}
