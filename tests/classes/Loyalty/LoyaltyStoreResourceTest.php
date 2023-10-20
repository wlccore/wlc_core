<?php
namespace eGamings\WLC\Tests\Loyalty;

use eGamings\WLC\Loyalty;
use eGamings\WLC\System;
use eGamings\WLC\Tests\BaseCase;
use ReflectionClass;
use eGamings\WLC\Loyalty\LoyaltyStoreResource;
use eGamings\WLC\Front;
use eGamings\WLC\User;

class LoyaltyStoreResourceTest extends BaseCase
{
    private $user = null;
    private $frontReflection = null;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
    }

    public function setUp(): void {
        $frontReflection = new ReflectionClass(Front::class);
        $front = $frontReflection->newInstanceWithoutConstructor();
        $fProperty = $frontReflection->getProperty('f');
        $fProperty->setAccessible(true);
        $fProperty->setValue($front);
        $this->frontReflection = $frontReflection;

        $this->user = (new ReflectionClass(User::class))->newInstanceWithoutConstructor();
        $this->user->userData = new \stdClass();
        $this->user->userData->id = null;

        $userProperty = $frontReflection->getProperty('_user');
        $userProperty->setAccessible(true);
        $userProperty->setValue(Front::getInstance(), $this->user);
    }

    public function tearDown(): void {
        $frontReflection = new ReflectionClass(Front::class);
        $front = $frontReflection->newInstanceWithoutConstructor();
        $fProperty = $frontReflection->getProperty('f');
        $fProperty->setAccessible(true);
        $fProperty->setValue(null);

        $this->user = null;
        $this->frontReflection = null;
    }

    public function testStoreGet() {
        $mock = $this->getMockBuilder(Loyalty::class)->setMethods(['send'])->getMock();

        $this->user->userData->id = 1;
        $_SESSION['FundistIDUser'] = 1;
        $_SESSION['user']['UserTags'] = '1';

        $mock
            ->method('send')
            ->willReturn(
                '{"ID": 1, "Name": "Item1"}'
            );

        $reflectionLoyalty = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionLoyalty->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $result = LoyaltyStoreResource::StoreItems();
        $this->assertTrue(!empty($result));
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, $result['ID']);
        $this->assertEquals("Item1", $result['Name']);

        unset($_SESSION['user']);
        unset($_SESSION['FundistIDUser']);
    }


    public function testStoreBuy() {
        $mock = $this->getMockBuilder(LoyaltyStoreResource::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        $userProperty = $this->frontReflection->getProperty('_user');
        $userProperty->setAccessible(true);
        $userProperty->setValue(Front::getInstance(), $mock);

        $mock->user = new \stdClass();
        $mock->userData = new \stdClass();
        $mock->userData->id = 1;
        $_SESSION['FundistIDUser'] = 'TestUser';

        $mock
            ->method('getApiTID')
            ->willReturn('test_123');

        $mock
            ->method('runFundistAPI')
            ->willReturn(
                '1,{"ID": 1, "IDUser": 1, "IDItem": 1}'
            );

        $result = LoyaltyStoreResource::StoreBuy(1, 2);
        $this->assertTrue(!empty($result));
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, $result['IDItem']);

        unset($_SESSION['FundistIDUser']);
    }

    public function testStoreOrders()
    {
        $mock = $this->getMockBuilder(Loyalty::class)->setMethods(['send'])->getMock();

        $this->user->userData->id = 1;
        $_SESSION['FundistIDUser'] = 'FTestUser123';
        $_SESSION['user']['IDCategory'] = '1';

        // testing pass the 'status' filter
        foreach ([1,99,100, 0] as $orderStatus) {
            $_GET['status'] = $orderStatus;

            $mock
                ->method('send')
                ->will($this->returnCallback(function($url, $params) {
                    $paramsOrderStatus = isset($params['Status']) ? $params['Status'] : 0;

                    return json_encode($this->getDummyStoreOrdersData($paramsOrderStatus));
                }));

            $reflectionLoyalty = new ReflectionClass(Loyalty::class);
            $reflectionProperty = $reflectionLoyalty->getProperty('instance');
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($mock);

            $resultData = LoyaltyStoreResource::StoreOrders();
            $expectedData = $this->getDummyStoreOrdersData($orderStatus);

            $this->assertSame($expectedData, $resultData);
        }

        unset($_SESSION['user']);
        unset($_SESSION['FundistIDUser']);
        unset($_GET['Status']);
    }

    public function testStoreGetCategories()
    {
        $mock = $this->getMockBuilder(Loyalty::class)->setMethods(['send'])->getMock();

        $this->user->userData->id = 1;
        $_SESSION['FundistIDUser'] = 1;
        $_GET['showAll'] = 1;

        $mock
            ->method('send')
            ->willReturn(
                '{"ID": 1, "Name": {"en": "Category1"}}'
            );

        $reflectionLoyalty = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionLoyalty->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $result = LoyaltyStoreResource::StoreGetCategories();
        $this->assertTrue(!empty($result));
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, $result['ID']);
        $this->assertEquals("Category1", $result['Name']["en"]);

        unset($_SESSION['FundistIDUser']);
    }

    /**
     * Gets dummy store orders data
     *
     * @param int $status
     *
     * @return array
     */
    private function getDummyStoreOrdersData(int $status)
    {
        $dummyStoreOrdersData = [
            [
                'ID' => '1',
                'IDClient' => '1',
                'IDUser' => '1',
                'IDItem' => 'testItem1',
                'IDTransaction' => 'testTransaction',
                'Status' => '1',
                'Updated' => '2020-04-15 09:19:30',
                'Amount' => '1.0000',
                'Name' => 'test item',
            ],
            [
                'ID' => '2',
                'IDClient' => '1',
                'IDUser' => '1',
                'IDItem' => 'testItem1',
                'IDTransaction' => 'testTransaction',
                'Status' => '99',
                'Updated' => '2020-04-15 09:19:30',
                'Amount' => '1.0000',
                'Name' => 'test item',
            ],
            [
                'ID' => '3',
                'IDClient' => '1',
                'IDUser' => '1',
                'IDItem' => 'testItem1',
                'IDTransaction' => 'testTransaction',
                'Status' => '100',
                'Updated' => '2020-04-15 09:19:30',
                'Amount' => '1.0000',
                'Name' => 'test item',
            ],
        ];

        if (!in_array($status, [1,99,100])) {
            return $dummyStoreOrdersData;
        }

        return array_values(array_filter($dummyStoreOrdersData, function($item) use ($status) {
            return isset($item['Status']) && $item['Status'] == $status;
        }));
    }

}
