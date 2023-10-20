<?php
namespace eGamings\WLC\Tests;

use eGamings\WLC\System;
use eGamings\WLC\Front;
use eGamings\WLC\Games;
use ReflectionClass;

class GamesTest extends BaseCase {
    private static $frontReflection;
    private $gamesListFile = '';

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
    }

    public function setUp(): void {
        $frontReflection = new ReflectionClass(Front::class);
        $front = $frontReflection->newInstanceWithoutConstructor();
        $fProperty = $frontReflection->getProperty('f');
        $fProperty->setAccessible(true);
        $fProperty->setValue($front);
        self::$frontReflection = $frontReflection;
        $_SESSION['FundistIDUser'] = 'Test';
        $this->gamesListFile = Games::$gamesListFile;
    }

    public function tearDown(): void {
        $frontReflection = new ReflectionClass(Front::class);
        $fProperty = $frontReflection->getProperty('f');
        $fProperty->setAccessible(true);
        $fProperty->setValue(null);
        self::$frontReflection = $frontReflection;
        unset($_SESSION['FundistIDUser']);
        Games::$gamesListFile = $this->gamesListFile;
    }

    public function testGetGamesList() {
        $mock = $this->getMockBuilder(System::class)->disableOriginalConstructor()
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->getMock();

        $iProp = (new \ReflectionClass(System::class))->getProperty('instance');
        $iProp->setAccessible(true);
        $iProp->setValue($mock);

        $mock->expects($this->exactly(1))->method('getApiTID')->willReturn('test_123');
        $mock->expects($this->exactly(1))->method('runFundistAPI')->willReturn(
            file_get_contents(_cfg('core') . DIRECTORY_SEPARATOR . Games::$gamesListFile)
        );

        $result = Games::getGamesList();
        $this->assertTrue(is_array($result), 'Check that result is array');
        $this->assertTrue(!empty($result['games']), 'Check that result games not empty');
        $iProp->setValue(null);
    }

    public function testGetGamesListFundistFail() {
        $mock = $this->getMockBuilder(System::class)->disableOriginalConstructor()
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->getMock();

        $iProp = (new \ReflectionClass(System::class))->getProperty('instance');
        $iProp->setAccessible(true);
        $iProp->setValue($mock);

        $mock->expects($this->exactly(1))->method('getApiTID')->willReturn('test_123');
        $mock->expects($this->exactly(1))->method('runFundistAPI')->willReturn(
            '999,test failure'
        );

        $result = Games::getGamesList();
        $this->assertFalse($result, 'Check that result is false');
        $iProp->setValue(null);
    }

    public function testGetGamesFullList() {
        $mock = $this->getMockBuilder(System::class)->disableOriginalConstructor()
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->getMock();

        $iProp = (new \ReflectionClass(System::class))->getProperty('instance');
        $iProp->setAccessible(true);
        $iProp->setValue($mock);

        $mock->expects($this->exactly(1))->method('getApiTID')->willReturn('test_123');
        $mock->expects($this->exactly(1))->method('runFundistAPI')->willReturn(
            file_get_contents(_cfg('core') . DIRECTORY_SEPARATOR . Games::$gamesListFile)
        );

        $result = Games::getGamesFullList();
        $this->assertTrue(is_array($result), 'Check that result is array');
        $this->assertTrue(!empty($result['games']), 'Check that result games not empty');
        $iProp->setValue(null);
    }

    public function testGetGamesFullListFundistFailure() {
        $mock = $this->getMockBuilder(System::class)->disableOriginalConstructor()
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->getMock();

        $iProp = (new \ReflectionClass(System::class))->getProperty('instance');
        $iProp->setAccessible(true);
        $iProp->setValue($mock);

        $mock->expects($this->exactly(1))->method('getApiTID')->willReturn('test_123');
        $mock->expects($this->exactly(1))->method('runFundistAPI')->willReturn('999,test failure');

        $result = Games::getGamesFullList();
        $this->assertFalse($result, 'Check that result is false');
        $iProp->setValue(null);
    }

    public function testGetGamesDataFundistFailure() {
        $mock = $this->getMockBuilder(System::class)->disableOriginalConstructor()
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->getMock();

        $iProp = (new \ReflectionClass(System::class))->getProperty('instance');
        $iProp->setAccessible(true);
        $iProp->setValue($mock);

        $mock->expects($this->exactly(1))->method('getApiTID')->willReturn('test_123');
        $mock->expects($this->exactly(1))->method('runFundistAPI')->willReturn('999,test failure');

        Games::$gamesListFile = 'gamesListFailure.json';
        $gamesReflection = new ReflectionClass(Games::class);
        $games = $gamesReflection->newInstanceWithoutConstructor();

        $result = $games->getGamesData();

        $this->assertFalse($result, 'Check that result is not array');
        $this->assertTrue(empty($result['games']), 'Check that result games is empty');
        $iProp->setValue(null);
    }

    public function testDropCache(): void {
        $mock = $this->getMockBuilder(System::class)->disableOriginalConstructor()
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->getMock();

        $iProp = (new \ReflectionClass(System::class))->getProperty('instance');
        $iProp->setAccessible(true);
        $iProp->setValue($mock);

        $mock->expects($this->exactly(2))->method('getApiTID')->willReturn('test_123');
        $mock->expects($this->exactly(2))->method('runFundistAPI')->willReturn('1,{}');

        $this->assertIsNotBool(Games::DropCache(false));
        $this->assertIsNotBool(Games::DropCache(true));
        $iProp->setValue(null);
    }

    public function testRedirect() {
        $mock = $this->getMockBuilder(Games::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock = new \stdClass();
        $userMock->userData = new \stdClass();

        $userProperty = self::$frontReflection->getProperty('_user');
        $userProperty->setAccessible(true);
        $userProperty->setValue(Front::getInstance(), $userMock);

        //$this->assertEquals($_SESSION['FundistIDUser'], $mock->fundist_uid(1), 'Should return FundistIDUser if this already set');

        $mock
            ->expects($this->exactly(2))
            ->method('getApiTID')
            ->willReturn('test_123');

        $mock
            ->expects($this->exactly(2))
            ->method('runFundistAPI')
            ->willReturn(
                '1,thisIsAuthRedirTest',
                '1,thisIsAnonRedirTest'
            );

        $userMock->userData->id = 123;
        $userMock->userData->api_password = 'test_123';

        $result = $mock->Redirect('998', 'ThisIsAuthTestGame');
        $this->assertEquals('thisIsAuthRedirTest', $result, 'Should return runFundistAPI response thisIsAuthRedirTest');
        
        $userMock->userData->id = null;
        $userMock->userData->api_password = null;

        $result = $mock->Redirect('998', 'ThisIsAnonymousTestGame');
        $this->assertEquals('thisIsAnonRedirTest', $result, 'Should return runFundistAPI response thisIsAnonRedirTest');

        $userProperty->setValue(Front::getInstance(), null);
    }

    public function testLaunchHTML() {
        $mock = $this->getMockBuilder(Games::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock = new \stdClass();
        $userMock->userData = new \stdClass();
        $userMock->userData->id = null;
        
        $userProperty = self::$frontReflection->getProperty('_user');
        $userProperty->setAccessible(true);
        $userProperty->setValue(Front::getInstance(), $userMock);
        
        //$this->assertEquals($_SESSION['FundistIDUser'], $mock->fundist_uid(1), 'Should return FundistIDUser if this already set');
        
        $mock
            ->expects($this->exactly(2))
            ->method('getApiTID')
            ->willReturn('test_123');
        
        $mock
            ->expects($this->exactly(2))
            ->method('runFundistAPI')
            ->willReturn(
                '1,thisIsAuthLaunchHtmlTest',
                '1,thisIsAnonLaunchHtmlTest'
            );
        
        $userMock->userData = new \stdClass();
        $userMock->userData->id = 123;
        $userMock->userData->api_password = 'test_123';
        
        $result = $mock->LaunchHTML('998', 'ThisIsAuthTestGame');
        $this->assertEquals('thisIsAuthLaunchHtmlTest', $result, 'Should return runFundistAPI response thisIsAuthRedirTest');
        
        $userMock->userData = new \stdClass();
        $userMock->userData->id = null;
        $userMock->userData->api_password = null;
        
        $result = $mock->LaunchHTML('998', 'ThisIsAnonymousTestGame', null, false, '', null, 'CAD');
        $this->assertEquals('thisIsAnonLaunchHtmlTest', $result, 'Should return runFundistAPI response thisIsAnonRedirTest');

        $userProperty->setValue(Front::getInstance(), null);
    }

    public function testGetGamesData() {
        $gamesReflection = new ReflectionClass(Games::class);
        $games = $gamesReflection->newInstanceWithoutConstructor();

        $result = $games->getGamesData();
        $this->assertTrue(is_object($result), 'Check that games data is object');
    }

    public function testGetGamesDataMobile() {
        $mock = $this->getMockBuilder(Games::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        _cfg('mobileDetected', true);
        $gamesData = $mock->getGamesData();
        _cfg('mobileDetected', null);
        $this->assertTrue(is_object($gamesData), 'Check that games data is object');
    }

    public function testGetGamesDataImages() {
        $mock = $this->getMockBuilder(Games::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();
        
        _cfg('gamesImages', ['3152' => 'test_image.jpg']);
        $gamesData = $mock->getGamesData();
        _cfg('gamesImages', null);
        $this->assertTrue(is_object($gamesData), 'Check games data is object');
        $this->assertTrue(is_array($gamesData->games), 'Check games data has games array');
        $this->assertTrue(!empty($gamesData->games), 'Check games data has games non empty array');
        $this->assertEquals($gamesData->games[2]['Image'], '/games/test_image.jpg', 'Check game image override');
    }

    public function testGetGamesDataCategory() {
        $mock = $this->getMockBuilder(Games::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $gamesData = $mock->getGamesData(0, 16);
        $this->assertTrue(is_object($gamesData), 'Check that games data is object');
        $this->assertTrue(is_array($gamesData->games), 'Check games data has games array');
        $this->assertTrue(!empty($gamesData->games), 'Check games data has games non empty array');
        $this->assertTrue(!empty($gamesData->games[1]), 'Check games data has game');
        $this->assertTrue(!empty($gamesData->games[1]['CategoryID']), 'Check games data has game category ids');
        $this->assertTrue(is_array($gamesData->games[1]['CategoryID']), 'Check games data has game category ids is array');
        $this->assertTrue(in_array(16, $gamesData->games[1]['CategoryID']), 'Check games data has game category ids');
    }

    public function testGetGamesDataCategoryTag() {
        $mock = $this->getMockBuilder(Games::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $gamesData = $mock->getGamesData(0, 'main');
        $this->assertTrue(is_object($gamesData), 'Check that games data is object');
        $this->assertTrue(is_array($gamesData->games), 'Check games data has games array');
        $this->assertTrue(!empty($gamesData->games), 'Check games data has games non empty array');
        $this->assertTrue(!empty($gamesData->games[1]), 'Check games data has game');
        $this->assertTrue(!empty($gamesData->games[1]['CategoryID']), 'Check games data has game category ids');
        $this->assertTrue(is_array($gamesData->games[1]['CategoryID']), 'Check games data has game category ids is array');
        $this->assertTrue(in_array(16, $gamesData->games[1]['CategoryID']), 'Check games data has game category ids');
    }

    public function testGetGamesDataMerchant() {
        $mock = $this->getMockBuilder(Games::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $gamesData = $mock->getGamesData(992);
        $this->assertTrue(is_object($gamesData), 'Check that games data is object');
        $this->assertTrue(is_array($gamesData->games), 'Check games data has games array');
        $this->assertTrue(!empty($gamesData->games), 'Check games data has games non empty array');
        $this->assertTrue(!empty($gamesData->games[1]), 'Check games data has game');
        $this->assertTrue(!empty($gamesData->games[1]['MerchantID']), 'Check games data has game merchant id');
        $this->assertEquals("992", $gamesData->games[1]['MerchantID'], 'Check games data has game merchant id netent');
    }

    public function testDropGamesSortingCache () {
        $this->assertNull(Games::dropGamesSortingCache());
    }

    public function testDropGamesSortsCache () {
        $this->assertNull(Games::dropGamesSortsCache());
    }
}
