<?php 
namespace eGamings\WLC\Tests\Loyalty;

use eGamings\WLC\Tests\DbMock;
use eGamings\WLC\Tests\DbConnectionMock;
use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Loyalty;
use eGamings\WLC\Front;
use eGamings\WLC\Loyalty\LoyaltyTournamentsResource;
use eGamings\WLC\Utils;
use eGamings\WLC\User;
use ReflectionClass;

class LotaltyTournamentsTest extends BaseCase {

    private $user = null;

    public function setUp(): void {
        $frontReflection = new ReflectionClass(Front::class);
        $front = $frontReflection->newInstanceWithoutConstructor();
        $fProperty = $frontReflection->getProperty('f');
        $fProperty->setAccessible(true);
        $fProperty->setValue($front);

        $this->user = (new ReflectionClass(User::class))->newInstanceWithoutConstructor();
        $this->user->userData = new \stdClass();
        $this->user->userData->id = null;
        $this->user->userData->fundist_uid = 0;

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

    function testTournamentsList() {
        $mock = $this->getMockBuilder(Loyalty::class)->setMethods(['send'])->getMock();
        $mock->expects($this->exactly(1))->method('send')->willReturn('[{"ID":1,"data": true},{"ID":2,"data": false}]');

        $reflectionLoyalty = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionLoyalty->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $this->user->userData->id = 1;
        $_SESSION['FundistIDUser'] = 'FTestUser123';
        $_SESSION['user']['UserTags'] = '1';
        $result = LoyaltyTournamentsResource::TournamentsList('eur');
        unset($_SESSION['user']);
        unset($_SESSION['FundistIDUser']);

        $this->assertTrue(is_array($result), 'Check that result is array');
        $this->assertEquals(count($result), 2, 'Check that result is array with 2 items');
        $this->assertTrue(!empty($result[0]['ID']), 'Check that result 0 item ID non empty');
    }

    function testTournamentsListWithError() {
        $mock = $this->getMockBuilder(Loyalty::class)->setMethods(['send'])->getMock();
        $mock->expects($this->exactly(1))->method('send')->willReturn('{"error": "Test error"}');

        $reflectionLoyalty = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionLoyalty->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $hasException = false;
        try {
            $this->user->userData->id = 1;
            $_SESSION['FundistIDUser'] = 'FTestUser123';
            $_SESSION['user']['UserTags'] = '1';
            $result = LoyaltyTournamentsResource::TournamentsList('eur');
            unset($_SESSION['user']);
            unset($_SESSION['FundistIDUser']);
        } catch (\Exception $ex) {
            $hasException = true;
        }
        $this->assertTrue($hasException, 'Check if exception is thrown');
    }

    function testTournamentsGet() {
        $mock = $this->getMockBuilder(Loyalty::class)->setMethods(['send'])->getMock();
        $mock->expects($this->exactly(1))->method('send')->willReturn('[{"ID":1,"data": true},{"ID":2,"data": false}]');

        $reflectionLoyalty = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionLoyalty->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $result = LoyaltyTournamentsResource::TournamentGet(1, 'eur');
        $this->assertTrue(is_array($result), 'Check that result is array');
        $this->assertTrue(!empty($result['ID']), 'Check that result ID non empty');
        $this->assertEquals($result['ID'], 1, 'Check that result ID eq to 1');
    }

    function testTournamentWidgetsTop() {
        $mock = $this->getMockBuilder(Loyalty::class)->setMethods(['send'])->getMock();
        $mock->expects($this->exactly(1))->method('send')->willReturn('{"start":0,"limit":50,"results":[{"IDUserPlace":"1","IDUser":"1","Points":"500.00","Login":"1_1"}],"user":{"ID":"1","IDUser":"1","IDTournament":"1","BetsCount":"1","WinsCount":"1","BetsAmount":"50.00","WinsAmount":"50.00","Points":500,"ManualPoints":"0.00","Status":"1","Place":"1","Win":null,"LastBet":null,"EndDate":null,"AddDate":"0000-00-00 00:00:00","Balance":"200.0000","IDLoyalty":"1","ExRate":"1.00000000","Qualification":"0","PointsCoef":"2.00","Currency":"EUR"}}');

        $reflectionLoyalty = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionLoyalty->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $connection = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        DbMock::setConnection($connection);

        $queryResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_assoc'])->getMock();
        $queryResult->num_rows = 1;

        $fetchResult = [
            'id' => 1,
            'first_name' => 'Name',
            'last_name' => 'Surname',
            'login' => 'Login',
            'email' => 'user@email.com',
        ];

        $queryResult->fetch_assoc = function() use ($fetchResult) {
            return $fetchResult;
        };

        $connection->expects($this->exactly(1))
            ->method('query')
            ->willReturn($queryResult);

        $queryResult->expects($this->exactly(2))
            ->method('fetch_assoc')
            ->willReturn($fetchResult, null);


        $result = LoyaltyTournamentsResource::TournamentWidgetsTop(1);
        $this->assertIsArray($result);
        $this->assertTrue(isset($result['results'][0]));
        $this->assertEquals($fetchResult['id'], $result['results'][0]['IDUser']);
        $this->assertEquals($fetchResult['first_name'], $result['results'][0]['FirstName']);
        $this->assertEquals($fetchResult['last_name'][0], $result['results'][0]['LastName']);
        $this->assertEquals($fetchResult['login'], $result['results'][0]['UserLogin']);
        $this->assertEquals(Utils::hideStringWithWildcards($fetchResult['email']), $result['results'][0]['Email']);
    }

}
