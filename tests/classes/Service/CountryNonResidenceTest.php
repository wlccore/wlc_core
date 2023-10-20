<?php

namespace eGamings\WLC\Tests\Service;

use eGamings\WLC\Service\CountryNonResidence;
use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Tests\DbConnectionMock;
use eGamings\WLC\Tests\DbMock;
use eGamings\WLC\Tests\UserMock;

final class CountryNonResidenceTest extends BaseCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    public function testIsEnable(): void
    {
        $CNR = new CountryNonResidence(new UserMock());
        $this->assertFalse($this->invokeMethod($CNR, 'isEnable'));

        _cfg('CountryNonResidence', ['rus']);
        $CNR = new CountryNonResidence(new UserMock());
        $this->assertTrue($this->invokeMethod($CNR, 'isEnable'));
    }

    public function testOnlyAuthenticated(): void
    {
        $user = new UserMock();
        $userData = new \stdClass();
        $userData->id = 1;
        $userData->email = 'test@example.com';
        $user->setUserData($userData);

        $CNR = new CountryNonResidence($user);
        $this->assertTrue($this->invokeMethod($CNR, 'onlyAuthenticated'));

        try {
            $CNR = new CountryNonResidence(null);
            $this->invokeMethod($CNR, 'onlyAuthenticated');
        } catch (\ErrorException $ex) {
            $this->assertEquals(_('User is not authorized'), $ex->getMessage());
        }
    }

    public function testIsBlocked(): void
    {
        $CNR = new CountryNonResidence(null);
        $this->assertFalse($CNR->isBlocked());

        _cfg('userCountry', 'deu');
        _cfg('CountryNonResidence', ['rus']);
        $user = new UserMock();
        $userData = new \stdClass();
        $userData->id = 1;
        $userData->email = 'test@example.com';
        $user->setUserData($userData);
        $CNR = new CountryNonResidence($user);
        $this->assertFalse($CNR->isBlocked());

        $this->assertFalse($CNR->isBlocked('api/v1/auth'));

        _cfg('CountryNonResidence', ['deu']);
        $CNR = new CountryNonResidence($user);
        $this->assertFalse($CNR->isBlocked('api/v1/bootstrap'));
        $this->assertTrue($CNR->isBlocked('api/v1/deposits'));
        $this->assertTrue($CNR->isBlocked('api/v1/userInfo', '', [], true));
    }

    public function testSaveConfirmation(): void
    {
        _cfg('CountryNonResidence', []);
        $CNR = new CountryNonResidence(null);
        try {
            $CNR->saveConfirmation();
        } catch (\ErrorException $ex) {
            $this->assertEquals(_('This feature is disabled'), $ex->getMessage());
        }

        _cfg('CountryNonResidence', ['rus']);
        $CNR = new CountryNonResidence(null);
        try {
            $CNR->saveConfirmation();
        } catch (\ErrorException $ex) {
            $this->assertEquals(_('User is not authorized'), $ex->getMessage());
        }

        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        $conn->affected_rows = 1;
        DbMock::setConnection($conn);
        $conn->expects($this->exactly(1))->method('query')->willReturn(1);

        _cfg('userCountry', 'deu');
        _cfg('CountryNonResidence', ['deu']);
        $user = new UserMock();
        $userData = new \stdClass();
        $userData->id = 1;
        $userData->email = 'test@example.com';
        $user->setUserData($userData);
        $CNR = new CountryNonResidence($user);
        $CNR->saveConfirmation();
    }
}
