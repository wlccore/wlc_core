<?php

namespace eGamings\WLC\Tests;

use eGamings\WLC\Affiliate;
use eGamings\WLC\Storage;
use eGamings\WLC\System;
use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Tests\DbMock;
use eGamings\WLC\Tests\DbConnectionMock;
use eGamings\WLC\Core;
use eGamings\WLC\User;
use eGamings\WLC\Front;
use eGamings\WLC\Utils;
use PasswordLib\PasswordLib;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;

class UserTest extends BaseCase
{
    private static $frontReflection;
    private static $SESSION_ORIGINAL = [];
    private static $CONFIG_ORIGINAL  = [];

    public static function setUpBeforeClass(): void
    {
        global $cfg;

        parent::setUpBeforeClass();

        textdomain('none'); // avoid responses localization

        $frontReflection = new ReflectionClass(Front::class);
        $front = $frontReflection->newInstanceWithoutConstructor();

        $fProperty = $frontReflection->getProperty('f');
        $fProperty->setAccessible(true);
        $fProperty->setValue($front);

        self::$frontReflection   = $frontReflection;
        self::$CONFIG_ORIGINAL   = $cfg;

        if (isset($_SESSION)) {
            self::$SESSION_ORIGINAL  = $_SESSION;
        }
    }

    public function setUp(): void {
        global $cfg;
        unset($cfg['PasswordSecureLevel']);
        _cfg('useFundistTemplate', 1);

        $reflectionCore = new \ReflectionClass(Core::class);
        $reflectionProperty = $reflectionCore->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(new CoreMock());
    }

    public function tearDown(): void {
        global $cfg;

        $reflectionCore = new \ReflectionClass(Core::class);
        $reflectionProperty = $reflectionCore->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(null);

        $_SESSION = self::$SESSION_ORIGINAL;
        $cfg = self::$CONFIG_ORIGINAL;
    }

    public function testSendPasswordRecovery(): void
    {
        $mock = $this->getMockBuilder(User::class)
            ->setMethods(['checkIfEmailExist'])
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects($this->any())->method('checkIfEmailExist')->will($this->onConsecutiveCalls('1;Done', '0;An error has occurred'));

        $this->assertEquals($mock->sendPasswordRecovery(1, [
            'email' => 'some@example.com'
        ]), [
            '1',
            'Done'
        ], "There is should be fine");

        $this->assertEquals($mock->sendPasswordRecovery(2, [
            'email' => 'some@example.co.uk'
        ]), [
            '0',
            'An error has occurred'
        ], "There is should be wrong password recovery");
    }

    public function testAssertAuth(): void {
        $mock = $this->getMockBuilder(User::class)
            ->setMethods(['isUser'])
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects($this->any())->method('isUser')->will($this->onConsecutiveCalls(false, true, true));

        $this->assertEquals($mock->assertAuth([]), '0;{"sessionExpired":"Session expired"}', "There should be no session");

        $mock->userData = new \stdClass();
        $mock->userData->password = "123";
        unset($_SERVER['TEST_RUN']);

        $this->assertTrue(strpos($mock->assertAuth([]), '0;') === 0, 'There should be no password');
        $this->assertEquals($mock->assertAuth(['currentPassword' => 42]), '0;{"currentPassword":"Current password is incorrect"}', "There should be wrong password");
        $this->assertEquals($mock->assertAuth([], true), '1', 'Do not check the api calls');
    }

    public function testFundistUid() {
        $mock = $this->getMockBuilder(User::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        $userProperty = self::$frontReflection->getProperty('_user');
        $userProperty->setAccessible(true);
        $userProperty->setValue(Front::getInstance(), $mock);

        $_SESSION['FundistIDUser'] = 'Test';
        $this->assertEquals($_SESSION['FundistIDUser'], $mock->fundist_uid(1), 'Should return FundistIDUser if this already set');

        $mock
            ->expects($this->exactly(3))
            ->method('getApiTID')
            ->willReturn('test_123');

        $mock
            ->expects($this->exactly(3))
            ->method('runFundistAPI')
            ->willReturn(
                '1,12345,test',
                '1,12345,-1',
                '17,12345'
            );
        unset($_SESSION['FundistIDUser']);
        $mock->userData = new \stdClass();
        $mock->userData->id = 123;
        $this->assertEquals('12345', $mock->fundist_uid(null), 'Should return runFundistAPI response');

        unset($_SESSION['FundistIDUser']);
        $this->assertEquals(0, $mock->fundist_uid(null, true), 'Should return false when check_status flag set true and fundist response less than 0');

        unset($_SESSION['FundistIDUser']);
        $this->assertFalse($mock->fundist_uid(null, true), 'Should return false when check_status flag set true and fundist response less than 0');
    }

    public function testCheckPasswordDefault() {
        $user = new User();
        $result = $user->checkPassword('test123');
        $this->assertTrue($result, 'Password default level check');
    }

    public function testCheckPassword() {
        $user = new User();
        _cfg('PasswordSecureLevel', 'low');
        $result = $user->checkPassword('test123');
        $this->assertTrue($result, 'Password low level check');
    }

    public function testCheckPasswordLowFailureUpperLower() {
        $user = new User();
        _cfg('PasswordSecureLevel', 'low');
        $result = $user->checkPassword('123#123');
        $this->assertFalse($result, 'Password low level check failure');
    }

    public function testCheckPasswordLowFailureUnicode() {
        $user = new User();
        _cfg('PasswordSecureLevel', 'low');
        $result = $user->checkPassword('абвг123');
        $this->assertFalse($result, 'Password low level check failure');
    }

    public function testCheckPasswordStrong() {
        $user = new User();
        _cfg('PasswordSecureLevel', 'strong');
        $result = $user->checkPassword('Ztest123$');
        $this->assertTrue($result, 'Password strong level check');
    }

    public function testCheckPasswordStrongFail() {
        $user = new User();
        _cfg('PasswordSecureLevel', 'strong');
        $result = $user->checkPassword('test123');
        $this->assertFalse($result, 'Password strong level check');
    }

    public function testCheckPasswordSuperStrong() {
        $user = new User();
        _cfg('PasswordSecureLevel', 'super-strong');
        $result = $user->checkPassword('Ztest123');
        $this->assertTrue($result, 'Password strong level check');
    }

    public function testCheckPasswordSuperStrongFail() {
        $user = new User();
        _cfg('PasswordSecureLevel', 'super-strong');
        $result = $user->checkPassword('test1');
        $this->assertFalse($result, 'Password strong level check');
    }

    public function testCheckPasswordCustom() {
        $user = new User();
        _cfg('PasswordSecureLevel', 'custom:lower,upper,digit,special,alpha');
        $result = $user->checkPassword('Ztest123$#');
        $this->assertTrue($result, 'Password strong level check');
    }

    public function testCheckPasswordCustomFailure() {
        $user = new User();
        _cfg('PasswordSecureLevel', 'custom:lower,upper,digit,fail');
        $result = $user->checkPassword('Ztest123');
        $this->assertFalse($result, 'Password strong level check');
    }

    public function testTestPassword() {
        $user = new User();
        $result = $user->testPassword('test123');
        $this->assertTrue($result, 'Password allowed symbols check');
    }

    public function testTestPasswordEmptyFailure() {
        $user = new User();
        $result = $user->testPassword('');
        $this->assertFalse($result, 'Password allowed symbols failure check');
    }

    public function testTestPasswordUnicodeFailure() {
        $user = new User();
        $result = $user->testPassword('абвгд123');
        $this->assertFalse($result, 'Password allowed symbols failure check');
    }

    public function testPasswordLib() {
        $this->assertInstanceOf(PasswordLib::class, User::passwordLib());
    }

    public function testPasswordHash() {
        $hash = 'asd712893asd';

        $mockPasswordLib = $this->getMockBuilder(PasswordLib::class)
            ->setMethods(['createPasswordHash'])
            ->getMock();

        $mockPasswordLib
            ->expects($this->once())
            ->method('createPasswordHash')
            ->willReturn($hash);

        $userReflection = new ReflectionClass(User::class);
        $user = $userReflection->newInstanceWithoutConstructor();
        $userReflection = new ReflectionClass($user);

        $libProperty = $userReflection->getProperty('_lib');
        $libProperty->setAccessible(true);
        $libProperty->setValue($mockPasswordLib);

        $this->assertEquals($hash, $user::passwordHash('test'));
    }

    public function testVerifyPassword() {
        $mockPasswordLib = $this->getMockBuilder(PasswordLib::class)
            ->setMethods(['verifyPasswordHash'])
            ->getMock();

        $mockPasswordLib
            ->expects($this->any())
            ->method('verifyPasswordHash')
            ->willReturn(true);

        $userReflection = new ReflectionClass(User::class);
        $user = $userReflection->newInstanceWithoutConstructor();
        $userReflection = new ReflectionClass($user);

        $libProperty = $userReflection->getProperty('_lib');
        $libProperty->setAccessible(true);
        $libProperty->setValue($mockPasswordLib);

        $this->assertTrue($user::verifyPassword('123', '$hash'));
    }

    public function testGeneratePassword() {
        $mock = $this->getMockBuilder(User::class)
            ->setMethodsExcept(['generatePassword'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertEquals(strlen($mock->generatePassword()), 8);
        $this->assertEquals(strlen($mock->generatePassword(1)), 1);
        $this->assertEquals(strlen($mock->generatePassword(3)), 3);

        $chars = str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?');

        $pass = $mock->generatePassword(10);
        $arPass = str_split($pass);

        $this->assertCount(10, $arPass);

        foreach ($arPass as $sym) {
            $this->assertTrue(in_array($sym, $chars));
        }
    }

    // Deprecated ?
    public function testTryConnectSocial() {
        $this->assertTrue(true, true, "Dummy risky test");
    }

    public function testSendSocialRegistrationCompletedEmail() {
        $this->assertTrue(true, true, "Dummy risky test");
    }

//    public function testRegisterFinishSocial() {
//        $mock = $this->getMockBuilder(User::class)
//            ->setMethodsExcept(['registerFinishSocial', 'generatePassword'])
//            ->setMethods(['checkIP', 'userDataCheck', 'passwordHash', 'registerDB', 'finishRegistration', 'sendSocialRegistrationCompletedEmail'])
//            ->disableOriginalConstructor()
//            ->getMock();
//
//        $userReflection = new ReflectionClass($mock);
//
//        $method = $userReflection->getMethod('registerDB');
//        $method->setAccessible(true);
//
//        $mock->method('checkIP')->will($this->onConsecutiveCalls('bad ip', 1, 1));
//        $mock->method('userDataCheck')->will($this->onConsecutiveCalls('test user data check', false, false));
//        $mock->method('registerDB')->willReturn(18537);
//
//        $mock->method('registerDB')->willReturn(18537);
//        $mock->method('sendSocialRegistrationCompletedEmail')->will($this->onConsecutiveCalls('mail false', true));
//        $mock->method('finishRegistration')->willReturn('');
//
//        $mock->expects($this->exactly(3))->method('checkIP');
//        $mock->expects($this->once())->method('finishRegistration')->with(18537, $this->any());
//
//        $this->assertEquals($mock->registerFinishSocial([]), 'bad ip');
//        $this->assertEquals($mock->registerFinishSocial([]), json_encode(['error' => 'test user data check']));
//        $this->assertEquals($mock->registerFinishSocial([]), json_encode(['error' => 'test user data check']));
//        $this->assertEquals($mock->registerFinishSocial([]), 'mail false');
//        $this->assertEquals($mock->registerFinishSocial([]), json_encode(['error' => 'mail false']));
//
//    }

    public function testFinishRegistration() {
        global $cfg;

        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->onlyMethods(['query'])
            ->disableOriginalConstructor()->getMock();

        DbMock::setConnection($conn);

        $queryResult = true;
        // Insert into api_request, api_logs, update api_logs
        $conn->expects($this->any())->method('query')->willReturn($queryResult);

        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['sendConfirmationEmail', 'confirmationCode'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->expects($this->any())->method('sendConfirmationEmail')->willReturn(true);
        $userMock->expects($this->any())->method('confirmationCode');

        $userMock->id = 1;
        $userMock->additional_fields = '{test}';
        $userMock->api_password = 'test-user-password';
        $userMock->password = 'test-user-password';
        $userMock->first_name = 'name';
        $userMock->last_name = 'surname';
        $userMock->login = 'user';
        $userMock->email = 'test@test.com';
        $userMock->currency = 'EUR';
        $userMock->country = 'country';
        $userMock->phone1 = '+371';
        $userMock->phone2 = '23456789';
        $userMock->reg_ip = '0.0.0.0';
        $userMock->reg_time = '2020-09-16 23:59:59';
        $userData = [
            'phone_verified' => 1,
            'userMock' => $userMock,
            'sendWelcomeEmail' => '1',
        ];

        $cfg['fastRegistrationWithoutBets'] = true;

        $_SERVER['TEST_RUN'] = true;
        $_SERVER['HTTP_X_UA_FINGERPRINT'] = '1q2w3e';
        $result = $userMock->finishRegistration(1, $userData);
        $this->assertIsObject($result);
        DbMock::setConnection(null);
    }

    public function testTurnYourselfOff() {
        $userMock = $this->getMockBuilder(User::class)
            ->setMethodsExcept(['turnYourselfOff'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->userData        = new \stdClass();
        $userMock->userData->id    = 32;
        $userMock->userData->email = 'test@test.com';
        $fundistAPIReturn = '1,DummyTrue';

        $userMock
            ->expects($this->exactly(1))
            ->method('getApiTID')
            ->willReturn('test_123');

        $userMock->method('runFundistAPI')
            ->will($this->returnCallback(function () use (&$fundistAPIReturn) {
                return $fundistAPIReturn;
            }));

        $conn = $this
            ->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();

        DbMock::setConnection($conn);

        $queryFetchResult = $this
            ->getMockBuilder('stdClass')
            ->setMethods(['fetch_object', 'free'])
            ->getMock();

        $queryFetchResult->num_rows = 1;

        $conn
            ->method('query')
            ->willReturn($queryFetchResult);

        $this->assertTrue($userMock->turnYourselfOff(), "Must be OK, enable status exists");

        DbMock::setConnection(null);
    }

    public function testTurnYourselfOffWrongDate()
    {
        $userMock = $this->getMockBuilder(User::class)
            ->setMethodsExcept(['turnYourselfOff'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->userData = new \stdClass();
        $userMock->userData->id = 32;
        $userMock->userData->email = 'test@test.com';

        $this->expectException(\eGamings\WLC\RestApi\ApiException::class);
        $this->expectExceptionMessage('Wrong dateTo param');

        $userMock->turnYourselfOff('2020-01-01');
    }

    public function testGetUsersTempByCode(): void {
        $conn = $this->getMockBuilder(DbConnectionMock::class)->setMethods(['query'])->disableOriginalConstructor()->getMock();
        DbMock::setConnection($conn);

        $queryResult = ['id' => 42];
        $queryFetchResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryFetchResult->num_rows = 1;

        $conn->expects($this->exactly(2))->method('query')->willReturn($queryFetchResult, $queryFetchResult);

        $queryFetchResult->expects($this->exactly(2))->method('fetch_object')->willReturn(
            (object) $queryResult,
            (object)[]
        );
        $queryFetchResult->expects($this->exactly(2))->method('free');

        $userMock = $this->getMockBuilder(User::class)->setMethodsExcept(['getUsersTempByCode'])->disableOriginalConstructor()->getMock();

        $this->assertIsObject($userMock->getUsersTempByCode('code'), "Must be an object");

        $queryResult = false;

        $this->assertIsObject($userMock->getUsersTempByCode('code'), "Must be an object");
    }

    public function testServiceApplyAndSendConfirmationEmail(): void {
        $userMock = $this->getMockBuilder(User::class)
            ->setMethodsExcept(['serviceApplyAndSendConfirmationEmail'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->expects($this->exactly(2))->method('sendConfirmationEmail')->willReturn(true);

        $userMock->userData = new \stdClass();
        $userMock->userData->id = 32;
        $userMock->userData->additional_fields = '{"email_code": "test_email_code"}';

        $this->assertTrue($userMock->serviceApplyAndSendConfirmationEmail([
            'password' => 'test_pass'
        ]));

        $userMock->userData->additional_fields = '{';
        $this->assertTrue($userMock->serviceApplyAndSendConfirmationEmail([
            'password' => 'test_pass'
        ]));
    }

    public function testGetUsersTempByEmail(): void {
        $conn = $this->getMockBuilder(DbConnectionMock::class)->setMethods(['query'])->disableOriginalConstructor()->getMock();
        DbMock::setConnection($conn);

        $queryResult = ['id' => 42];
        $queryFetchResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryFetchResult->num_rows = 1;

        $conn->expects($this->exactly(2))->method('query')->willReturn($queryFetchResult, $queryFetchResult);

        $queryFetchResult->expects($this->exactly(2))->method('fetch_object')->willReturn(
            (object) $queryResult,
            (object)[]
        );
        $queryFetchResult->expects($this->exactly(2))->method('free');

        $userMock = $this->getMockBuilder(User::class)->setMethodsExcept(['getUsersTempByEmail'])->disableOriginalConstructor()->getMock();

        $this->assertIsObject($userMock->getUsersTempByEmail('code'), "Must be an object");

        $queryResult = false;

        $this->assertIsObject($userMock->getUsersTempByEmail('code'), "Must be an object");
    }

    public function testAuthorizeUser(): void {
        $mock = $this->getMockBuilder(System::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        $reflectionSystem = new \ReflectionClass(System::class);
        $reflectionProperty = $reflectionSystem->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $userMock = $this->getMockBuilder(User::class)
            ->setMethods(['login'])
            ->disableOriginalConstructor()
            ->getMock();

        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])->disableOriginalConstructor()->getMock();
        DbMock::setConnection($conn);

        $queryResult = true;
        $queryFetchResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryFetchResult->num_rows = 1;

        $conn->expects($this->any())->method('query')->willReturn($queryResult, $queryFetchResult, $queryFetchResult);

        $queryFetchResult->expects($this->any())->method('fetch_object')->willReturn(
            (object)[],
            (object)[]
        );
        $queryFetchResult->expects($this->any())->method('free');

        $userMock->expects($this->any())->method('login')->willReturn(1);

        $mock->expects($this->any())->method('getApiTID')->willReturn('test_123');
        $mock->expects($this->any())->method('runFundistAPI')->willReturn('1,DummyTrue');

        $userMock->userData               = new \stdClass();
        $userMock->userData->id           = 32;
        $userMock->userData->api_password = 'passwd';

        $userData = new \stdClass();
        $userData->id     = '42';
        $userData->email  = 'test@test.com';
        $userData->phone1 = '89111424242';
        $userData->phone2 = '89222424242';

        $this->assertNull($userMock->authorizeUser($userData, true), "Must be null");

        $userData->login = '0x78897f3ba4b46e55aac74f3ad0ce0a56ea6ae5ef';
        $this->assertNull($userMock->authorizeUser($userData, true, User::LOGIN_TYPE_METAMASK), "Must be null");

        $this->assertNull($userMock->authorizeUser($userData, true, User::LOGIN_TYPE_SMS), "Must be null");
        DbMock::setConnection(null);
    }

    public function testConfirmationCode(): void {
        $userMock = $this
            ->getMockBuilder(User::class)
            ->onlyMethods(['authorizeUser'])
            ->disableOriginalConstructor()
            ->getMock();

        $conn = $this
            ->getMockBuilder(DbConnectionMock::class)
            ->onlyMethods(['query'])
            ->disableOriginalConstructor()->getMock();
        DbMock::setConnection($conn);

        $queryResult = true;
        $queryFetchResult = $this
            ->getMockBuilder('stdClass')
            ->setMethods(['fetch_object', 'free'])
            ->getMock();
        $queryFetchResult->num_rows = 1;

        $conn->expects($this->any())->method('query')->willReturn($queryResult, $queryFetchResult, $queryFetchResult);

        $queryFetchResult->expects($this->any())->method('fetch_object')->willReturn(
            (object)[],
            (object)[]
        );
        $queryFetchResult->expects($this->any())->method('free');

        $userMock->expects($this->any())->method('authorizeUser');

        $userMock->userData        = new \stdClass();
        $userMock->userData->email = 'test@test.com';

        $this->assertNull($userMock->confirmationCode(), "Must be null");
        DbMock::setConnection(null);
    }

    public function testProfileUpdate()
    {
        $mock = $this->getMockBuilder(System::class)
            ->onlyMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        $reflectionSystem = new \ReflectionClass(System::class);
        $reflectionProperty = $reflectionSystem->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $mock->expects($this->exactly(1))->method('getApiTID')->willReturn('test_123');
        $mock->expects($this->exactly(1))->method('runFundistAPI')->willReturn('1,DummyTrue');

        $userMock = $this->getMockBuilder(User::class)
             ->onlyMethods(['sendMailAfterUserUpdate'])
             ->disableOriginalConstructor()
             ->getMock();

        $userMock
            ->method('sendMailAfterUserUpdate')
            ->willReturn(true);

        $userMock->userData = new \stdClass();
        $userMock->userData->id = 32;
        $userMock->userData->password = 'passwd';

        $this->assertTrue(strpos($userMock->profileUpdate(
            [
                'email'=>'',
                'login'=>'',
                'firstName'=>'',
                'lastName'=>'',
                'Swift' =>'',
                'Iban' => '',
            ]
        ), '0;') === 0, 'Should fail with the error string');



        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])->disableOriginalConstructor()->getMock();
        DbMock::setConnection($conn);

        $queryResult = true;
        $queryFetchResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryFetchResult->num_rows = 1;

        $conn->expects($this->exactly(4))->method('query')->willReturn($queryResult, $queryFetchResult, $queryFetchResult, $queryFetchResult);

        $queryFetchResult->expects($this->exactly(2))->method('fetch_object')->willReturn(
            (object)[
                'id' => 32,
                'api_password' => 'test_api_password',
                'currency' => 'EUR',
                'first_name' => 'Name',
                'last_name' => 'Surname',
                'phone1' => '1',
                'phone2' => '123123123',
                'country' => 'TEST',
                'email' => 'test@test.com',
                'sex' => 'm',
                'birth_year' => 2001, 'birth_month' => 1, 'birth_day' => 1,
                'email_verified' => 1,
                'email_verified_datetime' => date('Y-m-d H:i:s'),
                'phone_verified' => 1
            ],
            (object)[]
        );
        $queryFetchResult->expects($this->exactly(2))->method('free');

        $userUpdateData = [
            'id' => 32,
            'pre_phone' => '371',
            'phoneVerify' => 1,
            'Swift' =>'',
            'Iban' => '',
        ];

        $result = $userMock->profileUpdate($userUpdateData, true);
        DbMock::setConnection(null);
        $reflectionProperty->setValue(null);
        $this->assertTrue($result);

        $userUpdateDataBadVersion = [
          'currentPassword' => 'qwerty',
          'password' => '42',
          'repeatPassword' => '',
          'email' => '',
          'login' => '',
          'main_phone' => '',
          'firstName' => '',
          'lastName' => '',
          'Swift' => '',
          'Iban' => '',
        ];
        $userMock->userData = new \stdClass();
        $userMock->userData->password = '42';
        $this->assertIsString($userMock->profileUpdate($userUpdateDataBadVersion, false), 'Should return a string');
    }

    public function testCronFinishRegistration() {
        _cfg('fastRegistration', false);

        $conn = $this->getMockBuilder(DbConnectionMock::class)
          ->setMethods(['query'])
          ->disableOriginalConstructor()->getMock();

        DbMock::setConnection($conn);

        $queryFetchResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryFetchResult->num_rows = 1;

        $conn->expects($this->any())->method('query')->willReturn($queryFetchResult, $queryFetchResult);

        $queryFetchResult->expects($this->any())->method('fetch_object')->willReturn(
            (object)[
                'reg_time' => "-1 week",
                'additional_fields' => '[]'
            ],
            (object)[]
        );
        $queryFetchResult->expects($this->any())->method('free');

        $result = User::getInstance()->cronFinishRegistration(1);

        DbMock::setConnection(null);
        $this->assertNull($result, 'A job was successfully completed!');
    }

    public function testProfileUpdateWithUserInfo() {
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['runFundistAPI', 'isUser', 'getApiTID'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->expects($this->any())->method('runFundistAPI')->willReturn("1");
        $userMock->expects($this->any())->method('isUser')->willReturn(true);
        $userMock->expects($this->any())->method('getApiTID')->willReturn(1);

        $password = User::passwordHash('Test1234!');

        $userMock->userData = new \stdClass();
        $userMock->userData->id = 32;
        $userMock->userData->email = 'testuser@egamings.com';
        $userMock->userData->login = 'user';
        $userMock->userData->password = $password;
        $userMock->userData->id = 1;
        $userMock->userData->api_password = $password;
        $userMock->userData->currency = 'RUB';

        $_SERVER['TEST_RUN'] = true;

        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()->getMock();

        DbMock::setConnection($conn);

        $queryResult = true;
        $queryFetchResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryFetchResult->num_rows = 1;

        $conn->expects($this->any())->method('query')->willReturn($queryResult, $queryFetchResult, $queryFetchResult);

        $queryFetchResult->expects($this->any())->method('fetch_object')->willReturn(
            (object)[
                'id' => 32,
                'api_password' => 'test_api_password',
                'currency' => 'EUR',
                'first_name' => 'Name',
                'last_name' => 'Surname',
                'phone1' => '1',
                'phone2' => '123123123',
                'country' => 'TEST',
                'email' => 'test@test.com',
                'sex' => 'm',
                'birth_year' => 2001, 'birth_month' => 1, 'birth_day' => 1,
                'email_verified' => 1,
                'email_verified_datetime' => date('Y-m-d H:i:s'),
                'phone_verified' => 1,
                'additional_fields' => json_encode([
                    'sendEmail' => true,
                    'sendSMS' => true
                ])
            ],
            (object)[]
        );
        $queryFetchResult->expects($this->any())->method('free');

        $result = $userMock->profileUpdate([
            'id' => 32,
            'login' => 'user',
            'email' => 'testuser@egamings.com',
            'password' => '',
            'currentPassword' => '123456qWe',
            'repeatPassword' => '',
            'firstName' => 'firstName',
            'lastName' => 'lastName',
            'pre_phone' => '',
            'main_phone' => '',
            'country' => 'rus',
            'birth_day' => '11',
            'birth_month' => '08',
            'birth_year' => '1994',
            'sex' => 'm',
            'Swift' => '',
            'Iban' => '',
            'affiliateClickId' => '',
            'affiliateClickIdOld' => '',
            'additional_fields' => [],
            'ext_profile' => [],
        ], false);

        unset($_SERVER['TEST_RUN']);
        DbMock::setConnection(null);
        $this->assertTrue($result);
    }

    public function testProfileAdditionaUpdateNoUser() {
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['isUser'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->method('isUser')->willReturn(false);
        $userData = [
            'first_name' => 'name',
            'last_name' => 'surname'
        ];
        $result = $userMock->profileAdditionalUpdate($userData);

        $this->assertEquals($result, '0;{"sessionExpired":"Session expired"}');
    }

    public function testProfileAdditionaUpdateData() {
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['isUser', 'sendMailAfterUserUpdate', 'assertAuth'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->method('isUser')->willReturn(true);
        $userMock->method('assertAuth')->will($this->onConsecutiveCalls('0;Test auth error', '1', '1'));
        $userMock->method('sendMailAfterUserUpdate')->willReturn(true);

        $userMock->userData = new \stdClass();
        $userMock->userData->id = 1;
        $userMock->userData->api_password = 'testpassword';
        $userMock->userData->currency = 'EUR';
        $userMock->userData->email = 'test@test.com';
        $userMock->userData->additional_fields = '{}';
        $userMock->userData->login = 'login';

        $userData = [
            'first_name' => 'name',
            'last_name' => 'surname',
            'country' => 'lva',
            'city' => 'City',
            'address' => 'Address',
            'BankName' => 'BankName',
            'IDNumber' => '11223344',
            "currentPassword" => "123",
            "birth_day" => 1,
            "birth_month" => 2,
            "birth_year" => 2003,
            "sendSMS" => true,
            "sendEmail" => true,
            "login" => 'login',
            "additional_fields" => [],
            "ext_profile" => ['pep' => true],
        ];

        $reflectionSystem = new \ReflectionClass(System::class);
        $reflectionProperty = $reflectionSystem->getProperty('instance');
        $reflectionProperty->setAccessible(true);

        $systemMock = $this->getMockBuilder(System::class)->setMethods(['getApiTID', 'runFundistAPI'])->disableOriginalConstructor()->getMock();
        $systemMock->method('getApiTID')->willReturn('test-tid-1');
        $systemMock->method('runFundistAPI')->willReturn('1;Saved');
        _cfg('useFundistTemplate', 1);
        $reflectionProperty->setValue($systemMock);

        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();

        DbMock::setConnection($conn);

        $queryResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryResult->num_rows = 1;

        $userRow = json_decode('{
            "id": 1,
            "api_password": "fundistApiPassword",
            "password": "testpassword",
            "first_name": "",
            "last_name": "",
            "login": "test user",
            "email": "test@test.com",
            "phone1": "",
            "phone2": "",
            "country": "lva",
            "currency": "EUR",
            "additional_fields": "{\"sendEmail\": true, \"sendSMS\": true}",
            "ext_profile": "{\"nick\":\"IDDQD\",\"dontSendSms\":true}",

            "birth_day": 1,
            "birth_month": 2,
            "birth_year": 2003
        }');

        $conn
            ->expects($this->any())
            ->method('query')
            ->willReturn($queryResult);

        $queryResult
            ->expects($this->any())
            ->method('fetch_object')
            ->willReturn($userRow);

        $queryResult
            ->expects($this->any())
            ->method('free')
            ->willReturn(true);


        $this->assertIsString($userMock->profileAdditionalUpdate($userData), "Must be an auth error message");
        $this->assertIsString($userMock->profileAdditionalUpdate($userData), "Must be an error message");

        $userData['sex'] = 'm';
        $result = $userMock->profileAdditionalUpdate($userData);
        $this->assertTrue($result);

        $userMock->userData->additional_fields = '{"type":"metamask"}';
        unset($userData['currentPassword']);
        $result = $userMock->profileAdditionalUpdate($userData);
        $this->assertIsString($result);

        $reflectionProperty->setValue(null);
        DbMock::setConnection(null);
    }

    public function testProfileUpdateBonusRestrictions()
    {
        _cfg('checkPassOnUpdate', 0);
        $data = [
            'id' => 4222,
            'birth_day' => '4',
            'birth_month' => '10',
            'birth_year' => '1990',
            'api_password' => '',
            'currency' => 'EUR',
            'firstName' => 'Name',
            'lastName' => 'Lastname',
            'pre_phone' => '',
            'main_phone' => '',
            'sex' => 'M',
            'email' => 'user@egamings.com',
            'login' => 'user',
            'country' => 'ru',
            'password' => '',
            'currentPassword' =>'',
            'repeatPassword' => '',
            'RestrictCasinoBonuses' => '1',
            'RestrictSportBonuses' => '1',
            'state' => 'US-OK',
            'Swift' => '',
            'Iban' => '',
            'affiliateClickId' => '',
            'affiliateClickIdOld' => '',
            'additional_fields' => '{}',
        ];

        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['getApiTID', 'runFundistAPI', 'assertAuth', 'profileAdditionalFieldsUpdate'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->expects($this->any())->method('getApiTID')->willReturn('test_123');
        $userMock->expects($this->any())->method('assertAuth')->willReturn(true);
        $userMock->expects($this->any())->method('runFundistAPI')->willReturn('1,DummyTrue');
        $userMock->expects($this->any())->method('profileAdditionalFieldsUpdate')->willReturn([
            'sendEmail' => true,
            'sendSMS' => true,
            'Swift' => '',
            'Iban' => '',
            'isApiCall' => false,
            'dontSendEmail' => false,
            'dontSendSms' => false,
        ]);

        $userMock->userData = new \stdClass();
        foreach ($data as $k => $v) {
            $userMock->userData->{$k} = $v;
        }

        $conn = $this
            ->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();

        DbMock::setConnection($conn);

        $queryFetchResult = $this
            ->getMockBuilder('stdClass')
            ->setMethods(['fetch_object', 'free'])
            ->getMock();

        $queryFetchResult->num_rows = 1;

        $conn
            ->method('query')
            ->willReturn($queryFetchResult);

        $result = $userMock->profileUpdate($data);
        $this->assertTrue($result);

        DbMock::setConnection(null);
    }

    public function testSetPhoneVerified()
    {
        $userMock = $this->getMockBuilder(User::class)
                         ->setMethodsExcept(['setPhoneVerified'])
                         ->disableOriginalConstructor()
                         ->getMock();

        $conn = $this->getMockBuilder(DbConnectionMock::class)
                     ->setMethods(['query'])
                     ->disableOriginalConstructor()
                     ->getMock();
        DbMock::setConnection($conn);

        $queryResult = '1';
        $queryFetchResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $row = new \stdClass();
        $row->additional_fields = '{}';
        $queryFetchResult->expects($this->any())->method('fetch_object')->willReturn($row);
        $conn
            ->expects($this->exactly(3))
            ->method('query')
            ->willReturn($queryFetchResult, ['phone_verified'=>1], $queryResult);

        $userMock->userData     = new \stdClass();
        $userMock->userData->id = 32;

        $result = $userMock->setPhoneVerified($userMock->userData->id, 1, true);
        DbMock::setConnection(null);
        $this->assertEquals($result, $queryResult);
    }

    public function testVerifyUser()
    {
        $mock = $this->getMockBuilder(User::class)
            ->onlyMethods(['isUser', 'getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        $mock
            ->expects($this->any())
            ->method('isUser')
            ->willReturn(true);

        $mock
            ->expects($this->exactly(1))
            ->method('getApiTID')
            ->willReturn('test_123');

        $mock
            ->expects($this->exactly(1))
            ->method('runFundistAPI')
            ->willReturn('1,Phone verified');

        $mock->userData     = new \stdClass();
        $mock->userData->id = 32;
        $mock->userData->api_password = 'passwd';

        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        DbMock::setConnection($conn);

        $queryResult = '1';
        $conn
            ->expects($this->any())
            ->method('query')
            ->willReturn($queryResult);

        $result = $mock->verifyUser('+7', '9111111111');
        DbMock::setConnection(null);
        $this->assertEquals($result, ['1','Phone verified']);
    }

    public function testPrepareAndSendRegistrationToFundistAPI()
    {
        $mock = $this->getMockBuilder(User::class)
            ->onlyMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        $mock
            ->expects($this->exactly(1))
            ->method('getApiTID')
            ->willReturn('test_123');

        $mock
            ->expects($this->exactly(1))
            ->method('runFundistAPI')
            ->willReturn('123');

        $mock->userData = new \stdClass();
        $mock->userData->id = 123;
        $mock->userData->currency = 'EUR';
        $mock->userData->api_password = 'qwerty132132342';
        $mock->userData->first_name = 'aassdd';
        $mock->userData->last_name = 'aassxx';
        $mock->userData->sex = 'm';
        $mock->userData->reg_ip = '127.0.0.1';
        $mock->userData->email = 'qq@ww.ee';
        $mock->userData->login = 'login';
        $mock->userData->additional_fields = '{"finger_print":"1q2w3e"}';


        $result = $this->invokeMethod($mock, 'prepareAndSendRegistrationToFundistAPI', [$mock->userData]);
        $this->assertEquals($result, '123');

    }

    public function testLogin() {
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['getApiTID', 'runFundistAPI', 'logUserData'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockPasswordLib = $this->getMockBuilder(PasswordLib::class)
            ->setMethods(['verifyPasswordHash'])
            ->getMock();

        $mockPasswordLib->expects($this->exactly(2))
            ->method('verifyPasswordHash')
            ->willReturn(true);

        $userReflection = new ReflectionClass(User::class);
        $user = $userReflection->newInstanceWithoutConstructor();
        $userReflection = new ReflectionClass($user);

        $libProperty = $userReflection->getProperty('_lib');
        $libProperty->setAccessible(true);
        $libProperty->setValue($mockPasswordLib);

        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        DbMock::setConnection($conn);

        $queryResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryResult->num_rows = 1;

        $fetchResult = new \stdClass();
        $fetchResult->id = 1;
        $fetchResult->password = User::passwordHash('test123');
        $fetchResult->currency = 'EUR';
        $fetchResult->additional_fields = "[]";
        $fetchResult->status = 1;
        $fetchResult->api_password = 'this-is-test-pass';

        $queryResult->fetch_object = function() use ($fetchResult) {
            return $fetchResult;
        };

        $queryResult
            ->method('fetch_object')
            ->will($this->onConsecutiveCalls($fetchResult, null, $fetchResult, null));


        $conn->expects($this->exactly(2))->method('query')->willReturn($queryResult);
        $queryResult->expects($this->exactly(4))->method('fetch_object')->willReturn($fetchResult);
        $queryResult->expects($this->exactly(2))->method('free')->willReturn(true);

        $userMock->expects($this->exactly(4))->method('getApiTID')
            ->will($this->onConsecutiveCalls('test-tid-1', 'test-tid-2', 'test-tid-3', 'test-tid-4'));
        $userMock->expects($this->exactly(4))->method('runFundistAPI')
            ->will($this->onConsecutiveCalls('1,{"Category":"Vip"}', '1,/trading/proxy', '1,/trading/proxy', '1,/trading/proxy'));
        $userMock->expects($this->exactly(2))->method('logUserData')->willReturn(true);

        $storageMock = $this->getMockBuilder(Storage::class)
            ->setMethods(['setRecord'])
            ->disableOriginalConstructor()
            ->getMock();
        $storageMock->expects($this->exactly(1))->method('setRecord')->willReturn(new \stdClass());

        $userProperty = self::$frontReflection->getProperty('_storage');
        $userProperty->setAccessible(true);
        $userProperty->setValue(Front::getInstance(), $storageMock);

        _cfg('enableSpotOption', 1);
        _cfg('enableAuthUserId', true);

        $response = $userMock->login([
            'login' => 'test@test.com',
            'pass' => 'test123',
            'remember' => 1
        ]);
        $result = explode(';', $response, 2);
        $this->assertTrue(!empty($result), 'Non empty result');
        $this->assertEquals($result[0], '1', 'Response indicator set to 1');

        _cfg('enableFastTrackAuthentication', true);
        $response = $userMock->login([
            'login' => 'test@test.com',
            'pass' => 'test123',
            'remember' => 1
        ]);
        $result = explode(';', $response, 3);
        $this->assertTrue(!empty($result), 'Non empty result');
        $this->assertEquals($result[0], '1', 'Response indicator set to 1');
        $this->assertIsString($result[2]);


        _cfg('enableSpotOption', 0);
        _cfg('enableFastTrackAuthentication', false);
        $libProperty->setValue(null);
        DbMock::setConnection(null);
    }

    public function testLogout(): void
    {
        global $cfg;

        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['getApiTID', 'runFundistAPI', 'logUserData'])
            ->disableOriginalConstructor()
            ->getMock();

        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        DbMock::setConnection($conn);

        $queryResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryResult->num_rows = 1;

        $fetchResult = new \stdClass();
        $fetchResult->id = 1;
        $fetchResult->password = 'test123';
        $fetchResult->currency = 'EUR';
        $fetchResult->status = 1;
        $fetchResult->api_password = 'this-is-test-pass';

        $userMock->userData = new \stdClass();
        $userMock->userData->id = '42';
        $userMock->userData->api_password = 'api_password';

        $queryResult->fetch_object = function() use ($fetchResult) {
            return $fetchResult;
        };

        $conn->expects($this->any())->method('query')->willReturn($queryResult);
        $queryResult->expects($this->any())->method('fetch_object')->willReturn($fetchResult);
        $queryResult->expects($this->any())->method('free')->willReturn(true);

        $userMock->expects($this->exactly(2))->method('getApiTID')
            ->will($this->onConsecutiveCalls('test-tid-1', 'test-tid-2'));
        $userMock->expects($this->exactly(2))->method('runFundistAPI')
            ->will($this->onConsecutiveCalls('1,User was logged out', '42,Black magic happened'));
        $userMock->expects($this->exactly(1))->method('logUserData')->willReturn(true);

        $_SESSION['user'] = 'userData';
        $cfg['isFuncoreLogoutRequired'] = true;

        $core = Core::getInstance();
        $core->setSessionStartedFlag(false);

        $this->assertTrue($userMock->logout());
        $this->assertTrue($userMock->logout());
    }

    public function testLoginHookAfter() {
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['getApiTID', 'runFundistAPI', 'logUserData'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockPasswordLib = $this->getMockBuilder(PasswordLib::class)
            ->setMethods(['verifyPasswordHash'])
            ->getMock();

        $mockPasswordLib->expects($this->once())
            ->method('verifyPasswordHash')
            ->willReturn(true);

        $userReflectionBase = new ReflectionClass(User::class);
        $user = $userReflectionBase->newInstanceWithoutConstructor();
        $userReflection = new ReflectionClass($user);

        $libProperty = $userReflection->getProperty('_lib');
        $libProperty->setAccessible(true);
        $libProperty->setValue($mockPasswordLib);

        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        DbMock::setConnection($conn);

        $queryResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryResult->num_rows = 1;

        $fetchResult = new \stdClass();
        $fetchResult->id = 1;
        $fetchResult->password = User::passwordHash('test123');
        $fetchResult->currency = 'EUR';
        $fetchResult->additional_fields = "[]";
        $fetchResult->status = 1;
        $fetchResult->api_password = 'this-is-test-pass';

        $queryResult
            ->method('fetch_object')
            ->will($this->onConsecutiveCalls($fetchResult, null, $fetchResult, null));

        $conn->expects($this->exactly(1))->method('query')->willReturn($queryResult);
        $queryResult->expects($this->exactly(2))->method('fetch_object')->willReturn($fetchResult);
        $queryResult->expects($this->exactly(1))->method('free')->willReturn(true);

        $userMock->expects($this->exactly(1))->method('getApiTID')->willReturn('test-tid-1');
        $userMock->expects($this->exactly(1))->method('runFundistAPI')->willReturn('1,{"Category":"Vip"}');
        $userMock->expects($this->exactly(0))->method('logUserData');

        _cfg('hooks', ['user:login:after' => [function() {
                return false;
            }]
        ]);

        $response = $userMock->login(['login' => 'test@test.com', 'pass' => 'test123']);
        $result = explode(';', $response, 2);
        $this->assertTrue(!empty($result), 'Non empty result');
        $this->assertEquals($result[0], '0', 'Response indicator set to 0');

        $libProperty->setValue(null);
        DbMock::setConnection(null);
        _cfg('hooks', []);
    }

    public function testLoginFail() {
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['getApiTID', 'runFundistAPI', 'logUserData'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockPasswordLib = $this->getMockBuilder(PasswordLib::class)
            ->setMethods(['verifyPasswordHash'])
            ->getMock();

        $mockPasswordLib->expects($this->once())
            ->method('verifyPasswordHash')
            ->willReturn(true);

        $userReflectionBase = new ReflectionClass(User::class);
        $user = $userReflectionBase->newInstanceWithoutConstructor();
        $userReflection = new ReflectionClass($user);

        $libProperty = $userReflection->getProperty('_lib');
        $libProperty->setAccessible(true);
        $libProperty->setValue($mockPasswordLib);

        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        DbMock::setConnection($conn);

        $queryResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryResult->num_rows = 1;

        $fetchResult = new \stdClass();
        $fetchResult->id = 1;
        $fetchResult->password = User::passwordHash('test123');
        $fetchResult->currency = 'EUR';
        $fetchResult->additional_fields = "[]";
        $fetchResult->status = 1;
        $fetchResult->api_password = 'this-is-test-pass';

        $queryResult
            ->method('fetch_object')
            ->will($this->onConsecutiveCalls($fetchResult, null, $fetchResult, null));

        $conn->expects($this->exactly(1))->method('query')->willReturn($queryResult);
        $queryResult->expects($this->exactly(2))->method('fetch_object')->willReturn($fetchResult);
        $queryResult->expects($this->exactly(1))->method('free')->willReturn(true);

        $userMock->expects($this->exactly(1))->method('getApiTID')->willReturn('test-tid-1');
        $userMock->expects($this->exactly(1))->method('runFundistAPI')->willReturn('2,test failure');
        $userMock->expects($this->exactly(1))->method('logUserData')->willReturn(true);

        $response = $userMock->login(['login' => 'test@test.com', 'pass' => 'test123']);
        $result = explode(';', $response, 2);
        $this->assertTrue(!empty($result), 'Non empty result');
        $this->assertEquals($result[0], '2', 'Response indicator set to 2');
        $this->assertTrue(!empty($result[1]), 'Non empty result error text');

        $libProperty->setValue(null);
        DbMock::setConnection(null);
    }

    public function testGetInfo()
    {
        global $cfg;

        $mock = $this->getMockBuilder(User::class)
            ->setMethods(['getApiTID','runFundistAPI','getProfileData'])
            ->disableOriginalConstructor()
            ->getMock();

        $iProp = (new \ReflectionClass(System::class))->getProperty('instance');
        $iProp->setAccessible(true);
        $iProp->setValue($mock);

        $mock
            ->expects($this->any())
            ->method('getApiTID')
            ->willReturn('test_123');

        $mock
            ->expects($this->any())
            ->method('getProfileData')
            ->willReturn('["first_name" => "First Name"]');

        $fundistAPI = '1,{"IDUser":42,"Balance":10,"availableWithdraw":10,"Category":1,"CategoryID":1,"Freerounds":[],"Loyalty":{"IDUser": 42},"OpenPositions":"42", "Pincode": 42, "GlobalBlocked": true}';
        $mock
            ->expects($this->any())
            ->method('runFundistAPI')
            ->will($this->returnCallback(function() use (&$fundistAPI): string {
                return $fundistAPI;
            }));

        $cfg['getFullProfile'] = true;

        $conn = $this
            ->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();

        DbMock::setConnection($conn);

        $queryFetchResult = $this
            ->getMockBuilder('stdClass')
            ->setMethods(['fetch_object', 'free'])
            ->getMock();

        $queryFetchResult->num_rows = 1;

        $conn
            ->method('query')
            ->willReturn($queryFetchResult);

        $user = $mock::getInfo('test@test.com');
        $this->assertTrue(isset($user['freerounds']),'No freerounds given');

        $fundistAPI = '42,{"Test not found": true}';
        $this->assertTrue(isset($mock::getInfo('test@test.com')['freerounds']),'No freerounds given');

        DbMock::setConnection(null);
    }

    public function testUserRegisterDB() {
        $conn = $this->getMockBuilder(DbConnectionMock::class)->setMethods(['query'])
            ->disableOriginalConstructor()->getMock();
        DbMock::setConnection($conn);

        $queryResult = true;
        // Insert into api_request, api_logs, update api_logs
        $conn->expects($this->exactly(1))->method('query')->willReturn($queryResult);
        $conn->insert_id = 1;

        $userMock = $this->getMockBuilder(User::class)
            ->setMethodsExcept(['registerDB'])
            ->disableOriginalConstructor()
            ->getMock();

        $userData = [
            'firstName' => 'name',
            'lastName' => 'surname',
            'password' => 'test-user-password'
        ];

        $result = $userMock->registerDB($userData);
        $this->assertEquals($result, $conn->insert_id);
        DbMock::setConnection(null);
    }

    /**
     * @runInSeparateProcess
     */
    public function testUserRegister() {
        global $cfg;

        $systemMock = $this->getMockBuilder(System::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        $reflectionSystem = new \ReflectionClass(System::class);
        $reflectionProperty = $reflectionSystem->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($systemMock);

        $systemMock->expects($this->any())->method('getApiTID')->willReturn('test_123');
        $systemMock->expects($this->any())->method('runFundistAPI')->willReturn('1,DummyTrue');

        $conn = $this->getMockBuilder(DbConnectionMock::class)->setMethods(['query'])
            ->disableOriginalConstructor()->getMock();
        DbMock::setConnection($conn);

        $queryResult = true;
        // Insert into api_request, api_logs, update api_logs
        $conn->expects($this->any())->method('query')->willReturn($queryResult);
        $conn->insert_id = 1;

        $reflectionAff = new \ReflectionClass(Affiliate::class);
        $reflectionAffProperty = $reflectionAff->getProperty('_aff_cookie');
        $reflectionAffProperty->setAccessible(true);
        $reflectionAffProperty->setValue('system=faff&id=1&data=&params=faff%3D1');

        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['checkIP','runLoyaltyAPI', 'sendConfirmationEmail', 'finishRegistration'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->expects($this->any())->method('checkIP')->willReturn(1);
        $userMock->expects($this->any())->method('sendConfirmationEmail')->willReturn(true);
        $userMock->expects($this->any())->method('finishRegistration')->willReturn(true);
        $userMock
            ->expects($this->exactly(3))
            ->method('runLoyaltyAPI')
            ->willReturn(
                json_encode([
                    'isValid' => true,
                    'AffiliateSystem' => 'faff',
                    'AffiliateUrl' => 1
                ]), json_encode([
                    'isValid' => true,
                    'AffiliateSystem' => 'affilka',
                    'AffiliateUrl' => '{"Url":"email=test@softgamings.com&name=New aff&strategy=Test RevShare&code=devaff-2f4799d3"}'
                ])
            );

        $userData = [
            'firstName' => 'name',
            'lastName' => 'surname',
            'email' => 'test@wlc.com',
            'password' => 'TestPass123W#',
            'currency' => 'EUR',
            'country' => 'rus',
            'reg_promo' => 'TESTPROM0CODE1',
        ];

        _cfg('setAffiliateCookieByPromoCode', true);

        $result = $userMock->register($userData);
        $this->assertTrue($result, 'User succesfuly registered');

        $result = $userMock->register($userData);
        $this->assertTrue($result, 'User succesfuly registered');

        _cfg('setAffiliateCookieByPromoCode', false);
        $userData['social_uid'] = '42';
        $cfg['fastRegistration'] = true;
        $cfg['fastRegistrationWithoutBets'] = true;
        $cfg['useFundistTemplate'] = 1;

        $result = $userMock->register($userData, false, true);

        $this->assertIsNotObject($result, 'User succesfuly registered via social nets');

        DbMock::setConnection(null);
        $reflectionProperty->setValue(null);
        $reflectionAffProperty->setValue(null);
    }

    public function testSendConfirmationEmail(): void {
        global $cfg;

        $systemMock = $this->getMockBuilder(System::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        $reflectionSystem = new \ReflectionClass(System::class);
        $reflectionProperty = $reflectionSystem->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($systemMock);

        $systemMock->expects($this->any())->method('getApiTID')->willReturn('test_123');
        $systemMock->expects($this->any())->method('runFundistAPI')->willReturn('1,DummyTrue');

        $conn = $this->getMockBuilder(DbConnectionMock::class)->setMethods(['query'])
            ->disableOriginalConstructor()->getMock();
        DbMock::setConnection($conn);

        $queryResult = true;
        // Insert into api_request, api_logs, update api_logs
        $conn->expects($this->any())->method('query')->willReturn($queryResult);
        $conn->insert_id = 1;

        $data = [
            'firstName' => 'fName',
            'last_name' => 'lName',
            'original_password' => 'oPass',
            'password' => 'pass',
            'email' => 'email',
            'code' => 'code',
            'email_code' => 'email_code',
            'currency' => 'currency'
        ];

        $cfg['useFundistTemplate'] = 1;

        $userMock = $this->getMockBuilder(User::class)
            ->setMethods(['checkIP'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->expects($this->any())->method('checkIP')->willReturn(1);

        $result = $userMock->sendConfirmationEmail($data);
        $this->assertIsBool($result, "Must be a bool");

        DbMock::setConnection(null);
        $reflectionProperty->setValue(null);
    }

    public function testUserRegisterFailure()
    {
        $systemMock = $this->getMockBuilder(System::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        $reflectionSystem = new \ReflectionClass(System::class);
        $reflectionProperty = $reflectionSystem->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($systemMock);

        $systemMock->expects($this->any())->method('getApiTID')->willReturn('test_123');
        $systemMock->expects($this->any())->method('runFundistAPI')->willReturn('0, Error sending email\'');

        $conn = $this->getMockBuilder(DbConnectionMock::class)->setMethods(['query'])
            ->disableOriginalConstructor()->getMock();
        DbMock::setConnection($conn);

        $queryResult = true;
        // Insert into api_request, api_logs, update api_logs
        $conn->expects($this->any())->method('query')->willReturn($queryResult);
        $conn->insert_id = 1;

        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['checkIP', 'sendConfirmationEmail'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->expects($this->exactly(1))->method('checkIP')->willReturn(1);
        $userMock->expects($this->exactly(1))->method('sendConfirmationEmail')->willReturn(true);

        $data = [
            'login' => '180_45571111',
            'email' => 'asd@email.com',
            'password' => 'Test123!',
            'currency' => 'EUR',

        ];

        _cfg('fastRegistration', 0);
        _cfg('useFundistTemplate', 0);
        _cfg('fastRegistrationWithoutBets', 0);
        _cfg('enqueue_emails', 0);
        $result = $userMock->register($data);
        $this->assertTrue($result);

        DbMock::setConnection(null);
        $reflectionProperty->setValue(null);
    }

    public function testLoginUserNotFound() {
        $userMock = $this->getMockBuilder(User::class)
            ->setMethodsExcept(['login'])
            ->disableOriginalConstructor()
            ->getMock();

        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        DbMock::setConnection($conn);

        $queryResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryResult->num_rows = 0;

        $queryResult->fetch_object = function() {
            return false;
        };

        $conn->expects($this->exactly(2))->method('query')->willReturn($queryResult);
        $queryResult->expects($this->exactly(2))->method('fetch_object')->willReturn(false);
        $queryResult->expects($this->exactly(2))->method('free')->willReturn(true);


        $response = $userMock->login([
            'login' => 'test@test.com',
            'pass' => 'test123',
            'remember' => 1
        ]);
        $result = explode(';', $response, 2);
        $this->assertTrue(!empty($result), 'Non empty result');
        $this->assertEquals($result[1], 'authorization_error');

        DbMock::setConnection(null);
    }

    public function testLoginWrongPassword()
    {
        $userMock = $this->getMockBuilder(User::class)
            ->setMethodsExcept(['login'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockPasswordLib = $this->getMockBuilder(PasswordLib::class)
            ->setMethods(['verifyPasswordHash'])
            ->getMock();

        $mockPasswordLib->expects($this->once())
            ->method('verifyPasswordHash')
            ->willReturn(false);

        $userReflection = new ReflectionClass(User::class);
        $user = $userReflection->newInstanceWithoutConstructor();
        $userReflection = new ReflectionClass($user);

        $libProperty = $userReflection->getProperty('_lib');
        $libProperty->setAccessible(true);
        $libProperty->setValue($mockPasswordLib);

        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        DbMock::setConnection($conn);

        $queryResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryResult->num_rows = 1;

        $fetchResult = new \stdClass();
        $fetchResult->id = 1;
        $fetchResult->password = User::passwordHash('test123');
        $fetchResult->additional_fields = "[]";
        $fetchResult->status = 1;

        $queryResult
            ->method('fetch_object')
            ->will($this->onConsecutiveCalls($fetchResult, null, $fetchResult, null));

        $conn->expects($this->exactly(1))->method('query')->willReturn($queryResult);
        $queryResult->expects($this->exactly(2))->method('fetch_object')->willReturn($fetchResult);
        $queryResult->expects($this->exactly(1))->method('free')->willReturn(true);

        $response = $userMock->login([
            'login' => 'test@test.com',
            'pass' => 'test123',
            'remember' => 1
        ]);
        $result = explode(';', $response, 2);
        $this->assertTrue(!empty($result), 'Non empty result');
        $this->assertEquals($result[1], 'authorization_error');

        $libProperty->setValue(null);
        DbMock::setConnection(null);
    }

    public function testLoginUserDisabled() {
        $userMock = $this->getMockBuilder(User::class)
            ->setMethodsExcept(['login'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockPasswordLib = $this->getMockBuilder(PasswordLib::class)
            ->setMethods(['verifyPasswordHash'])
            ->getMock();

        $mockPasswordLib->expects($this->once())
            ->method('verifyPasswordHash')
            ->willReturn(true);

        $userReflection = new ReflectionClass(User::class);
        $user = $userReflection->newInstanceWithoutConstructor();
        $userReflection = new ReflectionClass($user);

        $libProperty = $userReflection->getProperty('_lib');
        $libProperty->setAccessible(true);
        $libProperty->setValue($mockPasswordLib);

        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        DbMock::setConnection($conn);

        $queryResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryResult->num_rows = 1;

        $fetchResult = new \stdClass();
        $fetchResult->id =1;
        $fetchResult->password = User::passwordHash('test123');
        $fetchResult->additional_fields = "[]";
        $fetchResult->status = 0;

        $queryResult
            ->method('fetch_object')
            ->will($this->onConsecutiveCalls($fetchResult, null, $fetchResult, null));

        $conn->expects($this->exactly(1))->method('query')->willReturn($queryResult);
        $queryResult->expects($this->exactly(2))->method('fetch_object')->willReturn($fetchResult);
        $queryResult->expects($this->exactly(1))->method('free')->willReturn(true);

        $response = $userMock->login([
            'login' => 'test@test.com',
            'pass' => 'test123',
            'remember' => 1
        ]);
        $result = explode(';', $response, 2);
        $this->assertTrue(!empty($result), 'Non empty result');
        $this->assertEquals($result[1], 'user_disabled');

        $libProperty->setValue(null);
        DbMock::setConnection(null);
    }
    public function testLoginUserDisabled2() {
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['fundist_uid'])
            ->disableOriginalConstructor()
            ->getMock();
        $userMock->expects($this->exactly(1))->method('fundist_uid')->willReturn(0);

        $mockPasswordLib = $this->getMockBuilder(PasswordLib::class)
            ->setMethods(['verifyPasswordHash'])
            ->getMock();

        $mockPasswordLib->expects($this->once())
            ->method('verifyPasswordHash')
            ->willReturn(true);

        $userReflection = new ReflectionClass(User::class);
        $user = $userReflection->newInstanceWithoutConstructor();
        $userReflection = new ReflectionClass($user);

        $libProperty = $userReflection->getProperty('_lib');
        $libProperty->setAccessible(true);
        $libProperty->setValue($mockPasswordLib);

        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        DbMock::setConnection($conn);

        $queryResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryResult->num_rows = 1;

        $fetchResult = new \stdClass();
        $fetchResult->id =1;
        $fetchResult->password = User::passwordHash('test123');
        $fetchResult->additional_fields = "[]";

        $queryResult
            ->method('fetch_object')
            ->will($this->onConsecutiveCalls($fetchResult, null, $fetchResult, null));

        $conn->expects($this->exactly(2))->method('query')->willReturn($queryResult);
        $queryResult->expects($this->exactly(2))->method('fetch_object')->willReturn($fetchResult);
        $queryResult->expects($this->exactly(1))->method('free')->willReturn(true);

        $response = $userMock->login([
            'login' => 'test@test.com',
            'pass' => 'test123',
            'remember' => 1
        ]);
        $result = explode(';', $response, 2);
        $this->assertTrue(!empty($result), 'Non empty result');
        $this->assertEquals($result[1], 'user_disabled');

        $libProperty->setValue(null);
        DbMock::setConnection(null);
    }

    public function testLoginRegistrationInProgress() {
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['fundist_uid'])
            ->disableOriginalConstructor()
            ->getMock();
        $userMock->expects($this->exactly(1))->method('fundist_uid')->willReturn(0);

        $mockPasswordLib = $this->getMockBuilder(PasswordLib::class)
            ->setMethods(['verifyPasswordHash'])
            ->getMock();

        $mockPasswordLib->expects($this->once())
            ->method('verifyPasswordHash')
            ->willReturn(true);

        $userReflection = new ReflectionClass(User::class);
        $user = $userReflection->newInstanceWithoutConstructor();
        $userReflection = new ReflectionClass($user);

        $libProperty = $userReflection->getProperty('_lib');
        $libProperty->setAccessible(true);
        $libProperty->setValue($mockPasswordLib);

        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        DbMock::setConnection($conn);

        $queryResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryResult->num_rows = 1;

        $fetchResult = new \stdClass();
        $fetchResult->id =1;
        $fetchResult->password = User::passwordHash('test123');
        $fetchResult->additional_fields = "[]";
        $fetchResult->status = -1;

        $queryResult
            ->method('fetch_object')
            ->will($this->onConsecutiveCalls($fetchResult, null, $fetchResult, null));

        $conn->expects($this->exactly(2))->method('query')->willReturn($queryResult);
        $queryResult->expects($this->exactly(2))->method('fetch_object')->willReturn($fetchResult);
        $queryResult->expects($this->exactly(1))->method('free')->willReturn(true);

        $response = $userMock->login([
            'login' => 'test@test.com',
            'pass' => 'test123',
            'remember' => 1
        ]);
        $result = explode(';', $response, 2);
        $this->assertTrue(!empty($result), 'Non empty result');
        $this->assertEquals($result[1], 'registration_in_progress');

        $libProperty->setValue(null);
        DbMock::setConnection(null);
    }

    public function testLoginEmptyLogin() {
        $userMock = $this->getMockBuilder(User::class)
            ->setMethodsExcept(['login'])
            ->disableOriginalConstructor()
            ->getMock();

        $response = $userMock->login([
            'login' => '',
        ]);
        $result = explode(';', $response, 2);
        $this->assertTrue(!empty($result), 'Non empty result');
        $this->assertEquals($result[1], 'authorization_error');
    }

    public function testsendEmailUnsubscribe()
    {
        $mock = $this->getMockBuilder(User::class)
            ->onlyMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        $mock
            ->expects($this->exactly(1))
            ->method('getApiTID')
            ->willReturn('test_123');

        $mock
            ->expects($this->exactly(1))
            ->method('runFundistAPI')
            ->willReturn('1,Email agree disabled');

        $res = $mock->sendEmailUnsubscribe('512046_840cb8a8a8e1fe9d6c38188d6c9b650a2');
        $this->assertEquals($res, '1,Email agree disabled');
    }

    public function testGetFundistUser()
    {
        global $cfg;

        $mock = $this->getMockBuilder(User::class)
            ->setMethods(['getApiTID','runFundistAPI','getProfileData'])
            ->disableOriginalConstructor()
            ->getMock();

        $iProp = (new \ReflectionClass(System::class))->getProperty('instance');
        $iProp->setAccessible(true);
        $iProp->setValue($mock);

        $mock
            ->expects($this->any())
            ->method('getApiTID')
            ->willReturn('test_123');

        $fundistAPI = '1,{"Balance":10,"availableWithdraw":10,"Category":1,"Freerounds":[],"Loyalty":{"IDUser": 42},"OpenPositions":"42", "Pincode": 42}';
        $mock
            ->expects($this->any())
            ->method('runFundistAPI')
            ->will($this->returnCallback(function() use (&$fundistAPI): string {
                return $fundistAPI;
            }));

        $cfg['getFullProfile'] = true;

        $user = new User(123);
        $user->id = 123;
        $user->api_password = '312abc';
        $_SESSION['user'] = [
            'id' => $user->id,
            'email' => 'test@test.com',
            'password' => '123',
        ];

        $userData = $mock->getFundistUser($user, true);
        $this->assertEquals($fundistAPI, $userData);
    }

    public function testProfileUpdateIbanSwift()
    {
        $systemMock = $this->getMockBuilder(System::class)
            ->setMethods(['runFundistAPI', 'getApiTID'])
            ->disableOriginalConstructor()
            ->getMock();

        $reflectionSystem = new \ReflectionClass(System::class);
        $reflectionProperty = $reflectionSystem->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($systemMock);

        $systemMock->expects($this->any())->method('getApiTID')->willReturn('test_123');
        $systemMock->expects($this->any())->method('runFundistAPI')->willReturn("1,iban");

        unset($_SESSION['user']);
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['runFundistAPI', 'assertAuth'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->expects($this->any())->method('assertAuth')->willReturn(1,1,1,0);

        $userData = [
            'Swift' => 'SWIFT',
            'Iban' => 'BAIBANWRONG',
            'email' => 'test@test.test',
            'login' => '',
            'country' => 'RU',
            'main_phone' => '',
            'firstName' => 'name',
            'lastName' => 'lanme',
            'currentPassword' => '',
            'password' => '',
            'repeatPassword' => '',
            "pre_phone" => '',
            "main_phone" => '',
            "birth_day" => '4',
            "birth_month" => '4',
            "birth_year" => '1996',
            "sex" => 'M',
        ];
        $userMock->userData = new \stdClass();
        $userMock->userData->id = '1';
        $userMock->userData->password = '4reer#2';
        $userMock->userData->email = 'test@test.test';
        $userMock->userData->api_password = 'api_password';
        $userMock->userData->currency = 'EUR';

        $this->expectException(\eGamings\WLC\RestApi\ApiException::class);
        $this->expectExceptionMessage('');

        $this->assertEquals($userMock->profileUpdate($userData), '0;{"swift":"Wrong SWIFT number","ibanNumber":"Wrong IBAN number"}');

        $userData['Swift'] = 'SABRRUMM';
        $this->assertEquals($userMock->profileUpdate($userData), '0;{"ibanNumber":"Wrong IBAN number"}');

        $userData['Iban'] = 'SABRRPMM';
        $this->assertEquals($userMock->profileUpdate($userData), '0;{"swift":"Wrong SWIFT number","ibanNumber":"Wrong IBAN number"}');
    }

    public function testUpdateTemporaryLocks()
    {
        $conn = $this->getMockBuilder(DbConnectionMock::class)->setMethods(['query'])
            ->disableOriginalConstructor()->getMock();
        DbMock::setConnection($conn);

        $queryResult = true;
        $conn->expects($this->any())->method('query')->willReturn($queryResult);
        $conn->insert_id = 1;

        $_POST = [];
        $r = User::updateTemporaryLocks();
        $this->assertEquals($r, json_encode(['error' => ['Error' => 'Empty params']]));

        $_POST = [
            'params' => json_encode([]),
        ];
        $r = User::updateTemporaryLocks();
        $this->assertEquals($r, json_encode(['error' => ['Error' => 'Wrong data']]));

        $_POST = [
            'params' => json_encode([
                    'enabled' => [1,2],
                    'disabled' => [3,4],
                ]),
        ];
        $r = User::updateTemporaryLocks();
        $this->assertTrue($r);
    }

    public function testCancelDebetNoUserSession(): void
    {
        $this->assertSame('0,Session is expired', (new User())->cancelDebet(['withdraw_id' => 10]));
    }

    public function testCancelDebet(): void
    {
        $userMock = $this->getMockBuilder(User::class)
            ->setMethods(['isUser', 'runFundistAPI', 'getApiTID'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->expects($this->any())->method('isUser')->willReturn(true);
        $userMock->expects($this->any())->method('getApiTID')->willReturn('test_123');
        $userMock->expects($this->any())->method('runFundistAPI')->willReturn('1,1');

        $userMock->userData = new \stdClass();
        $userMock->userData->id = 32;
        $userMock->userData->api_password = 'passwd';

        $this->assertSame('0,Withdrawal ID not specified', $userMock->cancelDebet([]));
        $this->assertSame('1,1', $userMock->cancelDebet(['withdraw_id' => 10]));
    }

    public function testCompleteDebetNoUserSession(): void
    {
        $this->assertSame('0,Session is expired', (new User())->completeDebet(['withdraw_id' => 10]));
    }

    public function testCompleteDebet(): void
    {
        $userMock = $this->getMockBuilder(User::class)
            ->setMethods(['isUser', 'runFundistAPI', 'getApiTID'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->expects($this->any())->method('isUser')->willReturn(true);
        $userMock->expects($this->any())->method('getApiTID')->willReturn('test_123');
        $userMock->expects($this->any())->method('runFundistAPI')->willReturn('1,SomeJSON');

        $userMock->userData = new \stdClass();
        $userMock->userData->id = 32;
        $userMock->userData->api_password = 'passwd';

        $this->assertSame('0,Withdrawal ID not specified', $userMock->completeDebet([]));
        $this->assertSame('1,SomeJSON', $userMock->completeDebet(['withdraw_id' => 10]));
    }

    public function testProfileUpdatePassword()
    {
        $systemMock = $this->getMockBuilder(System::class)
            ->onlyMethods(['runFundistAPI', 'getApiTID'])
            ->disableOriginalConstructor()
            ->getMock();

        $systemMock->expects($this->any())->method('getApiTID')->willReturn('test_123');
        $systemMock->expects($this->any())->method('runFundistAPI')->willReturn("1,iban");

        unset($_SESSION['user']);
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['runFundistAPI', 'assertAuth', 'testPassword', 'getApiTID'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->expects($this->any())->method('assertAuth')->willReturn(1);
        $userMock->expects($this->any())->method('testPassword')->willReturn(true);
        $userMock->expects($this->any())->method('getApiTID')->willReturn('test_123');

        $_SERVER['TEST_RUN'] = true;

        $userData = [
            'Swift' => '',
            'Iban' => '',
            'email' => 'test@test.test',
            'login' => '',
            'country' => 'RU',
            'main_phone' => '',
            'firstName' => 'name',
            'lastName' => 'lanme',
            'currentPassword' => '4reer#2',
            'password' => 'Test123!',
            'repeatPassword' => 'Test123!',
            "pre_phone" => '',
            "birth_day" => '4',
            "birth_month" => '4',
            "birth_year" => '1996',
            "sex" => 'M',
            "affiliateClickId" => '',
            "affiliateClickIdOld" => '',
        ];

        $userMock->userData = new \stdClass();
        $userMock->userData->id = '1';
        $userMock->userData->password = '4reer#2';
        $userMock->userData->email = 'test@test.test';
        $userMock->userData->api_password = 'api_password';
        $userMock->userData->currency = 'EUR';
        // successfull 6 symbol standart password
        $this->assertEquals($userMock->profileUpdate($userData), true);

        // failed standart 6 symbol password
        $userData['password'] = '12345';
        $userData['repeatPassword'] = '12345';
        $this->assertEquals($userMock->profileUpdate($userData), '0;{"password":"new_pass_less_6_symbols"}');

        // successfull 5 symbol simple password
        global $cfg;
        $cfg['PasswordSecureLevel'] = 'custom:lowest';
        $this->assertEquals($userMock->profileUpdate($userData), true);

        // failed less than 5 symbols
        $userData['password'] = '123';
        $userData['repeatPassword'] = '123';
        $this->assertEquals($userMock->profileUpdate($userData), '0;{"password":"new_pass_less_5_symbols"}');

    }

    public function testCheckPassOnUpdate()
    {
        $userMock = $this->getMockBuilder(User::class)
            ->setMethodsExcept(['checkPassOnUpdate'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->userData = new \stdClass();
        $userMock->userData->id = '1';
        $userMock->userData->additional_fields = '{}';

        _cfg('checkPassOnUpdate', 0);
        $this->assertFalse($userMock->checkPassOnUpdate());

        _cfg('checkPassOnUpdate', 1);
        _cfg('fastRegistration', 1);
        _cfg('registerGeneratePassword', 1);
        _cfg('skipPassCheckOnFirstSession', 1);
        $this->assertTrue($userMock->checkPassOnUpdate());

        $userMock->userData->additional_fields = '{"isFirstSession":1}';
        $this->assertFalse($userMock->checkPassOnUpdate());

        $userMock->userData->additional_fields = '{"type":"metamask"}';
        $this->assertFalse($userMock->checkPassOnUpdate());
    }

    public function testSetIsFirstSession()
    {
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['profileAdditionalFieldsUpdate'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->expects($this->any())->method('profileAdditionalFieldsUpdate')->willReturn([
            'isFirstSession' => 1,
        ]);

        $userMock->userData = new \stdClass();
        $userMock->userData->id = '1';
        $userMock->userData->additional_fields = '{}';

        _cfg('skipPassCheckOnFirstSession', 0);
        $this->assertNull($userMock->setIsFirstSession());

        _cfg('skipPassCheckOnFirstSession', 1);
        $userMock->userData->additional_fields = '{"isFirstSession":1}';
        $this->assertNull($userMock->setIsFirstSession());

        $userMock->userData->additional_fields = '{}';
        $this->assertNull($userMock->setIsFirstSession());
    }

    public function testRestorePassword(): void
    {
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['login'])
            ->disableOriginalConstructor()
            ->getMock();

        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->onlyMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        DbMock::setConnection($conn);

        $queryResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryResult->num_rows = 1;

        $fetchResult = new \stdClass();
        $fetchResult->id = 1;
        $fetchResult->email = 'qw@er.ty';
        $fetchResult->password = User::passwordHash('test123');
        $fetchResult->currency = 'EUR';
        $fetchResult->additional_fields = "[]";
        $fetchResult->status = 1;
        $fetchResult->api_password = 'this-is-test-pass';

        $queryResult->fetch_object = static function() use ($fetchResult) {
            return $fetchResult;
        };

        $conn->expects($this->exactly(2))->method('query')->willReturn($queryResult);
        $queryResult->expects($this->exactly(1))->method('fetch_object')->willReturn($fetchResult);
        $queryResult->expects($this->exactly(1))->method('free')->willReturn(true);

        $userMock->expects($this->exactly(1))->method('login')->willReturn('1');

        $response = $userMock->restorePassword('qw@er.ty','pass', null, true);
        $this->assertEquals('1;1', $response);

        DbMock::setConnection(null);
    }

    public function testSetProfileType()
    {
        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['profileAdditionalFieldsUpdate'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->expects($this->exactly(1))->method('profileAdditionalFieldsUpdate')->willReturn([
            'type' => 'default',
        ]);

        $userMock->userData = new \stdClass();

        $userMock->userData->id = '';
        $this->assertNull($userMock->setProfileType(User::LOGIN_TYPE_DEFAULT));

        $userMock->userData->id = '1';
        $this->assertNull($userMock->setProfileType(User::LOGIN_TYPE_DEFAULT));
    }

    public function testIsNeedProfileChangeMail(): void
    {
        $userMock = $this->getMockBuilder(User::class)
            ->setMethodsExcept(['isNeedProfileChangeMail'])
            ->disableOriginalConstructor()
            ->getMock();

        $new = [
            'firstName' => 'Name',
            'lastName' => 'LName',
            'pre_phone' => '7',
            'main_phone' => '9111111111',
            'repeatPassword' => 'passwd',
            'ext_profile' => [
                'pep' => true,
            ]
        ];
        $old = [
            'first_name' => 'Name',
            'last_name' => 'LName',
            'phone1' => '7',
            'phone2' => '9111111111',
        ];
        $this->assertTrue($userMock->isNeedProfileChangeMail($new,$old));

        $old['additional_fields'] = json_encode([
            'ext_profile' => [
                'pep' => true,
            ]
        ]);
        $this->assertFalse($userMock->isNeedProfileChangeMail($new,$old));
    }

    public function testUpdateFundistUser(): void
    {
        _cfg('fundistTidUUID', 1);
        _cfg('useFundistTemplate', 1);

        $userMock = $this->getMockBuilder(User::class)
            ->setMethodsExcept(['updateFundistUser', 'isUser', 'runFundistAPI'])
            ->setMethods(['isUser', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->method('runFundistAPI')->willReturn("1");
        $userMock->method('isUser')->willReturn(true);

        $reflectionSystem = new \ReflectionClass(System::class);
        $reflectionProperty = $reflectionSystem->getProperty('instance');
        $reflectionProperty->setAccessible(true);

        $systemMock = $this->getMockBuilder(System::class)->setMethods(['getApiTID', 'runFundistAPI'])->disableOriginalConstructor()->getMock();
        $systemMock->method('getApiTID')->willReturn('test-tid-1');
        $systemMock->method('runFundistAPI')->willReturn('1;Saved');
        $reflectionProperty->setValue($systemMock);

        $userMock->userData = new \stdClass();
        $userMock->userData->id = 1;
        $userMock->userData->api_password = 'testpassword';
        $userMock->userData->currency = 'EUR';
        $userMock->userData->email = 'test@test.com';
        $userMock->userData->additional_fields = '{}';
        $userMock->userData->login = 'login';

        $data = [
            'firstName' => 'Name',
            'lastName' => 'LName',
            'pre_phone' => '7',
            'main_phone' => '9111111111',
        ];
        $this->assertEquals($userMock->updateFundistUser($data), 1);
    }

    public function testDepositPrestepSessionExpired(): void
    {
        $userMock = $this->createUserMock(['isUser']);

        $userMock
            ->method('isUser')
            ->willReturn(false);

        $this->assertSame('0,Session is expired', $userMock->depositPrestep([]));
    }

    public function testDepositPrestepWithoutSystemId(): void
    {
        $userMock = $this->createUserMockWithSession();
        $this->assertSame('0,System ID not specified', $userMock->depositPrestep([]));
    }

    public function testDepositPrestepWithoutAmount(): void
    {
        $userMock = $this->createUserMockWithSession();
        $this->assertSame('0,set_amount', $userMock->depositPrestep(['systemId' => 1]));
    }

    public function testDepositPrestepSuccess(): void
    {
        $userMock = $this->createUserMockWithSession(['runFundistAPI', 'getApiTID']);

        $userMock
            ->method('runFundistAPI')
            ->willReturn('1,1');

        $userMock
            ->method('getApiTID')
            ->willReturn('test123');

        $userMock->userData = new \stdClass();
        $userMock->userData->id = 1;
        $userMock->userData->currency = 'EUR';
        $userMock->userData->api_password = 'test-api-password';

        $this->assertSame('1,1', $userMock->depositPrestep([
            'systemId' => 1,
            'amount' => '1.00',
        ]));
    }

    private function createUserMock(array $methods = []): MockObject
    {
        return $this
            ->getMockBuilder(User::class)
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function createUserMockWithSession(array $methods = []): MockObject
    {
        $userMock = $this->createUserMock(array_merge($methods, ['isUser']));

        $userMock
            ->method('isUser')
            ->willReturn(true);

        return $userMock;
    }
}
