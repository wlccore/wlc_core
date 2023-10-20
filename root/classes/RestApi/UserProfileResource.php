<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\Config;
use eGamings\WLC\KycAml;
use eGamings\WLC\RestApi\AuthResource;
use eGamings\WLC\User;
use eGamings\WLC\Template;
use eGamings\WLC\Email;
use eGamings\WLC\Cache;
use eGamings\WLC\Validators\Rules\CpfValidatorRules;
use eGamings\WLC\Validators\Rules\CnpValidatorRules;
use eGamings\WLC\Validators\Rules\EtheriumSignatureValidatorRules;
use eGamings\WLC\Validators\Rules\UserRegisterMaltaValidatorRules;
use eGamings\WLC\Validators\Rules\UserRegisterValidatorRules;
use eGamings\WLC\Validators\Rules\UserProfileValidatorRules;
use eGamings\WLC\Validators\Rules\UserProfilePatchValidatorRules;
use eGamings\WLC\Validators\Rules\UserProfileBankDataRules;
use eGamings\WLC\Logger;
use eGamings\WLC\PrometheusKeys;
use eGamings\WLC\FixAdditionalFieldsUnicode;

/**
 * @SWG\Definition(
 *     definition="UserProfile",
 *     description="User profile",
 *     type="object",
 *     @SWG\Property(
 *         property="idUser",
 *         type="string",
 *         description="User unique identifier (from fundist)"
 *     ),
 *     @SWG\Property(
 *         property="firstName",
 *         type="string",
 *         description="User first name"
 *     ),
 *     @SWG\Property(
 *         property="lastName",
 *         type="string",
 *         description="User last name"
 *     ),
 *     @SWG\Property(
 *         property="email",
 *         type="string",
 *         description="User email"
 *     ),
 *     @SWG\Property(
 *         property="login",
 *         type="string",
 *         description="User login"
 *     ),
 *     @SWG\Property(
 *         property="countryCode",
 *         type="string",
 *         description="User country code (iso3)",
 *         example="rus"
 *     ),
 *     @SWG\Property(
 *         property="currency",
 *         type="string",
 *         description="User currency",
 *         example="EUR"
 *     ),
 *     @SWG\Property(
 *         property="gender",
 *         type="string",
 *         description="User gender",
 *         enum={"m", "f"}
 *     ),
 *     @SWG\Property(
 *         property="postalCode",
 *         type="string",
 *         description="User postal code"
 *     ),
 *     @SWG\Property(
 *         property="city",
 *         type="string",
 *         description="User city"
 *     ),
 *     @SWG\Property(
 *         property="address",
 *         type="string",
 *         description="User address"
 *     ),
 *     @SWG\Property(
 *         property="birthDay",
 *         type="string",
 *         description="User birth day",
 *         example="15"
 *     ),
 *     @SWG\Property(
 *         property="birthMonth",
 *         type="string",
 *         description="User birth month",
 *         example="3"
 *     ),
 *     @SWG\Property(
 *         property="birthYear",
 *         type="string",
 *         description="User birth year",
 *         example="1987"
 *     ),
 *     @SWG\Property(
 *         property="phoneAltCode",
 *         type="string",
 *         description="Second phone code",
 *         example="+1"
 *     ),
 *     @SWG\Property(
 *         property="phoneAltNumber",
 *         type="string",
 *         description="Second phone number",
 *         example="9876543210"
 *     ),
 *     @SWG\Property(
 *         property="extProfile",
 *         type="object",
 *         description="Additional fields",
 *         example={"nick": "Test"}
 *     ),
 *     @SWG\Property(
 *         property="registrationBonus",
 *         type="string",
 *         description="Registration bonus"
 *     ),
 *     @SWG\Property(
 *         property="phoneCode",
 *         type="string",
 *         description="User phone code",
 *         example="+213"
 *     ),
 *     @SWG\Property(
 *         property="phoneNumber",
 *         type="string",
 *         description="User phone number",
 *         example="9876543210"
 *     ),
 *     @SWG\Property(
 *         property="idNumber",
 *         type="string",
 *         description="User document identifier (passport/personal number). For validation purposes",
 *         example="5625123456"
 *     ),
 *     @SWG\Property(
 *         property="affiliateSystem",
 *         type="string",
 *         description="User affiliate system identifier",
 *         example="faff"
 *     ),
 *     @SWG\Property(
 *         property="affiliateId",
 *         type="string",
 *         description="User affiliate identifier",
 *         example="123"
 *     ),
 *     @SWG\Property(
 *         property="affiliateClickId",
 *         type="string",
 *         description="User affiliate additional information",
 *         example="google_ref"
 *     )
 * )
 */

/**
 * @class UserProfileResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\User
 */
class UserProfileResource extends AbstractResource
{
    private $_cache;

    public function __construct()
    {
        $this->_cache = new Cache();
    }

    /**
     * @SWG\Post(
     *     path="/profiles",
     *     description="User registration. Sends email to user if not enabled fast registration.",
     *     tags={"user"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"currency", "email", "password"},
     *             @SWG\Property(
     *                 property="address",
     *                 type="string",
     *                 description="User address"
     *             ),
     *             @SWG\Property(
     *                 property="birthDay",
     *                 type="string",
     *                 description="Birth day"
     *             ),
     *             @SWG\Property(
     *                 property="birthMonth",
     *                 type="string",
     *                 description="Birth month"
     *             ),
     *             @SWG\Property(
     *                 property="birthYear",
     *                 type="string",
     *                 description="Birth year"
     *             ),
     *             @SWG\Property(
     *                 property="city",
     *                 type="string",
     *                 description="User city"
     *             ),
     *              @SWG\Property(
     *                 property="code",
     *                 type="string",
     *                 description="Code"
     *             ),
     *             @SWG\Property(
     *                 property="countryCode",
     *                 type="string",
     *                 description="User country",
     *                 example="rus"
     *             ),
     *             @SWG\Property(
     *                 property="currency",
     *                 type="string",
     *                 description="User currency",
     *                 example="EUR"
     *             ),
     *             @SWG\Property(
     *                 property="email",
     *                 type="string",
     *                 description="User email"
     *             ),
     *             @SWG\Property(
     *                 property="extProfile",
     *                 type="object",
     *                 description="Additional fields",
     *                 example={"nick": "Test"}
     *             ),
     *             @SWG\Property(
     *                 property="firstName",
     *                 type="string",
     *                 description="User first name"
     *             ),
     *             @SWG\Property(
     *                 property="gender",
     *                 type="string",
     *                 description="User gender",
     *                 enum={"m", "f"}
     *             ),
     *             @SWG\Property(
     *                 property="lastName",
     *                 type="string",
     *                 description="User last name"
     *             ),
     *             @SWG\Property(
     *                 property="login",
     *                 type="string",
     *                 description="User login"
     *             ),
     *              @SWG\Property(
     *                 property="message",
     *                 type="string",
     *                 description="Message"
     *             ),
     *             @SWG\Property(
     *                 property="password",
     *                 type="string",
     *                 description="User password"
     *             ),
     *             @SWG\Property(
     *                 property="passwordRepeat",
     *                 type="string",
     *                 description="Repeat password"
     *             ),
     *             @SWG\Property(
     *                 property="phoneCode",
     *                 type="string",
     *                 description="User phone code",
     *                 example="+7"
     *             ),
     *             @SWG\Property(
     *                 property="phoneNumber",
     *                 type="string",
     *                 description="User phone number",
     *                 example="9876543210"
     *             ),
     *             @SWG\Property(
     *                 property="postalCode",
     *                 type="string",
     *                 description="User postal code"
     *             ),
     *             @SWG\Property(
     *                 property="registrationPromoCode",
     *                 type="string",
     *                 description="Bonus promo code"
     *             ),
     *             @SWG\Property(
     *                 property="registrationBonus",
     *                 type="string",
     *                 description="Bonus ID"
     *             ),
     *             @SWG\Property(
     *                 property="signature",
     *                 type="string",
     *                 description="Signature"
     *             ),
     *             @SWG\Property(
     *                 property="smsCode",
     *                 type="string",
     *                 description="Sms code (if confirmation is required)"
     *             ),
     *              @SWG\Property(
     *                 property="skipEmailVerification",
     *                 type="boolean",
     *                 description="Skip email verification"
     *             ),
     *             @SWG\Property(
     *                 property="idNumber",
     *                 type="string",
     *                 description="User document identifier (passport/personal number). For validation purposes"
     *             ),
     *             @SWG\Property(
     *                 property="useJwt",
     *                 type="integer",
     *                 description="Use jwt instead of cookies"
     *             ),
     *             @SWG\Property(
     *                 property="walletAddress",
     *                 type="string",
     *                 description="Wallet address"
     *             ),
     *             @SWG\Property(
     *                 property="affiliateSystem",
     *                 type="string",
     *                 description="Affiliate system"
     *             ),
     *             @SWG\Property(
     *                 property="affiliateId",
     *                 type="string",
     *                 description="Affiliate ID"
     *             ),
     *             @SWG\Property(
     *                 property="affiliateClickId",
     *                 type="string",
     *                 description="Affiliate click ID"
     *             ),
     *         )
     *     ),
     *     @SWG\Parameter(
     *         name="smsLogin",
     *         type="number",
     *         in="query",
     *         description="SMS Login"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="result",
     *                 type="boolean",
     *                 example="true"
     *             ),
     *             @SWG\Property(
     *                 property="jwtToken",
     *                 type="string"
     *             ),
     *             @SWG\Property(
     *                 property="refreshToken",
     *                 type="string"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * @SWG\Post(
     *     path="/profiles/email",
     *     description="Email change. You need to provide email and currentPassword for change or validate existing email. For final verification you must be logged in and provide code which is sent to current email.",
     *     tags={"user"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"email"},
     *             @SWG\Property(
     *                 property="email",
     *                 type="string"
     *             ),
     *             @SWG\Property(
     *                 property="code",
     *                 type="string"
     *             ),
     *             @SWG\Property(
     *                 property="password",
     *                 type="string"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Result of change (false - changes not available)",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="result",
     *                 type="boolean",
     *                 example="true"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * @SWG\Post(
     *     path="/profiles/confirmation/email",
     *     description="Email confirmation. To send a confirmation email, you need to send an empty POST request. To confirm, you need to specify the confirmation code in the POST and, if there is no authorization, then the password.",
     *     tags={"user"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="code",
     *                 type="string"
     *             ),
     *             @SWG\Property(
     *                 property="password",
     *                 type="string"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="true - Message sent",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="result",
     *                 type="boolean",
     *                 example="true"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * Register new user profile
     *
     * @public
     * @method post
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     * @return {array}
     * @throws {ApiException}
     */
    public function post($request, $query, $params = [])
    {
        PrometheusKeys::getInstance()->AUTH_REGISTER_START->store();
        $this->checkCountryForbidden();

        $fastPhoneRegistration = _cfg('fastPhoneRegistration') && empty($request['login']) && empty($request['email']);
        $loginType = User::LOGIN_TYPE_DEFAULT;
        if ((isset($query['smsLogin']) && $query['smsLogin'] == 1) || $fastPhoneRegistration) {
            $loginType = User::LOGIN_TYPE_SMS;
        } elseif (!empty($request['walletAddress']) && !empty($request['message']) && !empty($request['signature'])) {
            if (!_cfg('useMetamask')) {
                throw new ApiException(_('register_metamask_not_allowed'), 403);
            }

            $loginType = User::LOGIN_TYPE_METAMASK;
        }

        $user = User::getInstance();

        $action = !empty($params['action']) ? $params['action'] : '';
        $post_action = !empty($params['post_action']) ? $params['post_action'] : '';
        $result = null;

        if (isset($request['emailAgree']) && !isset($request['sendEmail'])) {
            $request['sendEmail'] = $request['emailAgree'];
        }

        switch ($action) {
            case 'email':
            case 'phone':
            case 'verification':
                if (!empty($request['code']) && !empty($request['password']) && !User::isAuthenticated()) {
                    if (!$userData = User::getDataByEmailCode($request['code'])) {
                        throw new ApiException(_('Code is incorrect'), 403);
                    }

                    if (!User::verifyPassword($request['password'], $userData->password)) {
                        $attempts = json_decode($userData->additional_fields, true)['emailChangeVerifyAttempts'] ?? 0;
                        $user->profileAdditionalFieldsUpdate(['emailChangeVerifyAttempts' => ++$attempts], (int)$userData->id);

                        if ($attempts >= 3) {
                            User::removeEmailCode((int)$userData->id);
                        }

                        throw new ApiException(_('Password is invalid'), 403);
                    }

                    $user->login(['login' => $userData->email, 'pass' => $request['password']], true);
                    $user->userData = $userData;
                }

                if (!User::isAuthenticated() || empty($user->userData)) {
                    throw new ApiException(_('User is not authorized'), 401);
                }
                if ($action == 'email') {
                    if (!_cfg('allowEmailChange')) {
                        throw new ApiException(_('Email change is not allowed'), 405);
                    }

                    if (!empty($request['code'])) {
                        $request['email'] = $user->userData->new_email;
                    }else{
                        if (empty($request['email'])) {
                            throw new ApiException(_('Email is required'), 405);
                        }

                        if (empty($request['password'])) {
                            throw new ApiException(_('Password is required'), 405);
                        }

                        if(!User::verifyPassword($request['password'], $user->userData->password)){
                            throw new ApiException(_('Invalid password'), 405);
                        }

                        if($request['email'] == $user->userData->email){
                            throw new ApiException(_('The new email must not be the same as the old one.'), 405);
                        }

                        if(!$user->checkFieldExists($action, $request['email'])){
                            throw new ApiException(_('This email is already in the database'), 405);
                        }
                    }


                }
                elseif ($action == 'phone' && _cfg('registerUniquePhone')) {
                    if($user->userData->phone1 != $request['phoneCode'] || $user->userData->phone2 != $request['phoneNumber']) {
                        $validator = new UserRegisterValidatorRules();
                        $validatorResult = $validator->validate($request, ['phoneCode', 'phoneNumber']);

                        if (!$validatorResult['result']) {
                            $errors = $validatorResult['errors'];
                            throw new ApiException($errors['phoneCode'] ? $errors['phoneCode'] : $errors['phoneNumber'], 400, null);
                        }
                    }
                }

                if ($action === 'email' && !empty($request['code'])) {
                    $result = $user->profileUpdateEmail('', $request['code']);
                } else {
                    $fieldsForUpdate = [$action];
                    $data = User::transformProfileData($request);

                    $result = $user->profileUpdatePartial( $fieldsForUpdate, $data );
                }

                if ($result !== true) {
                    throw new ApiException(_('User update failed'), 400, null, $result);
                }

                return [
                    'result' => $result
                ];

            case 'confirmation':
                $errors = [];

                switch ($post_action) {
                    case 'email':
                        if (empty($request['code']) && User::isAuthenticated()) {
                            if ($user->userData->email_verified) {
                                throw new ApiException('Email already confirmed', 400, null, $errors);
                            }

                            $result = $user->profileConfirmEmail();
                            $user->profileAdditionalFieldsUpdate(['emailChangeVerifyAttempts' => 0], (int)$user->userData->id);


                            if (empty($result)) {
                                throw new ApiException('Email update failed', 400, null, $errors);
                            }

                        } elseif (!empty($request['code'])){
                            $authUser = User::isAuthenticated();
                            if (!$authUser && empty($request['password'])) {
                                throw new ApiException('Password not specified', 400, null, $errors);
                            }

                            if ($user->userData->email_verified) {
                                throw new ApiException('Email already confirmed', 400, null, $errors);
                            }

                            $result = $user->confirmationEmail($request['code'], (bool) $authUser, (string) $request['password']);

                            if (empty($result)) {
                                throw new ApiException('Code is incorrect', 403, null, $errors);
                            }
                        } elseif(empty($request['code']) && !User::isAuthenticated()) {
                            throw new ApiException('Unauthorized', 401, null, $errors);
                        }

                        break;
                    default:
                        throw new ApiException('Route not found', 400, null, $errors);
                        break;
                }

                if (empty($result)) {
                    throw new ApiException('Email update failed', 400, null, $errors);
                }

                if (isset($request['useJwt'])
                    && $request['useJwt'] == 1
                    && !empty($_SESSION['user']['id'])
                ) {
                    $AuthResource = new AuthResource();
                    return [
                        'result' => $result,
                        'jwtToken' => $AuthResource->setAccessJwtToken((int)$_SESSION['user']['id']),
                        'refreshToken' => $AuthResource->setRefreshJwtToken((int)$_SESSION['user']['id']),
                    ];
                }

                return [
                    'result' => $result
                ];

            default:
                $siteConfig = Config::getSiteConfig();
                if ($siteConfig['RestrictRegistration'] === 1) {
                    throw new ApiException(_('User creation error,Registration is restricted'), 400);
                }

                if (_cfg('requiredRegisterCheckbox')) {
                    foreach (_cfg('requiredRegisterCheckbox') as $checkbox) {
                        if (!isset($request[$checkbox]) || !filter_var($request[$checkbox], FILTER_VALIDATE_BOOLEAN)) {
                            throw new ApiException(_('One or more required agreements are not selected'), 400);
                        }
                    }
                }

                //auto generating password for registerGeneratePassword == true wlcs, if password field is empty
                $generatePassword = !empty(_cfg('registerGeneratePassword'));
                if (empty($request['password']) && $generatePassword) {
                    $request['password'] = $request['passwordRepeat'] = $request['repeat_password'] = $user->generatePassword(8, false);
                }

                $request = (new FixAdditionalFieldsUnicode($request))->run();

                // @codeCoverageIgnoreStart
                $config = Config::getSiteConfig();
                if ($loginType !== User::LOGIN_TYPE_METAMASK) {
                    $type = $config['Type'] ?? 'wlc';
                    $isMaltaLicense = ($config['License'] ?? '') === 'malta'
                        && ($type === 'wlc' || ($type === 'turnkey' && !empty(_cfg('enableMaltaDuplicateCheck'))));

                    $validator = $isMaltaLicense
                        ? new UserRegisterMaltaValidatorRules()
                        : new UserRegisterValidatorRules();

                    // @codeCoverageIgnoreEnd
                    $validatorResult = $loginType === User::LOGIN_TYPE_SMS
                    ? $validator->validate($request, ($fastPhoneRegistration ? ['phoneCode', 'phoneNumber', 'currency', 'countryCode']
                        : ['phoneCode', 'phoneNumber', 'code']))
                        : $validator->validate($request);
                    if (!is_array($validatorResult)) {
                        throw new ApiException(_('Registration data validation failed'), 400, null);
                    }

                    if (!$validatorResult['result']) {
                        throw new ApiException('', 400, null, $validatorResult['errors']);
                    }

                    $isRomaniaLicense = ($config['License'] ?? '') === 'romania';
                    if ($isRomaniaLicense && $request['countryCode'] === 'rou') {
                        $cpfValidatorResult = (new CnpValidatorRules())->validate($request);
                        if (!empty($cpfValidatorResult['errors'])) {
                            throw new ApiException('', 400, null, $cpfValidatorResult['errors']);
                        }
                    }

                    if (_cfg('requiredCpfField') && $request['countryCode'] === 'bra') {
                        $cpfValidator = new CpfValidatorRules();
                        $cpfValidatorResult = $cpfValidator->validate($request);
                        if (!empty($cpfValidatorResult['errors'])) {
                            throw new ApiException('', 400, null, $cpfValidatorResult['errors']);
                        }
                    }
                }

                $phoneCode = (int)trim($request['phoneCode'], '+- ');
                $phoneNumber = (int)trim($request['phoneNumber'], '+- ');
                $storedCode = $this->_cache->get(
                    SmsProviderResource::SMS_VERIFICATION_CODE,
                    [
                        'phoneCode' => $phoneCode,
                        'phoneNumber' => $phoneNumber
                    ]
                );
                if ($request['code'] != $storedCode) {
                    throw new ApiException(_('Invalid validation code'), 400, null);
                }

                if (is_array($request['affiliateId'])) {
                    Logger::log("AffiliateID is array: " . json_encode($request['affiliateId']));
                    throw new ApiException(_('Request invalid. Error: ') . _('affiliateId is array'), 400);
                }

                $compatibility_request = User::transformProfileData($request);
                if (!is_array($compatibility_request)) {
                    throw new ApiException(_('Request invalid. Error: ') . $compatibility_request, 400);
                }

                $fastRegistration = $request['skipEmailVerification'] ?? false;

                $compatibility_request['finger_print'] = !empty($_SERVER['HTTP_X_UA_FINGERPRINT']) ?
                    $_SERVER['HTTP_X_UA_FINGERPRINT'] :
                    '';

                $result = $user->register($compatibility_request, _cfg('registerSkipLogin') ? true : false, $fastRegistration, $loginType, true);
                if ($result === true || $result === 1) {
                    $result = true;
                }
                else {
                    $resultDecoded = json_decode($result, true);
                    if (is_null($resultDecoded)) {
                        $result = ['error' => [$result]];
                    }
                    else {
                        $result = $resultDecoded;
                    }
                }

                if (!empty($result['error'])) {
                    $errors = [];
                    $errorCode = NULL;

                    if (is_array($result['error'])) {
                        foreach ($result['error'] as $error) {
                            if (is_numeric($error))
                                continue;

                            $arError = explode(';', $error, 2);
                            if (count($arError) > 1) {
                                $error = $arError[1];
                                $errorCode = is_numeric($arError[0]) ? intval($arError[0]) : NULL;
                            }

                            $errors[] = $error;
                        }
                    }
                    else {
                        $errors[] = $result['error'];
                    }

                    throw new ApiException(false, 400, null, $errors, $errorCode);
                }
                break;
        }

        if (!_cfg('registerSkipLogin')
            && isset($request['useJwt'])
            && $request['useJwt'] == 1
            && !empty($_SESSION['user']['id'])
        ) {
            $AuthResource = new AuthResource();
            return [
                'result' => $result,
                'jwtToken' => $AuthResource->setAccessJwtToken((int)$_SESSION['user']['id']),
                'refreshToken' => $AuthResource->setRefreshJwtToken((int)$_SESSION['user']['id']),
            ];
        }

        PrometheusKeys::getInstance()->AUTH_REGISTER_SESSION_START->store();

        return [
            'result' => $result
        ];
    }

    /**
     * @SWG\Put(
     *     path="/profiles",
     *     description="Update user profile",
     *     tags={"user"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="address",
     *                 type="string",
     *                 description="User address"
     *             ),
     *             @SWG\Property(
     *                 property="birthDay",
     *                 type="string",
     *                 description="Birth day"
     *             ),
     *             @SWG\Property(
     *                 property="birthMonth",
     *                 type="string",
     *                 description="Birth month"
     *             ),
     *             @SWG\Property(
     *                 property="birthYear",
     *                 type="string",
     *                 description="Birth year"
     *             ),
     *             @SWG\Property(
     *                 property="city",
     *                 type="string",
     *                 description="User city"
     *             ),
     *             @SWG\Property(
     *                 property="countryCode",
     *                 type="string",
     *                 description="User country",
     *                 example="rus"
     *             ),
     *             @SWG\Property(
     *                 property="currency",
     *                 type="string",
     *                 description="User currency",
     *                 example="EUR"
     *             ),
     *             @SWG\Property(
     *                 property="email",
     *                 type="string",
     *                 description="User email"
     *             ),
     *             @SWG\Property(
     *                 property="login",
     *                 type="string",
     *                 description="User login"
     *             ),
     *             @SWG\Property(
     *                 property="firstName",
     *                 type="string",
     *                 description="User first name"
     *             ),
     *             @SWG\Property(
     *                 property="gender",
     *                 type="string",
     *                 description="User gender",
     *                 enum={"m", "f"}
     *             ),
     *             @SWG\Property(
     *                 property="lastName",
     *                 type="string",
     *                 description="User last name"
     *             ),
     *             @SWG\Property(
     *                 property="password",
     *                 type="string",
     *                 description="New password"
     *             ),
     *             @SWG\Property(
     *                 property="currentPassword",
     *                 type="string",
     *                 description="User password"
     *             ),
     *             @SWG\Property(
     *                 property="newPasswordRepeat",
     *                 type="string",
     *                 description="Repeat new password"
     *             ),
     *             @SWG\Property(
     *                 property="phoneCode",
     *                 type="string",
     *                 description="User phone code",
     *                 example="+7"
     *             ),
     *             @SWG\Property(
     *                 property="phoneNumber",
     *                 type="string",
     *                 description="User phone number",
     *                 example="9876543210"
     *             ),
     *             @SWG\Property(
     *                 property="phoneAltCode",
     *                 type="string",
     *                 description="Second phone code",
     *                 example="+1"
     *             ),
     *             @SWG\Property(
     *                 property="phoneAltNumber",
     *                 type="string",
     *                 description="Second phone number",
     *                 example="9876543210"
     *             ),
     *             @SWG\Property(
     *                 property="postalCode",
     *                 type="string",
     *                 description="User postal code"
     *             ),
     *             @SWG\Property(
     *                 property="extProfile",
     *                 type="object",
     *                 description="Additional fields",
     *                 example={"nick": "Test"}
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="result",
     *                 type="boolean",
     *                 example="true"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * @SWG\Put(
     *     path="/profiles/email",
     *     description="Email existence validation",
     *     tags={"user"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"email"},
     *             @SWG\Property(
     *                 property="email",
     *                 type="string"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Result of the check (false - exist)",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="result",
     *                 type="boolean",
     *                 example="true"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * @SWG\Put(
     *     path="/profiles/login",
     *     description="Login existence validation",
     *     tags={"user"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"login"},
     *             @SWG\Property(
     *                 property="login",
     *                 type="string",
     *                 description="User address"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Result of the check (false - exist)",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="result",
     *                 type="boolean",
     *                 example="true"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * @SWG\Put(
     *     path="/profiles/phone",
     *     description="Phone number uniqueness (if $cfg['registerUniquePhone'] = true) and format validation",
     *     tags={"user"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="phone",
     *                 type="string",
     *                 description="User phone number"
     *             ),
     *             @SWG\Property(
     *                 property="code",
     *                 type="string",
     *                 description="Phone county code"
     *             ),
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Result of the check",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="result",
     *                 type="boolean",
     *                 example="true"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * @SWG\Put(
     *     path="/profiles/promocode",
     *     description="Checks if there is a promo exists",
     *     tags={"user"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="promocode",
     *         type="string",
     *         in="body",
     *         description="Promocode"
     *     ),
     *     @SWG\Parameter(
     *         name="currency",
     *         type="string",
     *         in="body",
     *         description="Currency"
     *     ),
     *     @SWG\Parameter(
     *         name="country",
     *         type="string",
     *         in="body",
     *         description="Country"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"promocode", "currency"},
     *             @SWG\Property(
     *                 property="promocode",
     *                 type="string",
     *                 description="Promocode"
     *             ),
     *             @SWG\Property(
     *                 property="currency",
     *                 type="string",
     *                 description="Currency"
     *             ),
     *             @SWG\Property(
     *                 property="country",
     *                 type="string",
     *                 description="Country ISO"
     *             ),
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Result of the check",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="result",
     *                 type="boolean",
     *                 example="true"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * @SWG\Put(
     *     path="/profiles/emailUnsubscribe",
     *     description="Unsubscribe from mailing lists",
     *     tags={"user"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="code",
     *                 type="string",
     *                 description="Unsubscribe code"
     *             ),
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Result of the unsubscribe",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="result",
     *                 type="boolean",
     *                 example="true"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * @SWG\Put(
     *     path="/profiles/activity",
     *     description="Send activity postback",
     *     tags={"user"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *      @SWG\Parameter(
     *         name="page",
     *         type="number",
     *         in="query",
     *         description="Page number"
     *     ),
     *       @SWG\Parameter(
     *         name="page",
     *         type="number",
     *         in="body",
     *         description="Page number"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Postback sending result",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="result",
     *                 type="boolean",
     *                 example="true"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

         /**
     * @SWG\Put(
     *     path="/profiles/disable",
     *     description="Disable user",
     *     tags={"user"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="dateTo",
     *         type="string",
     *         in="query",
     *         description="Date to"
     *     ),
     *      @SWG\Parameter(
     *         name="limitType",
     *         type="string",
     *         in="query",
     *         description="Limit type"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Postback sending result",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="result",
     *                 type="boolean",
     *                 example="true"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * Update user profile
     *
     * @public
     * @method put
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     * @return {array}
     * @throws {ApiException}
     */
    public function put($request, $query, $params = [])
    {
        $user = User::getInstance();
        $action = !empty($params['action']) ? $params['action'] : '';
        $result = false;

        switch ($action) {
            case 'email':
            case 'login':
                $value = empty($request[$action]) ? (empty($query[$action]) ? null : $query[$action]) : $request[$action];
                if (!empty($value)) {
                    $result = $user->checkFieldExists($action, $value);

                    $result = ($action === 'email' && _cfg('hideEmailExistence')) ? true : $result;
                }

                break;
            // @codeCoverageIgnoreStart
            case 'promocode':
                $request = !is_array($request) ? [] : $request;
                $query = !is_array($query) ? [] : $query;
                $errors = [];
                $isValid = User::checkPromoCode($request, $query, $errors);

                if ($isValid === null) {
                    throw new ApiException('', 400, null, $errors);
                }

                $result = $isValid;
                break;
            // @codeCoverageIgnoreEnd
            case 'phone':
                if (_cfg('registerUniquePhone')) {
                    $request['phoneCode'] = !empty($request['code']) ? $request['code'] : '';
                    $request['phoneNumber'] = !empty($request['phone']) ? $request['phone'] : '';

                    $validator = new UserRegisterValidatorRules();
                    $validatorResult = $validator->validate($request, ['phoneCode', 'phoneNumber']);

                    if (!$validatorResult['result']) {
                        $errors = $validatorResult['errors'];
                        throw new ApiException($errors['phoneCode'] ? $errors['phoneCode'] : $errors['phoneNumber'], 200, null);
                    }
                    $result = $validatorResult['result'];
                } else {
                    $result = true;
                }

                break;
            case 'emailUnsubscribe':
                if (empty($request['code'])) {
                    throw new ApiException(_('Empty unsubscribe code'), 400, null);
                }

                $result = $user->sendEmailUnsubscribe($request['code']);
                $result = explode(',', $result);
                if ($result[0] != 1) {
                    throw new ApiException(_($result[1]), 400, null);
                } else {
                    $result = true;
                }

                break;
            case 'disable':
                if (!User::isAuthenticated()) {
                    throw new ApiException(_('User is not authorized'), 401);
                }

                try {
                    if ($user->turnYourselfOff(
                        $request['dateTo'] ?? $query['dateTo'] ?? null
                    )) { // If can't an exception will be thrown
                        return (new AuthResource())->delete([], [], []);
                    } else {
                        $result = false;
                    }
                } catch (\Exception $error) {
                    $result = false;
                }
                break;
            case 'activity':
                if (!User::isAuthenticated()) {
                    throw new ApiException(_('User is not authorized'), 401);
                }

                $page = empty($request['page']) ?
                        (empty($query['page']) ? '' : $query['page']) :
                        $request['page'];
                $login = $user->userData->id;
                $url = '/WLC/Activity/?&Login=' . $login;
                $transactionId = $user->getApiTID($url, $login);
                $hash = md5('WLC/Activity/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));
                $params = [
                    'TID'  => $transactionId,
                    'Hash' => $hash,
                    'Page' => $page,
                ];
                $url .= '&' . http_build_query($params);
                $response = $user->runFundistAPI($url);
                $result = $response === '1';
                if (!$result) {
                    throw new ApiException(_(explode(',', $response)[1] ?? 'Internal error'), 400);
                }
                break;
            default:
                if (!User::isAuthenticated()) {
                    throw new ApiException(_('User is not authorized'), 401);
                }

                $country = !empty($request['countryCode']) ? $request['countryCode'] : $user->userData->country;
                $forbiddenFields = array_filter([
                    in_array($country, ['rou', 'bra']) ? 'cpf' : '',
                ]);

                if (!empty($request['cpf']) && $country === 'bra') {
                    $cpfValidator = new CpfValidatorRules();
                    $cpfValidatorResult = $cpfValidator->validate(array_merge($request, ['countryCode' => $country]));
                    if (!empty($cpfValidatorResult['errors'])) {
                        throw new ApiException('', 400, null, $cpfValidatorResult['errors']);
                    }
                }

                if (_cfg('fieldsForbiddenForEditing') || $forbiddenFields) {
                    $user->checkForbiddenFieldsForEditing($request, $forbiddenFields);
                }

                $validator = new UserProfileValidatorRules();
                $validatorResult = $validator->validate($request);
                if (!is_array($validatorResult)) {
                    throw new ApiException(_('Registration data validation failed'), 400, null);
                }

                if (!$validatorResult['result']) {
                    throw new ApiException('', 400, null, $validatorResult['errors']);
                }

                if ($request['stateCode'] == 'FRBDN') {
                    throw new ApiException('', 400, null, ['stateCode' => _('No states allowed')]);
                }

                $additionalFields = User::getInstance()->userData !== false
                    ? json_decode(User::getInstance()->userData->additional_fields, true) ?? []
                    : [];
                if (isset($additionalFields['type']) && $additionalFields['type'] === User::LOGIN_TYPE_METAMASK) {
                    $validator = new EtheriumSignatureValidatorRules();
                    $res = $validator->validate($request);
                    if (!$res['result']) {
                        throw new ApiException('', 400, null, $res['errors']);
                    }
                }

                if (empty($request['stateCode']) && $request['countryCode'] != $user->userData->country) {
                    $request['stateCode'] = '';
                }

                $compatibility_request = User::transformProfileData($request);
                $result = $user->profileUpdate($compatibility_request);

                if ($result !== true) {
                    $error = [];

                    if (gettype($result) === 'string') {
                        $result = explode(';', $result);
                        $error = count($result) > 1 ? json_decode($result[1], true) :
                            json_decode($result[0], true) ?? ['error' => $result[0]];
                    } else if (gettype($result) === 'array') {
                        $error = $result;
                    }

                    throw new ApiException('', 400, null, $error);
                }

                break;
        }

        return [
            'result' => $result
        ];
    }

    /**
     * @SWG\Patch(
     *     path="/profiles/complete",
     *     description="Completition of registration",
     *     tags={"user"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"code"},
     *             @SWG\Property(
     *                 property="code",
     *                 type="string",
     *                 description="Registration code"
     *             ),
     *             @SWG\Property(
     *                 property="useJwt",
     *                 type="integer",
     *                 description="Use jwt instead of cookies"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Result",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="result",
     *                 type="boolean",
     *                 example="true"
     *             ),
     *             @SWG\Property(
     *                 property="jwtToken",
     *                 type="string"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * @SWG\Patch(
     *     path="/profiles",
     *     description="Partial update profile",
     *     tags={"user"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="birthDay",
     *                 type="string",
     *                 description="Birth day"
     *             ),
     *             @SWG\Property(
     *                 property="birthMonth",
     *                 type="string",
     *                 description="Birth month"
     *             ),
     *             @SWG\Property(
     *                 property="birthYear",
     *                 type="string",
     *                 description="Birth year"
     *             ),
     *             @SWG\Property(
     *                 property="countryCode",
     *                 type="string",
     *                 description="User country code (iso3)",
     *                 example="rus"
     *             ),
     *             @SWG\Property(
     *                 property="gender",
     *                 type="string",
     *                 description="User gender",
     *                 enum={"m", "f"}
     *             ),
     *             @SWG\Property(
     *                 property="affiliateSystem",
     *                 type="string",
     *                 description="User registered affiliate system"
     *             ),
     *             @SWG\Property(
     *                 property="affiliateId",
     *                 type="string",
     *                 description="User registered affiliate identifier"
     *             ),
     *             @SWG\Property(
     *                 property="affiliateClickId",
     *                 type="string",
     *                 description="User registered affiliate click additional info"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Result",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="result",
     *                 type="boolean",
     *                 example="true"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * @SWG\Patch(
     *     path="/profiles/language",
     *     description="Save authorized user language",
     *     tags={"user"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Result",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="result",
     *                 type="boolean",
     *                 example="true"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * Partial update.
     * If 'code' parameter presents - user registration completion occurs.
     *
     * @public
     * @method patch
     * @param {array} $request
     * @param {array} $query
     * @return {array}
     * @throws {ApiException}
     */
    public function patch($request, $query, $params = [])
    {
        $this->checkCountryForbidden();

        $user = new User();
        if (!empty($request['code'])) {
            $val = Cache::get('registration:code', [$request['code']]);
            if(isset($val)) {
                throw new ApiException('Code processing', 400);
            }
            Cache::set('registration:code', 1, 2, [$request['code']]);

            $code = trim($request['code']);

            if (filter_var($code, FILTER_VALIDATE_INT)) { // Perhaps it is a user id
                $user = new User((int) $code);
                $userData = $user->userData;

                if (is_object($userData) && $userData->id) {
                    $this->setVerifyEmailPhone($userData);

                    $result = [
                        'result' => true
                    ];
                    if (isset($request['useJwt']) && $request['useJwt']) {
                        $result['jwtToken'] = (new AuthResource())->setAccessJwtToken((int)$userData->id);
                        $result['refreshToken'] = (new AuthResource())->setRefreshJwtToken((int)$userData->id);
                    }

                    return $result;
                }
            }

            $alreadyRegisterUserId = $user->getIdByEmailCode($request['code']);
            if (!empty($alreadyRegisterUserId)) {
                throw new ApiException(_('The user has already been confirmed. Log in to the site.'), 400);
            }

            $tempUser = $user->getUsersTempByCode($request['code']);
            if ($tempUser->id === null) {
                throw new ApiException(_('Email verification code is not found, maybe you have already confirmed your email, please check your mailbox'), 400);
            }

            if ((bool) _cfg('fastRegistrationWithoutBets')) {
                $userData = $user->getUserByEmail($tempUser->email);
                $user = new User($userData->id);
                $answer = $user->userData;
                $user->confirmationCode();
            } else {
                User::$userState = User::$CONFIRMATION_CODE;
                $data = !empty($_SERVER['HTTP_X_UA_FINGERPRINT']) ? ['finger_print' => $_SERVER['HTTP_X_UA_FINGERPRINT']]: [] ;
                $answer = $user->finishRegistration($tempUser->id, $data);
                User::$userState = User::$NONE;
            }

            if (!is_object($answer) && $answer !== true) {
                throw new ApiException($answer, 400);
            }

            if (is_object($answer)) {
                $this->setVerifyEmailPhone($answer);
            }

            Cache::delete('registration:code', [$request['code']]);

            if(_cfg('useFundistTemplate') == 1) {

                if (!_cfg('registerSkipLogin')
                    && isset($request['useJwt'])
                    && $request['useJwt'] == 1
                    && !empty($_SESSION['user']['id'])
                ) {
                    return [
                        'result' => true,
                        'jwtToken' => (new AuthResource())->setAccessJwtToken((int)$_SESSION['user']['id']),
                        'refreshToken' => (new AuthResource())->setRefreshJwtToken((int)$_SESSION['user']['id']),
                    ];
                }

                return [
                    'result' => true
                ];
            }

            $templateName = 'registration-complete';
            $templateContext = [
                'email' => $answer->email,
                'first_name' => $answer->first_name,
                'last_name' => $answer->last_name,
                'site_url' => _cfg('site'),
                'site_name' => _cfg('websiteName')
            ];

            $template = new Template();
            $msg = $template->getMailTemplate($templateName, $templateContext);
            if($msg!==false){
                if (_cfg('enqueue_emails') == 1) {
                    Email::enqueue($answer->email, _('Registration_complete'), $msg);
                } else {
                    $mailMsg = Email::send($answer->email, _('Registration_complete'), $msg);
                }
            }

        } else {
            if (!User::isAuthenticated()) {
                throw new ApiException(_('User is not authorized'), 401);
            }

            $country = !empty($request['countryCode']) ? $request['countryCode'] : $user->userData->country;
            $forbiddenFields = array_filter([
               in_array($country, ['rou', 'bra']) ? 'cpf' : '',
            ]);

            if (!empty($request['cpf']) && $country === 'bra') {
                $cpfValidator = new CpfValidatorRules();
                $cpfValidatorResult = $cpfValidator->validate(array_merge($request, ['countryCode' => $country]));
                if (!empty($cpfValidatorResult['errors'])) {
                    throw new ApiException('', 400, null, $cpfValidatorResult['errors']);
                }
            }

            if (_cfg('fieldsForbiddenForEditing') || $forbiddenFields) {
                $user->checkForbiddenFieldsForEditing($request, $forbiddenFields);
            }
            
            $action = !empty($params['action']) ? $params['action'] : '';

            if ($action == 'language') {
                $result = $user->profileUpdateLanguage(_cfg('language'));
            } else {
                $fields = [
                    'firstName' => 'first_name',
                    'lastName' => 'last_name',
                    'email' => 'email',
                    'middleName' => 'middle_name',
                    'countryCode' => 'country',
                    'birthDay' => 'birth_day',
                    'birthMonth' => 'birth_month',
                    'birthYear' => 'birth_year',
                    'gender' => 'sex',
                    'extProfile' => 'ext_profile',
                    'postalCode' => 'postal_code',
                    'city' => 'city',
                    'address' => 'address',
                    'bankName' => 'BankName',
                    'branchCode' => 'BranchCode',
                    'ibanNumber' => 'Iban',
                    'swift' => 'Swift',
                    'phoneCode' => 'phone1',
                    'phoneNumber' => 'phone2',
                    'idNumber' => 'IDNumber',
                    'VerificationSessionID' => 'VerificationSessionID',
                    'currentPassword' => 'currentPassword',
                    'smsAgree' => 'sendSMS',
                    'emailAgree' => 'sendEmail',
                    'stateCode' => 'state',
                    'IDIssueDate' => 'IDIssueDate',
                    'IDIssuer' => 'IDIssuer',
                    'message' => 'message',
                    'walletAddress' => 'walletAddress',
                    'signature' => 'signature',
                    'login' => 'login',
                    'cpf' => 'cpf',
                    'avatarId' => 'avatar_id',
                ];
                $compatibility_request = [];

                foreach ($fields as $field => $compatibility_field) {
                    if (isset($request[$field])) {
                        $compatibility_request[$compatibility_field] = $request[$field];
                    }
                }

                $compatibility_request['RestrictCasinoBonuses'] = (int)!empty($request['RestrictCasinoBonuses']);
                $compatibility_request['RestrictSportBonuses'] = (int)!empty($request['RestrictSportBonuses']);

                if (!isset($compatibility_request['country']) && !empty($user->userData->country)) {
                    $compatibility_request['country'] = $user->userData->country;
                }

                if (isset($request['stateCode']) && !isset($request['countryCode']) && !empty($user->userData->country)) {
                    $request['countryCode'] = $user->userData->country;
                }

                $validatorResult = (new UserProfilePatchValidatorRules())->validate($request);
                if (!$validatorResult['result']) {
                    throw new ApiException('', 400, null, $validatorResult['errors']);
                }

                $validator = new UserProfileBankDataRules();
                $validatorResult = $validator->validate($compatibility_request);
                if (!is_array($validatorResult)) {
                    throw new ApiException('', 400, null, $validatorResult['errors']);
                }

                if (empty($request['stateCode']) && $compatibility_request['country'] != $user->userData->country) {
                   $compatibility_request['state'] = '';
                }

                $params['isAfterDepositWithdraw'] = $request['isAfterDepositWithdraw'] ?? false;

                $result = $user->profileAdditionalUpdate($compatibility_request, $params);
            }

            $result = explode(';', $result, 2);
            if ($result[0] === '0') {
                $errorData = json_decode($result[1], true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    throw new ApiException('', 400, null, $errorData);
                }

                throw new ApiException($result[1], 400);
            }
        }

        if (!_cfg('registerSkipLogin')
            && isset($request['useJwt'])
            && $request['useJwt'] == 1
            && !empty($_SESSION['user']['id'])
        ) {
            return [
                'result' => true,
                'jwtToken' => (new AuthResource())->setAccessJwtToken($_SESSION['user']['id']),
            ];
        }

        return [
            'result' => true
        ];
    }

    private function setVerifyEmailPhone(object $answer): void {
        User::setEmailVerified($answer->id, true);

        $userFields = json_decode($answer->additional_fields, true);
        if (!empty($userFields['phone_verified'])) {
            User::setPhoneVerified($answer->id, true);
        }
    }

    /**
     * @SWG\Get(
     *     path="/profiles",
     *     description="Returns user profile",
     *     tags={"user"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="User profile",
     *         @SWG\Schema(
     *             ref="#/definitions/UserProfile"
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * Get current user profile
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @return {array}
     * @throws {\Exception}
     */
    public function get($request, $query, $params = [])
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $user = new User();
        if (!empty($user->userData)) {
            $profileData = (array) $user->userData;
        } else {
            $profileData = (array) $user->getProfileData($_SESSION['user']['id']);
        }

        if (!empty($params['action']) && $params['action'] === 'verification') {
            $data = $user->getFundistUser(null, true);
            $data = explode(',', $data, 2);
            if ($data[0] !== '1') {
                return [];
            }

            if (!is_array($data[1])) {
                $profileData = json_decode($data[1], true);
            } else {
                Logger::log('Data is array: ' . json_encode([
                    'userID' => $user->id,
                    'data[1]' => $data,
                ]));
                $profileData = $data[1];
            }


            return [
                'VerificationJobID' => $profileData['VerificationJobID'] ?? null,
                'VerificationSessionID' => $profileData['VerificationSessionID'] ?? null,
                'DocumentType' => $profileData['VerificationDocumentType'] ?? null,
                'IsPassed' => $profileData['VerificationIsPassed'] ?? false,
                'DocumentStatus' => $profileData['VerificationStatus'] ?? null,
            ];
        }

        $fundistData = User::getInfo($_SESSION['user']['id']);
        if (!empty($fundistData['idUser'])) {
            $profileData['idUser'] = $fundistData['idUser'];
        }

        if (!empty($fundistData['socketsData'])) {
            $profileData['socketsData'] = $fundistData['socketsData'];
        }

        if (!is_array($profileData["additional_fields"])) {
            $additionalFields = json_decode($profileData["additional_fields"], true);
            if (
                _cfg('termsOfService') 
                && !empty($additionalFields) 
                && !empty($additionalFields['TosFixV1'])
            ) {
                $additionalFields = (new FixAdditionalFieldsUnicode($additionalFields))->run();
            }
        } else {
            Logger::log('Data is array: ' . json_encode([
                'userID' => $user->id,
                'additional_fields' => $profileData["additional_fields"],
            ]));
            $additionalFields = $profileData["additional_fields"];
        }


        if (!is_array($additionalFields)) {
            $additionalFields = array();
        }

        //to prevent not relevant data about socket server from user profile
        unset($additionalFields['socketsData']);

        if (!empty($fundistData['loyalty']['BonusRestrictions'])) {
            $BonusRestrictions = (!is_array($fundistData['loyalty']['BonusRestrictions'])) ? json_decode($fundistData['loyalty']['BonusRestrictions'], true) : $fundistData['loyalty']['BonusRestrictions'];
            if (!empty($BonusRestrictions)) {
                $additionalFields['RestrictCasinoBonuses'] = is_array($BonusRestrictions['RestrictCasinoBonuses'])
                    ? $BonusRestrictions['RestrictCasinoBonuses']['State'] ?? ''
                    : $BonusRestrictions['RestrictCasinoBonuses'];

                $additionalFields['RestrictSportBonuses'] = is_array($BonusRestrictions['RestrictSportBonuses'])
                    ? $BonusRestrictions['RestrictSportBonuses']['State'] ?? ''
                    : $BonusRestrictions['RestrictSportBonuses'];
            }
        }

        $additionalFields['type'] = $additionalFields['type'] ?: 'default';

        $userProfile = User::transformProfileData($additionalFields + $profileData, false); // This way additional fields are prioritezed
        $userProfile['RestrictCasinoBonuses'] = (int)$userProfile['RestrictCasinoBonuses'];
        $userProfile['RestrictSportBonuses'] = (int)$userProfile['RestrictSportBonuses'];
        $userProfile['countryCode'] = strtolower($userProfile['countryCode'] ?? '');

        // Add phone global + for phone number codes
        foreach(['phoneCode', 'altPhoneCode'] as $phoneCodeField) {
            if (empty($userProfile[$phoneCodeField]) || $userProfile[$phoneCodeField][0] == '+') {
                continue;
            }
            $userProfile[$phoneCodeField] = '+' . $userProfile[$phoneCodeField];
        }

        if (empty($userProfile['extProfile'])) {
            $userProfile['extProfile'] = [];
        }

        if (isset($userProfile['sendSMS'])) {
            $userProfile['smsAgree'] = (bool) $userProfile['sendSMS'];
        }
        if (isset($userProfile['sendEmail'])) {
            $userProfile['emailAgree'] = (bool) $userProfile['sendEmail'];
        }

        unset($userProfile['sendSMS'], $userProfile['sendEmail']);

        if (isset($userProfile['extProfile']['pep'])) {
            $userProfile['extProfile']['pep'] = filter_var($userProfile['extProfile']['pep'], FILTER_VALIDATE_BOOLEAN);
        }

        return $userProfile;
    }

}
