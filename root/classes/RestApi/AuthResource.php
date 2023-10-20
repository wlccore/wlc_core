<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Auth2FAGoogle;
use eGamings\WLC\NonceService;
use eGamings\WLC\Service\Captcha;
use eGamings\WLC\Service\TrustDevice;
use eGamings\WLC\Cache;
use eGamings\WLC\Config;
use eGamings\WLC\SessionControl;
use eGamings\WLC\User;
use eGamings\WLC\System;
use eGamings\WLC\Front;
use \Firebase\JWT\JWT;
use eGamings\WLC\Core;
use eGamings\WLC\PrometheusKeys;

/**
 * @SWG\Tag(
 *     name="auth",
 *     description="Login and logout"
 * )
 */

/**
 * @class AuthResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Cache
 * @uses eGamings\WLC\User
 * @uses eGamings\WLC\System
 * @uses eGamings\WLC\Front
 * @uses \Firebase\JWT\JWT
 */
class AuthResource extends AbstractResource
{
    private const ACCESS_TOKEN_LIFE_TIME = 30 * 60; // 30 min
    private const REFRESH_TOKEN_LIFE_TIME = 24 * 60 * 60; // 24 hours

    /**
     * @SWG\Put(
     *     path="/auth",
     *     description="Login in WLC",
     *     tags={"auth"},
     *     @SWG\Parameter(
     *         name="smsLogin",
     *         type="integer",
     *         in="query",
     *     ),
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"login", "password"},
     *             @SWG\Property(
     *                 property="login",
     *                 type="string",
     *                 description="User login or email"
     *             ),
     *             @SWG\Property(
     *                 property="password",
     *                 type="string",
     *                 description="User password"
     *             ),
     *             @SWG\Property(
     *                 property="useJwt",
     *                 type="integer",
     *                 description="use jwt instead of cookies."
     *             ),
     *              @SWG\Property(
     *                 property="walletAddress",
     *                 type="string",
     *                 description="Wallet address"
     *             ),
     *              @SWG\Property(
     *                 property="message",
     *                 type="string",
     *                 description="Message"
     *             ),
     *              @SWG\Property(
     *                 property="signature",
     *                 type="string",
     *                 description="Signature"
     *             ),
     *              @SWG\Property(
     *                 property="phoneNumber",
     *                 type="string",
     *                 description="Phone number"
     *             ),
     *              @SWG\Property(
     *                 property="phoneCode",
     *                 type="string",
     *                 description="Phone code"
     *             ),
     *         )
     *     ),
     *     @SWG\Parameter(
     *         ref="#/parameters/lang",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="If authorization is successful",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="result",
     *                 type="object",
     *                 @SWG\Property(
     *                     property="loggedIn",
     *                     type="string",
     *                     example="1"
     *                 ),
     *                 @SWG\Property(
     *                     property="tradingURL",
     *                     type="string",
     *                     description="Url for https://www.tradingview.com/"
     *                 ),
     *                 @SWG\Property(
     *                     property="jwtToken",
     *                     type="string",
     *                     description="jwtToken returns in case system use jwt instead of cookies. For future requests [Authorization: Bearer <jwtToken>]"
     *                 )
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
     * Login
     *
     * @codeCoverageIgnore
     * @public
     * @method put
     * @param array $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array}
     * @throws {\Exception}
     */
    public function put($request, $query, $params = [])
    {
        PrometheusKeys::getInstance()->AUTH_START->store();

        $fastPhoneRegistration = _cfg('fastPhoneRegistration') && empty($request['email']) && empty($request['login']);
        $loginType = User::LOGIN_TYPE_DEFAULT;
        if ((isset($query['smsLogin']) && $query['smsLogin'] == 1) || $fastPhoneRegistration) {
            $loginType = User::LOGIN_TYPE_SMS;
        } elseif (!empty($request['walletAddress']) && !empty($request['message']) && !empty($request['signature'])) {
            if (!_cfg('useMetamask')) {
                PrometheusKeys::getInstance()->AUTH_METAMASK_NOT_ALLOWED->store();
                throw new ApiException(_('login_metamask_not_allowed'), 403);
            }

            $loginType = User::LOGIN_TYPE_METAMASK;
        }

        if (_cfg('enableForbidden') !== false || _cfg('enableForbiddenLogin') !== true) {
            $this->checkCountryForbidden();
        }

        $user = new User();

        switch ($loginType) {
            case User::LOGIN_TYPE_SMS:
                    $compatibility_request = $fastPhoneRegistration ? [
                        'phoneCode' => $request['phoneCode'],
                        'phoneNumber' => $request['phoneNumber'],
                        'pass' => $request['password'],
                    ] : [
                        'phoneCode' => $request['phoneCode'],
                        'phoneNumber' => $request['phoneNumber'],
                        'code' => $request['code'],
                    ];
                    $uniqueUserMark = $compatibility_request['phoneCode'] . $compatibility_request['phoneNumber'];
                break;
            case User::LOGIN_TYPE_METAMASK:
                $compatibility_request = [
                    'walletAddress' => $request['walletAddress'],
                    'message' => $request['message'],
                    'signature' => $request['signature'],
                ];
                $uniqueUserMark = $request['walletAddress'];
                break;
            default:
                $compatibility_request = [
                    'login' => $request['login'],
                    'pass' => $request['password'],
                ];
                $uniqueUserMark = $request['login'];

                if (_cfg('loginBy') === 'all' && (int)_cfg('registerUniquePhone') === 1
                    && !empty($request['phoneCode']) && !empty($request['phoneNumber'])
                    && empty($request['login'])
                ) {
                    $compatibility_request = [
                        'phoneCode' => $request['phoneCode'],
                        'phoneNumber' => $request['phoneNumber'],
                        'pass' => $request['password'],
                    ];
                    $uniqueUserMark = $compatibility_request['phoneCode'] . $compatibility_request['phoneNumber'];
                }
                break;
        }

        $compatibility_request['FingerPrint'] = !empty($_SERVER['HTTP_X_UA_FINGERPRINT']) ? $_SERVER['HTTP_X_UA_FINGERPRINT'] : '';
        $result = $user->login($compatibility_request, false, $loginType);
        $result = explode(';', $result, 3);

        if ($result[0] !== '1') {
            if (_cfg('enableCaptcha')) {
                (new Captcha($uniqueUserMark))->addAttempt();
            }

            $answer = strstr($result[1], 'API request failed') === false ? $result[1] : _('UnexpectedError');
            throw new ApiException($answer, 403);
        }


        $loggedUser = new User();
        $notifications = [];

        if ($loginType !== User::LOGIN_TYPE_METAMASK) {
            if (_cfg('trustDevicesEnabled') === TrustDevice::$STATUS_ALWAYS) {
                $trustDeviceService = new TrustDevice($loggedUser);

                $this->delete($request, $query, $params);
                $trustDeviceService->sendConfirmationEmail();
            }

            if (System::isCountry2FAForbidden($loggedUser)) {
                if (_cfg('trustDevicesEnabled') === TrustDevice::$STATUS_TRUSTED || _cfg('trustDevicesEnabled') === true) {
                     if ($loggedUser->userData->email !== '') {
                        $trustDeviceService = new TrustDevice($loggedUser);
                        $error = null;
                        try {
                            if (!$trustDeviceService->checkDevice($error)) {
                                $this->delete($request, $query, $params);

                                $trustDeviceService->sendConfirmationEmail();
                            }
                        } catch (\Exception $e) {
                            $code = $e->getCode() == 418 ? 418 : 403;
                            throw new ApiException($e->getMessage(), $code);
                        }
                    }
                }
            }
        }

        if ((bool) _cfg('fastRegistrationWithoutBets') && !$loggedUser->userData->email_verified) {
            $tempUserData = $loggedUser->getUsersTempByEmail($loggedUser->userData->email);

            if ($tempUserData->id === null) {
                $loggedUser->serviceApplyAndSendConfirmationEmail($request);
                $notifications[] = _('Confirm your email to start games');
            }
        }

        error_log("Successful login. IDUser: {$loggedUser->userData->id}");
        PrometheusKeys::getInstance()->AUTH_SESSION_START->store();

        $data = (object) [
            'loggedIn' => $result[0],
            'tradingURL' => $result[1]
        ];

        if (isset($request['useJwt']) && $request['useJwt']) {
            $data->jwtToken = $this->setAccessJwtToken((int)$loggedUser->userData->id);
            $data->refreshToken = $this->setRefreshJwtToken((int)$loggedUser->userData->id);
        }

        if (_cfg('enableFastTrackAuthentication')) {
            $data->sid = $result[2] ?? '';
        }

        if (_cfg('recaptchaLog')) {
            error_log("XXX UserID = " . ($loggedUser->userData->id ?? '') . "; IP = " . $_SERVER['REMOTE_ADDR']);
        }

        switch ($loginType) {
            case User::LOGIN_TYPE_SMS:
                PrometheusKeys::getInstance()->AUTH_SMS_TYPE->store();
                break;
            case User::LOGIN_TYPE_METAMASK:
                PrometheusKeys::getInstance()->AUTH_METAMASK_TYPE->store();
                break;
            default:
                PrometheusKeys::getInstance()->AUTH_DEFAULT_TYPE->store();
                break;
        }
        System::hook('api:auth:put', $data);

        PrometheusKeys::getInstance()->HOOK_AUTH_PUT->store();
        return array(
            'result' => (array) $data,
            'notifications' => (array) $notifications
        );
    }

    /**
     * set access jwt token
     *
     * @param int $IDUser
     * @return string
     */
    public function setAccessJwtToken(int $IDUser = 0): string
    {
        $key = 'Jwt_auth_key_' . _cfg('websiteName');
        $payload = [
            'user_id' => $IDUser,
            'jti' => session_id(),
            'iss' => _cfg('websiteName'),
            'sub' => "auth",
            'iat' => time(),
            'exp' => time() + self::ACCESS_TOKEN_LIFE_TIME,
        ];
        $token = JWT::encode($payload, $key);
        Cache::set($key, $token, self::ACCESS_TOKEN_LIFE_TIME, ['IDUser' => $IDUser]);
        return $token;
    }

    /**
     *  set refresh jwt token
     *
     * @param int $IDUser
     * @return string
     */
    public function setRefreshJwtToken(int $IDUser = 0): string
    {
        $key = 'Jwt_refresh_key_' . _cfg('websiteName');
        $payload = [
            'user_id' => $IDUser,
            'iss' => _cfg('websiteName'),
            'sub' => "refresh",
            'iat' => time(),
            'exp' => time() + self::REFRESH_TOKEN_LIFE_TIME,
        ];
        $token = JWT::encode($payload, $key);
        Cache::set($key, $token, self::REFRESH_TOKEN_LIFE_TIME, ['IDUser' => $IDUser]);
        return $token;
    }

    /**
     * @SWG\Delete(
     *     path="/auth",
     *     description="Logout from WLC",
     *     tags={"auth"},
     *     @SWG\Response(
     *         response="200",
     *         description="If user successfuly logout",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="result",
     *                 type="object",
     *                 @SWG\Property(
     *                     property="loggedIn",
     *                     type="string",
     *                     example="0"
     *                 )
     *             )
     *         )
     *     )
     * )
     */

    /**
     * Logout
     *
     * @public
     * @method delete
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array}
     */
    public function delete($request, $query, $params = [])
    {
        $user = new User();

        $user->logout();

        // Remove tradingURL from userData
        $user = Front::get('_user');
        if (is_object($user) && is_object($user->userData)) $user->userData->tradingURL = null;

        $data = (object) [
            'loggedIn' => '0'
        ];

        $this->deleteTokens();

        System::hook('api:auth:delete', $data);
        PrometheusKeys::getInstance()->HOOK_SESSION_DELETE->store();
        return array(
            'result' => (array) $data
        );
    }

    /**
     * delete refresh token
     * @return void
     */
    private function deleteTokens() : void
    {
        $jwt = Core::getAccessJwtToken();
        $refreshTokenKey = 'Jwt_refresh_key_' . _cfg('websiteName');
        $accessTokenKey = 'Jwt_auth_key_' . _cfg('websiteName');

        if (isset($jwt['user_id'])) {
            Cache::delete($refreshTokenKey, ['IDUser' => $jwt['user_id']]);
            Cache::delete($accessTokenKey, ['IDUser' => $jwt['user_id']]);
        }
    }
}
