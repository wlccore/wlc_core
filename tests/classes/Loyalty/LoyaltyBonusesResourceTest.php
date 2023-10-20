<?php
namespace eGamings\WLC\Tests\Loyalty;

use eGamings\WLC\Tests\BaseCase;
use ReflectionClass;
use eGamings\WLC\Loyalty\LoyaltyBonusesResource;
use eGamings\WLC\Loyalty;
use eGamings\WLC\Front;
use eGamings\WLC\User;

class LoyaltyBonusesResourceTest extends BaseCase
{
    private $user = null;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
    }

    public function setUp(): void {
        $frontReflection = new ReflectionClass(Front::class);
        $front = $frontReflection->newInstanceWithoutConstructor();
        $fProperty = $frontReflection->getProperty('f');
        $fProperty->setAccessible(true);
        $fProperty->setValue($front);
        
        $this->user = (new ReflectionClass(User::class))->newInstanceWithoutConstructor();
        $this->user->userData = new \stdClass();
        $this->user->userData->id = null;
        
        $userProperty = $frontReflection->getProperty('_user');
        $userProperty->setAccessible(true);
        $userProperty->setValue(Front::getInstance(), $this->user);
    }
    
    public function tearDown(): void {
        $reflectionLoyalty = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionLoyalty->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(null);
        
        $frontReflection = new ReflectionClass(Front::class);
        $front = $frontReflection->newInstanceWithoutConstructor();
        $fProperty = $frontReflection->getProperty('f');
        $fProperty->setAccessible(true);
        $fProperty->setValue(null);
        
        $this->user = null;
    }

    public function testBonusGetEmptyArray() {
        $result = LoyaltyBonusesResource::BonusGet(false);
        $this->assertFalse($result, 'Undefined bonus id must return false');
    }

    public function testBonusGetAnonymousBonusId() {
        $mock = $this->getMockBuilder(Loyalty::class)->setMethods(['send'])->getMock();
        $mock->expects($this->exactly(1))->method('send')->willReturn('[{"ID":1,"data": true}]');

        $reflectionLoyalty = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionLoyalty->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $result = LoyaltyBonusesResource::BonusGet(['id' => 1]);

        $this->assertTrue(!empty($result), 'Bonus result must be not empty');
        $this->assertTrue(is_array($result), 'Bonus result must be array');
        $this->assertTrue(!empty($result['ID']), 'Bonus result must contain bonus id');
        $this->assertEquals(1, $result['ID'], 'Bonus result bonus id must be 1');
    }

    public function testBonusGetAnonymousBonusIdException() {
        $mock = $this->getMockBuilder(Loyalty::class)->setMethods(['send'])->getMock();
        $mock->expects($this->exactly(1))->method('send')->willReturn('false');

        $reflectionLoyalty = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionLoyalty->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(_('Loyalty result not supported'));
        $result = LoyaltyBonusesResource::BonusGet(['id' => 1]);
    }

    public function testBonusGetAuthorizedBonusId() {
        $mock = $this->getMockBuilder(Loyalty::class)->setMethods(['send'])->getMock();
        $mock->expects($this->exactly(1))->method('send')->willReturn('[{"ID":1,"data": true}]');

        $reflectionLoyalty = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionLoyalty->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $this->user->userData->id = 1;
        $_SESSION['FundistIDUser'] = 'FTestUser123';
        $_SESSION['user']['UserTags'] = '1';
        $result = LoyaltyBonusesResource::BonusGet(['id' => 1]);
        unset($_SESSION['user']);
        unset($_SESSION['FundistIDUser']);

        $this->assertTrue(!empty($result), 'Bonus result must be not empty');
        $this->assertTrue(is_array($result), 'Bonus result must be array');
        $this->assertTrue(!empty($result['ID']), 'Bonus result must contain bonus id');
        $this->assertEquals(1, $result['ID'], 'Bonus result bonus id must be 1');
    }

    public function testBonusGetAnonymousBonusDataId() {
        $mock = $this->getMockBuilder(Loyalty::class)->setMethods(['send'])->getMock();
        $mock->expects($this->exactly(1))->method('send')->willReturn('{"ID":1,"data": true}');

        $reflectionLoyalty = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionLoyalty->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $result = LoyaltyBonusesResource::BonusData(1);

        $this->assertTrue(!empty($result), 'Bonus result must be not empty');
        $this->assertTrue(is_array($result), 'Bonus result must be array');
        $this->assertTrue(!empty($result['ID']), 'Bonus result must contain bonus id');
        $this->assertEquals(1, $result['ID'], 'Bonus result bonus id must be 1');
    }
    
    public function testBonuseGetAnonymousBonusDataIdException() {
        $mock = $this->getMockBuilder(Loyalty::class)->setMethods(['send'])->getMock();
        $mock->expects($this->exactly(1))->method('send')->willReturn('false');

        $reflectionLoyalty = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionLoyalty->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(_('Loyalty result not supported'));
        $result = LoyaltyBonusesResource::BonusData(1);
    }

    public function testBonusDataAuthorizedBonusId() {
        $mock = $this->getMockBuilder(Loyalty::class)->setMethods(['send'])->getMock();
        $mock->expects($this->exactly(1))->method('send')->willReturn('{"ID":1,"data": true}');

        $reflectionLoyalty = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionLoyalty->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $this->user->userData->id = 1;
        $_SESSION['FundistIDUser'] = 'FTestUser123';
        $_SESSION['user']['UserTags'] = '1';
        $result = LoyaltyBonusesResource::BonusData(1);
        unset($_SESSION['user']);
        unset($_SESSION['FundistIDUser']);

        $this->assertTrue(!empty($result), 'Bonus result must be not empty');
        $this->assertTrue(is_array($result), 'Bonus result must be array');
        $this->assertTrue(!empty($result['ID']), 'Bonus result must contain bonus id');
        $this->assertEquals(1, $result['ID'], 'Bonus result bonus id must be 1');
    }

    public function testBonusesHistory() {
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

        $this->user->userData->id = 1;
        $_SESSION['FundistIDUser'] = 'FTestUser123';
        $_SESSION['user']['IDCategory'] = '1';
        $result = LoyaltyBonusesResource::BonusesHistory();
        unset($_SESSION['user']);
        unset($_SESSION['FundistIDUser']);

        $this->assertTrue(!empty($result), 'Bonus result must be not empty');
        $this->assertTrue(is_array($result), 'Bonus result must be array');
        $this->assertTrue(!empty($result['result'][0]['ID']), 'Bonus result must contain bonus id');
        $this->assertEquals(1, $result['result'][0]['ID'], 'Bonus result bonus id must be 1');
    }

}
