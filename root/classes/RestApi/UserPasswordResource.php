<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Front;
use eGamings\WLC\User;
use eGamings\WLC\Utils;
use eGamings\WLC\Sms;

/**
 * @SWG\Tag(
 *     name="user",
 *     description="User"
 * )
 */

/**
 * @class UserPasswordResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Front
 * @uses eGamings\WLC\User
 */
class UserPasswordResource extends AbstractResource
{
    /**
     * @SWG\Post(
     *     path="/userPassword",
     *     description="Restore user password. Sends email with link to reset password.",
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
     *                 type="string",
     *                 description="User email"
     *             ),
     *              @SWG\Property(
     *                 property="phoneNumber",
     *                 type="integer",
     *                 description="Phone number"
     *             ),
     *              @SWG\Property(
     *                 property="phone",
     *                 type="integer",
     *                 description="Phone number, works same as phoneNumber param"
     *             ),
     *              @SWG\Property(
     *                 property="phoneCode",
     *                 type="string",
     *                 description="Phone code"
     *             ),
     *              @SWG\Property(
     *                 property="sendSmsCode",
     *                 type="boolean",
     *                 description="flag to make password reset using sms"
     *             ),
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="result",
     *                 type="string",
     *                 description="Success message"
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
     * Initiate password change process.
     *
     * @public
     * @method post
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array}
     * @throws {ApiException}
     */
    public function post($request, $query, $params = [])
    {
        if (_cfg('enableForbidden') !== false || _cfg('enableForbiddenLogin') !== true) {
             $this->checkCountryForbidden();
        }

        if ($request['sendSmsCode'] && (isset($request['phoneNumber']) || isset($request['phone']))) {
            $result = Sms::sendSmsPasswordRestoreCode($request['phoneNumber'] ?? $request['phone']);
            $result = explode(";", $result);

            if ((int)$result[0] == 0) {
                throw new ApiException($result[1], 422);
            }

            return ['result' => $result[1]];
        }

        $user = new User();
        $restorePasswordUrl = _cfg('restorePasswordUrl') ?: '';
        $restorePasswordUrl = str_replace('%language%', _cfg('language'), $restorePasswordUrl);

        $request['redirectUrl'] = _cfg('site') . '/'.$restorePasswordUrl.'?message=SET_NEW_PASSWORD';

        $result = $user->checkIfEmailExist($request);
        $result = explode(';', $result, 2);
        if ($result[0] === '1') {
            $user->logUserData('init restore', json_encode($request).json_encode($query).json_encode($params));
        }

        if (_cfg('hideEmailExistence')) {
            $message = true;
        } else {
            if ($result[0] != '1' && !empty($result[1]) && _cfg('sendRestoreError')) {
                throw new ApiException($result[1], 422);
            }

            $message = _('Email sent, recovery link will be available for 30 minutes');
        }

        return ['result' => $message];
    }

    /**
     * @SWG\Put(
     *     path="/userPassword",
     *     description="Restore user password. Changes a user's password",
     *     tags={"user"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"newPassword", "repeatPassword", "code"},
     *             @SWG\Property(
     *                 property="newPassword",
     *                 type="string",
     *                 description="New password"
     *             ),
     *             @SWG\Property(
     *                 property="repeatPassword",
     *                 type="string",
     *                 description="Repeat new password"
     *             ),
     *             @SWG\Property(
     *                 property="code",
     *                 type="string",
     *                 description="Restoration code"
     *             ),
     *             @SWG\Property(
     *                 property="phoneNumber",
     *                 type="integer",
     *                 description="Phone number"
     *             ),
     *             @SWG\Property(
     *                 property="phoneCode",
     *                 type="string",
     *                 description="Phone number"
     *             ),
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
     * Confirm password change.
     *
     * @public
     * @method put
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array}
     * @throws {ApiException}
     */
    public function put($request, $query, $params = [])
    {
        if (_cfg('enableForbidden') !== false || _cfg('enableForbiddenLogin') !== true) {
             $this->checkCountryForbidden();
        }

        $fastPhoneRegistration = _cfg('fastPhoneRegistration') && empty($request['email'])
            && empty($request['repeatPassword']) && empty($request['newPassword']);

        $user = new User();

        if ($fastPhoneRegistration) {
            $user->logUserData('confirm restore', json_encode(json_encode($request).json_encode($query).json_encode($params)));
            $newPassword = $user->generatePassword();
            $code = !empty($request['code']) ? trim($request['code']) : '';

            $data = [];
            $data['phoneNumber'] = (int)trim($request['phoneNumber'], '+- ');
            $data['phoneCode'] = '+'.(int)trim($request['phoneCode'], '+- ');

            $restoreData = $user->checkRestoreCode($code);

            if (!$restoreData) {
                throw new ApiException(_('Code is not valid'), 400);
            }
        } else {
            $user->logUserData('confirm restore', json_encode(Utils::obfuscatePassword($request)) . json_encode($query) . json_encode($params));
            $newPassword = !empty($request['newPassword']) ? $request['newPassword'] : '';
            $repeatPassword = !empty($request['repeatPassword']) ? $request['repeatPassword'] : '';
            $code = !empty($request['code']) ? trim($request['code']) : '';

            if (empty($newPassword)) {
                throw new ApiException(_('New password is empty'), 400);
            }

            if (empty($repeatPassword)) {
                throw new ApiException(_('Password repeat is empty'), 400);
            }

            $restoreData = $user->checkRestoreCode($code);
            if (!$restoreData) {
                throw new ApiException(_('Code is not valid'), 400);
            }

            if (_cfg('PasswordSecureLevel') === 'custom:lowest' && strlen($newPassword) < 5) {
                throw new ApiException(_('Password must be at least 5 characters long'), 400);
            }

            if (_cfg('PasswordSecureLevel') === 'medium' && strlen($newPassword) < 6) {
                throw new ApiException(_('Password must be at least 6 characters long'), 400);
            }

            if (!$user->testPassword($newPassword)) {
                throw new ApiException(_('New password may contain only latin letters, numbers and special symbols'), 400);
            }

            //Password is to simple and must have special characters, numbers, upper, and lower case simbols
            if (!$user->checkPassword($newPassword)) {
                if (_cfg('PasswordSecureLevel') == 'strong') {
                    throw new ApiException(_('Password must contain latin letters in upper and lower case, numbers and special symbols'), 400);
                } else if (_cfg('PasswordSecureLevel') == 'super-strong') {
                    throw new ApiException(_('Password must be at least 8 characters and contain latin letters and numbers'), 400);
                } else {
                    throw new ApiException(_('Password must contain latin letters and numbers'), 400);
                }
            }

            if ($newPassword != $repeatPassword) {
                throw new ApiException(_('Passwords do not match'), 400);
            }

        }

        $loginByPhone = false;

        if (empty($restoreData['email'])) {
            $loginByPhone = true;
            $restoreData['phone'] = (int)trim($restoreData['phone'], '+- ');
            $restoreData['phoneNumber'] = substr($restoreData['phone'], 1);
            $restoreData['phoneCode'] = substr($restoreData['phone'], 0, 1);

            unset($restoreData['phone']);
        }

        if ($fastPhoneRegistration) {
            $result = $user->restorePassword($data, $newPassword, $restoreData['code'], _cfg('restoreSkipLogin') ? false : true, true);
        } else if ($loginByPhone) {
            $result = $user->restorePassword($restoreData, $newPassword, $restoreData['code'], _cfg('restoreSkipLogin') ? false : true, false, $loginByPhone);
        } else {
            $result = $user->restorePassword($fastPhoneRegistration ? $data : $restoreData['email'], $newPassword, $restoreData['code'], _cfg('restoreSkipLogin') ? false : true, $fastPhoneRegistration);
        }

        $result = explode(';', $result, 2);

        if ($result[0] === '0') {
            throw new ApiException($result[1], 400);
        }

        return ['result' => true];
    }

    /**
     * @SWG\Get(
     *     path="/userPassword/check",
     *     description="Compares the transmitted password with the password on the server",
     *     tags={"user"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="password",
     *         type="string",
     *         in="query",
     *         required=true
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Result of the check",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="checked",
     *                 type="boolean"
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
     * @SWG\Get(
     *     path="/userPassword/checkRestoreCode",
     *     description="Compares the transmitted password with the password on the server",
     *     tags={"user"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="code",
     *         type="string",
     *         in="query",
     *         required=true,
     *         description="Restoration code"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Result of the check",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="email",
     *                 type="string"
     *             ),
     *             @SWG\Property(
     *                 property="code",
     *                 type="string"
     *             ),
     *             @SWG\Property(
     *                 property="time",
     *                 type="integer",
     *                 description="Created time (timestamp)",
     *                 example="1501071302"
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
     * Check params of request
     * (password or code)
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     * @return {array}
     * @throws {ApiException}
     */
    public function get($request, $query, $params)
    {
        $action = !empty($params['action']) ? $params['action'] : (!empty($request['action']) ? $request['action'] : null);

        switch ($action) {
            case 'check':
                return $this->check($request['password']);
            case 'checkLinkRestore':
            case 'checkRestoreCode':
                return $this->checkRestoreCode(trim($request['code']));
            case  'resetPassword':
                $restoreData = $this->checkRestoreCode(trim($request['code']));

                if ($restoreData) {
                    $user = new User();
                    $user->emailResetPassword($restoreData);
                    $user->deleteRestoreCode($restoreData['code']);
                    return 1;
                } else {
                    if (empty($request['code'])) {
                        throw new ApiException(_('Code is empty'), 400);
                    }
                    if (empty($restoreData)) {
                        throw new ApiException(_('Code is invalid'), 400);
                    }
                }
            default:
                throw new ApiException(_('Method not allowed'), 404);
        }
    }

    /**
     * @SWG\Patch(
     *     path="/userPassword",
     *     description="Change password of the current user",
     *     tags={"user"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"password", "newPassword"},
     *             @SWG\Property(
     *                 property="password",
     *                 type="string",
     *                 description="Current password"
     *             ),
     *             @SWG\Property(
     *                 property="newPassword",
     *                 type="string",
     *                 description="New password"
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
     *                 type="number",
     *                 example="1"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Must login",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
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
     * Update current user password logged
     *
     * @public
     * @method patch
     * @param $request
     * @param $query
     * @param $params
     * @throws ApiException
     */
    public function patch($request, $query, $params) {
        if (empty($_SESSION['user'])) {
            throw new ApiException(_('must_login'), 401);
        }

        if (empty($request['password'])) {
            throw new ApiException(_('Password is empty'), 400);
        }
        $password = $request['password'];

        if (empty($request['newPassword'])) {
            throw new ApiException(_('New password is empty'), 400);
        }
        $newPassword = trim($request['newPassword']);

        $user = new User();
        $user->logUserData('change password', json_encode(Utils::obfuscatePassword($request)).json_encode($query).json_encode($params));
        $userData = $user->getUserById(Front::User('id'));
        $userPassword = $userData->password;

        $passwordValid = $user->verifyPassword($password, $userPassword);
        if (!$passwordValid) {
            throw new ApiException(_('Password is invalid'), 400);
        }

        if (_cfg('PasswordSecureLevel') === 'custom:lowest' && strlen($newPassword) < 5) {
            throw new ApiException(_('Password must be at least 5 characters long'), 400);
        }

        if (_cfg('PasswordSecureLevel') === 'medium' && strlen($newPassword) < 6) {
            throw new ApiException(_('Password must be at least 6 characters long'), 400);
        }

        //Password is to simple and must have special characters, numbers, upper, and lower case simbols
        if (!$user->checkPassword($newPassword)) {
            if (_cfg('PasswordSecureLevel') == 'strong') {
                throw new ApiException(_('Password must contain latin letters in upper and lower case, numbers and special symbols'), 400);
            } else if (_cfg('PasswordSecureLevel') == 'super-strong') {
                throw new ApiException(_('Password must be at least 8 characters and contain latin letters and numbers'), 400);
            } else {
                throw new ApiException(_('Password must contain latin letters and numbers'), 400);
            }
        }

        $fastReg = _cfg('fastPhoneRegistration') && empty($userData->email);
        if ($fastReg) {
            $data = [
                'phoneCode'   => $userData->phone1,
                'phoneNumber' => $userData->phone2
            ];
        } else {
            $data = $userData->email;
        }

        $passwordResult = $user->restorePassword($data, $newPassword, null, true, $fastReg);

        $passwordResultArr = explode(';', $passwordResult, 2);
        if ($passwordResultArr[0] !== '1') {
            throw new ApiException($passwordResultArr[1], 400);
        }

        return [
            'result' => $passwordResultArr[1]
        ];
    }

    /**
     * Check current password
     *
     * @public
     * @method check
     * @param {string} $password
     * @return {array}
     * @throws {ApiException}
     */
    public function check($password) {

        if (empty($_SESSION['user'])) {
            throw new ApiException('', 401);
        }

        $user = new User();
        $userData = $user->getUserById(Front::User('id'));
        $userPassword = $userData->password;

        return array('checked' => $user->verifyPassword($password, $userPassword));
    }

    /**
     * Check current restore link
     *
     * @public
     * @method checkRestoreCode
     * @param {string|int} [$code='']
     * @return {array}
     * @throws {ApiException}
     */
    public function checkRestoreCode($code = '')
    {
        if (empty($code)) {
            throw new ApiException(_('Code is empty'), 400);
        }

        $user = new User();
        $restoreData = $user->checkRestoreCode($code);
        if (empty($restoreData)) {
            throw new ApiException(_('Code is invalid'), 400);
        }

        return $restoreData;
    }
}
