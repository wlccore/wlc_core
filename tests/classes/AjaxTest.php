<?php
namespace eGamings\WLC\Tests;

use eGamings\WLC\Front;
use eGamings\WLC\Loyalty;
use eGamings\WLC\System;
use eGamings\WLC\User;
use eGamings\WLC\Ajax;
use eGamings\WLC\Tests\BaseCase;
use ReflectionClass;

class AjaxTest extends BaseCase {
    private static $frontReflection;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        $frontReflection = new ReflectionClass(Front::class);
        $front = $frontReflection->newInstanceWithoutConstructor();
        
        $fProperty = $frontReflection->getProperty('f');
        $fProperty->setAccessible(true);
        $fProperty->setValue($front);
        
        self::$frontReflection = $frontReflection;
    }

    public function setUp(): void {
        $user = (new ReflectionClass(User::class))->newInstanceWithoutConstructor();
        $user->userData = new \stdClass();
        $user->userData->id = null;

        $userProperty = self::$frontReflection->getProperty('_user');
        $userProperty->setAccessible(true);
        $userProperty->setValue(Front::getInstance(), $user);
    }
    
    public function tearDown(): void {
        $userProperty = self::$frontReflection->getProperty('_user');
        $userProperty->setAccessible(true);
        $userProperty->setValue(Front::getInstance(), null);

        $reflectionLoyalty = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionLoyalty->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(null);
    }

    public function testBonusAnonymous() {
        $ajax = (new ReflectionClass(Ajax::class))->newInstanceWithoutConstructor();

        $mock = $this->getMockBuilder(Loyalty::class)->setMethods(['send'])->getMock();
        $mock->expects($this->exactly(1))->method('send')
            ->willReturn('[
                {
                    "ID":1,
                    "Selected": false,
                    "Active": false,
                    "Inventoried": false,
                    "AllowCatalog": true,
                    "Bonus": false
                }
            ]');

        $reflectionLoyalty = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionLoyalty->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $this->assertTrue(is_object($ajax), 'Mock creation success');

        $result = $ajax->Bonus();

        $this->assertTrue(!empty($result), 'Bonus result must be non empty');
        $this->assertTrue(!empty($result[0]['ID']), 'Bonus result 0 id must be non empty');
        $this->assertEquals(1, $result[0]['ID'], 'First bonus id must be 1');
    }

    public function testBonusAnonymousStore() {
        $ajax = (new ReflectionClass(Ajax::class))->newInstanceWithoutConstructor();

        $mock = $this->getMockBuilder(Loyalty::class)->setMethods(['send'])->getMock();
        $mock->expects($this->exactly(1))->method('send')
            ->willReturn('[
                {
                    "ID":1,
                    "Selected": false,
                    "Active": false,
                    "Inventoried": false,
                    "AllowCatalog": true,
                    "Bonus": false
                }
            ]');

        $reflectionLoyalty = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionLoyalty->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $this->assertTrue(is_object($ajax), 'Mock creation success');

        $result = $ajax->Bonus(['event' => 'store', 'lang' => 'en']);

        $this->assertTrue(!empty($result), 'Bonus result must be non empty');
        $this->assertTrue(!empty($result[0]['ID']), 'Bonus result 0 id must be non empty');
        $this->assertEquals(1, $result[0]['ID'], 'First bonus id must be 1');
    }

    public function testBonusLootboxPrizes() {
        $ajax = (new ReflectionClass(Ajax::class))->newInstanceWithoutConstructor();

        $mock = $this->getMockBuilder(Loyalty::class)->setMethods(['send'])->getMock();
        $mock->expects($this->exactly(1))->method('send')->willReturn('[
            {
                "ID":1,
                "Selected": false,
                "Active": false,
                "Inventoried": false,
                "AllowCatalog": true,
                "Bonus": false
            }
        ]');

        $reflectionLoyalty = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionLoyalty->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $rpUser = self::$frontReflection->getProperty('_user');
        $rpUser->setAccessible(true);
        $user = $rpUser->getValue(Front::getInstance());
        $user->userData = new \stdClass();
        $user->userData->id = 112233;

        $this->assertTrue(is_object($ajax), 'Mock creation success');

        $_SESSION['FundistIDUser'] = 'FTestUser123';
        $_SESSION['user']['UserTags'] = '1';
        $result = $ajax->Bonus(['type' => 'lootboxPrizes', 'lang' => 'en']);
        unset($_SESSION['user']);
        unset($_SESSION['FundistIDUser']);

        $this->assertTrue(!empty($result), 'Bonus result must be non empty');
        $this->assertTrue(!empty($result[0]['ID']), 'Bonus result 0 id must be non empty');
        $this->assertEquals(1, $result[0]['ID'], 'First bonus id must be 1');
    }

    public function testBonusAuthorized() {
        $ajax = (new ReflectionClass(Ajax::class))->newInstanceWithoutConstructor();

        $mock = $this->getMockBuilder(Loyalty::class)->setMethods(['send'])->getMock();
        $mock->expects($this->exactly(1))->method('send')->willReturn('[
            {
                "ID":1,
                "Selected": false,
                "Active": false,
                "Inventoried": false,
                "AllowCatalog": true,
                "Bonus": false
            }
        ]');

        $reflectionLoyalty = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionLoyalty->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $rpUser = self::$frontReflection->getProperty('_user');
        $rpUser->setAccessible(true);
        $user = $rpUser->getValue(Front::getInstance());
        $user->userData = new \stdClass();
        $user->userData->id = 112233;

        $this->assertTrue(is_object($ajax), 'Mock creation success');

        $_SESSION['FundistIDUser'] = 'FTestUser123';
        $_SESSION['user']['UserTags'] = '1';
        $result = $ajax->Bonus();
        unset($_SESSION['user']);
        unset($_SESSION['FundistIDUser']);

        $this->assertTrue(!empty($result), 'Bonus result must be non empty');
        $this->assertTrue(!empty($result[0]['ID']), 'Bonus result 0 id must be non empty');
        $this->assertEquals(1, $result[0]['ID'], 'First bonus id must be 1');
    }

    public function testBonusSelect()
    {
        $ajax = (new ReflectionClass(Ajax::class))->newInstanceWithoutConstructor();
        $ajaxMock = $this->getMockBuilder(Ajax::class)->disableOriginalConstructor()
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->getMock();

        $ajaxMock->expects($this->any())->method('getApiTID')->willReturn('test_123');
        $ajaxMock->expects($this->any())->method('runFundistAPI')->willReturn('1,success');

        $conn = $this->getMockBuilder(DbConnectionMock::class)->disableOriginalConstructor()
            ->setMethods(['query'])
            ->getMock();
        DbMock::setConnection($conn);
        $conn->expects($this->any())->method('query')->willReturn(true);
        $conn->insert_id = 1;

        $userProp = self::$frontReflection->getProperty('_user');
        $userProp->setAccessible(true);
        $user = $userProp->getValue(Front::getInstance());
        $user->userData = new \stdClass();
        $user->userData->id = 112233;

        $this->assertTrue(is_object($ajax), 'Mock creation success');

        $_SESSION['FundistIDUser'] = 'FTestUser123';
        $result = $ajax->Bonus(['IDBonus' => 1, 'Status' => 1]);
        unset($_SESSION['user']);
        unset($_SESSION['FundistIDUser']);

        $this->assertEquals('success', $result);
    }

    public function testBonusCancel() {
        $mock = $this->getMockBuilder(Ajax::class)->disableOriginalConstructor()
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->getMock();

        $iProp = (new ReflectionClass(Ajax::class))->getProperty('instance');
        $iProp->setAccessible(true);
        $iProp->setValue($mock);

        $mock->expects($this->exactly(1))->method('getApiTID')->willReturn('test_123');
        $mock->expects($this->exactly(1))->method('runFundistAPI')->willReturn('1, 100');

        $rpUser = self::$frontReflection->getProperty('_user');
        $rpUser->setAccessible(true);
        $user = $rpUser->getValue(Front::getInstance());
        $user->userData = new \stdClass();
        $user->userData->id = 112233;

        $_SESSION['FundistIDUser'] = 'FTestUser123';
        $_SESSION['user']['UserTags'] = '1';
        $result = $mock->bonus_cancel(['IDBonus' => 123, 'LBID' => 345]);
        unset($_SESSION['user']);
        unset($_SESSION['FundistIDUser']);
        $this->assertTrue(!empty($result), 'Bonus cancel result must be non empty');
        $result = json_decode($result, true);
        $this->assertTrue(!empty($result['balance']), 'Bonus cancel result should contain balance');
        $this->assertEquals(100, $result['balance'], 'Bonus cancel result should be == 100');
    }

    public function testAchievement() {
        $ajax = (new ReflectionClass(Ajax::class))->newInstanceWithoutConstructor();

        $mock = $this->getMockBuilder(Loyalty::class)->setMethods(['send'])->getMock();
        $mock->expects($this->exactly(1))->method('send')->willReturn('[
            {
                "ID": 1,
                "Name": {
                    "en": "Test1"
                },
                "Description": "Test2",
                "Achievement": 1
            },
            {
                "ID": 2,
                "Name": {
                    "ru": "Test2"
                },
                "Description": "Test2",
                "Achievement": 2
            },
            {
                "ID": 3,
                "Name": {
                    "de": "Test3"
                },
                "Description": "Test3",
                "Achievement": 3
            }
        ]');

        $reflectionLoyalty = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionLoyalty->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $rpUser = self::$frontReflection->getProperty('_user');
        $rpUser->setAccessible(true);
        $user = $rpUser->getValue(Front::getInstance());
        $user->userData = new \stdClass();
        $user->userData->id = 112233;

        $this->assertTrue(is_object($ajax), 'Mock creation success');

        $lang = _cfg('language');
        _cfg('language', 'ru');
        $_SESSION['FundistIDUser'] = 'FTestUser123';
        $_SESSION['user']['IDCategory'] = '1';
        $result = $ajax->Achievement();
        unset($_SESSION['user']);
        unset($_SESSION['FundistIDUser']);
        _cfg('language', $lang);

        $this->assertTrue(!empty($result), 'Achievement result must be non empty');
        $this->assertTrue(!empty($result[0]['ID']), 'Achievement result 0 id must be non empty');
        $this->assertEquals(1, $result[0]['ID'], 'First achievement id must be 1');
        $this->assertTrue(!empty($result[1]['ID']), 'Achievement result 1 id must be non empty');
        $this->assertEquals(2, $result[1]['ID'], 'Second achievement id must be 2');
        $this->assertTrue(!empty($result[2]['ID']), 'Achievement result 2 id must be non empty');
        $this->assertEquals(3, $result[2]['ID'], 'Third achievement id must be 3');
    }

    public function testBonusHistory() {
        $ajax = (new ReflectionClass(Ajax::class))->newInstanceWithoutConstructor();

        $mock = $this->getMockBuilder(Loyalty::class)->setMethods(['send'])->getMock();
        $mock->expects($this->exactly(1))->method('send')
            ->willReturn('[
                {
                    "ID":1,
                    "Name": {
                        "en": "Bonus"
                    },
                    "Description": {
                        "en": "Bonus"
                    },
                    "Image": {
                        "en": "Bonus"
                    },
                    "Image_promo": {
                        "en": "Bonus"
                    },
                    "Image_main": {
                        "en": "Bonus"
                    },
                    "Image_description": {
                        "en": "Bonus"
                    },
                    "Image_reg": {
                        "en": "Bonus"
                    },
                    "Image_store": {
                        "en": "Bonus"
                    },
                    "Image_deposit": {
                        "en": "Bonus"
                    },
                    "Image_other": {
                        "en": "Bonus"
                    }
                }
            ]');

        $reflectionLoyalty = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionLoyalty->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $rpUser = self::$frontReflection->getProperty('_user');
        $rpUser->setAccessible(true);
        $user = $rpUser->getValue(Front::getInstance());
        $user->userData = new \stdClass();
        $user->userData->id = 112233;

        $this->assertTrue(is_object($ajax), 'Mock creation success');

        $_SESSION['FundistIDUser'] = 'FTestUser123';
        $_SESSION['user']['IDCategory'] = '1';
        $result = $ajax->bonus_history();
        $result = json_decode($result, JSON_OBJECT_AS_ARRAY);
        unset($_SESSION['user']);
        unset($_SESSION['FundistIDUser']);

        $this->assertTrue(!empty($result), 'Bonus result must be non empty');
        $this->assertTrue(!empty($result[0]['ID']), 'Bonus result 0 id must be non empty');
        $this->assertEquals(1, $result[0]['ID'], 'First bonus id must be 1');
    }
}
