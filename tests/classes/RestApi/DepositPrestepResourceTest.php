<?php

namespace eGamings\WLC\Tests\RestApi;

use eGamings\WLC\RestApi\ApiException;
use eGamings\WLC\RestApi\DepositPrestepResource;
use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\User;

final class DepositPrestepResourceTest extends BaseCase
{
    private $userMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->userMock = $this->createMock(User::class);

        $reflectionProperty = new \ReflectionProperty(User::class, '_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->userMock);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $reflectionProperty = new \ReflectionProperty(User::class, '_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(null);
    }

    public function testWrongResponseFormat(): void
    {
        $this->userMock
            ->method('depositPrestep')
            ->willReturn('wrong_response');

        $userProfileResource = new DepositPrestepResource();
        $this->expectException(ApiException::class);

        $userProfileResource->post([], []);
    }

    public function testUnsuccessfulResponse(): void
    {
        $this->userMock
            ->method('depositPrestep')
            ->willReturn('0,Some error');

        $userProfileResource = new DepositPrestepResource();
        $this->expectException(ApiException::class);

        $userProfileResource->post([], []);
    }

    public function testSuccessResponseWithoutJson(): void
    {
        $this->userMock
            ->method('depositPrestep')
            ->willReturn('1,1');

        $userProfileResource = new DepositPrestepResource();

        $this->assertNull($userProfileResource->post([], []));
    }

    public function testSuccessResponseWithJson(): void
    {
        $arr = ['some' => 'data'];

        $this->userMock
            ->method('depositPrestep')
            ->willReturn('1,' . json_encode($arr));

        $userProfileResource = new DepositPrestepResource();
        $result = $userProfileResource->post([], []);

        $this->assertIsArray($result);
        $this->assertEquals($arr['some'], $result['some'] ?? '');
    }

    public function testResponseWithWrongJson(): void
    {
        $this->userMock
            ->method('depositPrestep')
            ->willReturn('0,{"wrong":');

        $userProfileResource = new DepositPrestepResource();
        $this->expectException(ApiException::class);

        $userProfileResource->post([], []);
    }
}
