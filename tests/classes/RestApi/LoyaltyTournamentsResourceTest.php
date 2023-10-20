<?php

namespace eGamings\WLC\Tests\RestApi;

use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\User;
use eGamings\WLC\Core;
use eGamings\WLC\Tests\CoreMock;
use eGamings\WLC\Tests\DbMock;
use eGamings\WLC\Tests\DbConnectionMock;
use eGamings\WLC\Loyalty\LoyaltyTournamentsResource;

final class LoyaltyTournamentsResourceTest extends BaseCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $userMock;

    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $this->userMock = $this->createMock(User::class);

        $reflectionProperty = new \ReflectionProperty(User::class, '_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->userMock);

        $reflectionCore = new \ReflectionClass(Core::class);
        $reflectionProperty = $reflectionCore->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(new CoreMock());

        DbMock::setConnection(null);
        DbMock::setConnClass('eGamings\WLC\Tests\DbConnectionMock');
        DbConnectionMock::$hasConnectError = false;
    }

    public function tearDown(): void
    {
        parent::tearDown(); // TODO: Change the autogenerated stub

        $reflectionProperty = new \ReflectionProperty(User::class, '_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(null);

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

    public function testTournamentsSelect(): void
    {
        $this->userMock->expects($this->any())->method('fundist_uid')->willReturn(0);

        $this->assertEquals(LoyaltyTournamentsResource::TournamentsSelect(0, false), ['error' => _('must_login')], "");
    }
}
