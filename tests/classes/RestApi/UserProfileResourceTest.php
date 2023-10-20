<?php
namespace eGamings\WLC\Tests\RestApi;

use eGamings\WLC\RestApi\ApiException;
use eGamings\WLC\RestApi\UserProfileResource;
use eGamings\WLC\Tests\DbMock;
use eGamings\WLC\Tests\DbConnectionMock;
use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\User;
use eGamings\WLC\Validators\Rules\UserRegisterValidatorRules;
use eGamings\WLC\Validators\Rules\UserProfileValidatorRules;


class UserProfileResourceTest extends BaseCase {
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        DbMock::setConnection(null);
        DbMock::setConnClass('eGamings\WLC\Tests\DbConnectionMock');
    }

    public function setUp(): void {
    }

    public function tearDown(): void {
    }

    private function getRegistrationData() {
        return [
            'firstName' => '',
            'lastName' => '',
            'gender' => '',
            'birthDay' => '',
            'birthMonth' => '',
            'birthYear' => '',
            'countryCode' => '',
            'phoneCode' => '',
            'phoneNumber' => '',
            'phoneAltCode' => '',
            'phoneAltNumber' => '',
            'postalCode' => '',
            'city' => '',
            'address' => '',
            'agreedWithTermsAndConditions' => true,
            'ageConfirmed' => true,
            'extProfile' => [],
            'registrationPromoCode' => '',
            'smsCode' => '',
            'email' => 'wlc_test@egamings.com',
            'password' => 'Test123!',
            'currency' => 'EUR',
            'passwordRepeat' => 'Test123!',
        ];
    }

    private function getProfileData() {
        return [
            'firstName' => '',
            'lastName' => '',
            'gender' => '',
            'birthDay' => '',
            'birthMonth' => '',
            'birthYear' => '',
            'countryCode' => '',
            'phoneCode' => '',
            'phoneNumber' => '',
            'phoneAltCode' => '',
            'phoneAltNumber' => '',
            'postalCode' => '',
            'city' => '',
            'address' => '',
            'agreedWithTermsAndConditions' => true,
            'ageConfirmed' => true,
            'extProfile' => [],
            'registrationPromoCode' => '',
            'smsCode' => '',
            'email' => 'wlc_test@egamings.com',
            'password' => 'Test123!',
            'currency' => 'EUR',
            'passwordRepeat' => 'Test123!',
        ];
    }

    function testUserProfileRegisterWithValidator() {
        $data = self::getRegistrationData();

        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        DbMock::setConnection($conn);
        
        $queryResult = true;
        $conn->expects($this->exactly(1))->method('query')->willReturn($queryResult);

        $regValidator = new UserRegisterValidatorRules();
        
        $regValidation = $regValidator->validate($data);
        $this->assertTrue(is_array($regValidation), 'Validation result is array');
        $this->assertTrue(!empty($regValidation['result']), 'Validation result not empty');
        $this->assertTrue($regValidation['result'], 'Validation result is true');
    }

    function testUserProfileRegisterWithTransformer() {
        $data = self::getRegistrationData();

        $compRequest = User::transformProfileData($data);
        $this->assertTrue(!empty($compRequest['repeat_password']), 'Repeat password set after transformation');
    }

    function testUserProfileUpdateWithValidatorFail() {
        $data = self::getProfileData();

        $profileValidator = new UserProfileValidatorRules();

        $profileValidation = $profileValidator->validate($data);
        $this->assertFalse($profileValidation['result'], 'Result must be unsuccessful');
        $this->assertTrue(!empty($profileValidation['errors']['firstName']));
        $this->assertTrue(!empty($profileValidation['errors']['lastName']));
        $this->assertTrue(!empty($profileValidation['errors']['countryCode']));
        $this->assertTrue(!empty($profileValidation['errors']['currentPassword']));
    }

    function testUserProfileUpdateWithValidatorSuccess() {
        $data = self::getProfileData();

        $profileValidator = new UserProfileValidatorRules();

        $data['firstName'] = 'Name';
        $data['lastName'] = 'Name';
        $data['gender'] = 'm';
        $data['countryCode'] = 'rus';
        unset($data['password']);
        $data['currentPassword'] = 'Test123!';
        $profileValidation = $profileValidator->validate($data);

        $this->assertTrue(is_array($profileValidation), 'Validation result is array');
        $this->assertTrue(!empty($profileValidation['result']), 'Validation result not empty');
        $this->assertTrue($profileValidation['result'], 'Validation result is true');
    }

    function testUserProfileUpdateWithoutPassValidatorSuccess() {
        $data = self::getProfileData();

        $profileValidator = new UserProfileValidatorRules();

        $data['firstName'] = 'Name';
        $data['lastName'] = 'Name';
        $data['gender'] = 'm';
        $data['countryCode'] = 'rus';
        unset($data['password']);
        $prevPassSettings = _cfg('checkPassOnUpdate');
        _cfg('checkPassOnUpdate', 0);
        $profileValidation = $profileValidator->validate($data);
        _cfg('checkPassOnUpdate', $prevPassSettings);

        $this->assertTrue(is_array($profileValidation), 'Validation result is array');
        $this->assertTrue(!empty($profileValidation['result']), 'Validation result not empty');
        $this->assertTrue($profileValidation['result'], 'Validation result is true');
    }

    function testUserProfileUpdateWithValidatorWithNewPassFail() {
        $data = self::getProfileData();

        $profileValidator = new UserProfileValidatorRules();

        $data['firstName'] = 'Name';
        $data['lastName'] = 'Name';
        $data['gender'] = 'm';
        $data['countryCode'] = 'rus';
        $data['currentPassword'] = 'Test123!';
        $data['password'] = 'ZZZ111###';

        $profileValidation = $profileValidator->validate($data);

        $this->assertFalse($profileValidation['result'], 'Result must be unsuccessful');
        $this->assertFalse(empty($profileValidation['errors']), 'Result errors must exists');
        $this->assertTrue(!empty($profileValidation['errors']['password']));
        $this->assertTrue(!empty($profileValidation['errors']['newPasswordRepeat']));
    }

    function testUserProfileUpdateWithValidatorWithNewPassSuccess() {
        $data = self::getProfileData();

        $profileValidator = new UserProfileValidatorRules();

        $data['firstName'] = 'Name';
        $data['lastName'] = 'Name';
        $data['gender'] = 'm';
        $data['countryCode'] = 'rus';
        $data['currentPassword'] = 'Test123!';
        $data['password'] = $data['newPasswordRepeat'] = 'ZZZ111###';

        $profileValidation = $profileValidator->validate($data);

        $this->assertTrue(is_array($profileValidation), 'Validation result is array');
        $this->assertTrue(!empty($profileValidation['result']), 'Validation result not empty');
        $this->assertTrue($profileValidation['result'], 'Validation result is true');
    }

    function testUserProfileUpdateWithTransformer() {
        $data = self::getProfileData();

        $data['firstName'] = 'Name';
        $data['lastName'] = 'Name';
        $data['gender'] = 'm';
        $data['countryCode'] = 'rus';

        $compRequest = User::transformProfileData($data);
        
        $this->assertTrue(!empty($compRequest['repeat_password']), 'Repeat password set after transformation');
    }

    function testUserBlockInvalidAuth()
    {
        $userProfileResource = new UserProfileResource();
        $this->expectException(ApiException::class);

        $userProfileResource->put([], [], [
            'action' => 'disable',
        ]);
    }

    function testUserBlockTransactionFailed()
    {
        $userProfileResource = new UserProfileResource();
        $_SESSION['user'] = [
            'id' => -1,
            'userData' => new \stdClass(),
        ];

        $user = User::getInstance();
        $user->userData = new \stdClass();
        $user->userData->id = -1;
        $user->userData->email = 'test@test.test';

        try {
            $this->assertIsArray($userProfileResource->put([], [], [
                'action' => 'disable',
            ]));
        } catch (\Exception $e) {
            $this->assertEquals('Transaction value generation failed', $e->getMessage());
        }

        $user->userData = false;
        $_SESSION['user'] = [];
    }
}