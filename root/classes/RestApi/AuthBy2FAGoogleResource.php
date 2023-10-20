<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Auth2FAGoogle;
use eGamings\WLC\User;

/**
 * @SWG\Definition(
 *     definition="Auth2FAGoogle",
 *     description="Auth by Google 2FA",
 *     type="object",
 *     @SWG\Property(
 *         property="code",
 *         type="integer",
 *         example=200,
 *         description="Status code"
 *     ),
 *     @SWG\Property(
 *         property="status",
 *         type="string",
 *         example="success",
 *         description="Status message"
 *     ),
 *     @SWG\Property(
 *         property="data",
 *         type="boolean",
 *         example=true,
 *         description="Status auth 2FA Google"
 *     )
 * )
 */
class AuthBy2FAGoogleResource extends AbstractResource
{

     /**
     * @SWG\Post(
     *     path="/authBy/google2fa",
     *     description="Auth by Google 2FA",
     *     tags={"auth"},
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Property(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/Auth2FAGoogle"
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
    public function post($request, $query, $params = []){

        if (User::isAuthenticated()) {
            throw new ApiException(_('The user is already logged in'), 401);
        }

        $google2fa = new Auth2FAGoogle();

        if ($google2fa->checkEnable2FAGoogle()) {
            $resultCheck2FAGoogle = $google2fa->checkCodeForAuth($request['authKey'], $request['code2FA']);

            if ($resultCheck2FAGoogle === false) {
                if (!_cfg('disableRateLimiterGoogle2FA')) {
                    $lockTime = _cfg('lockTimeGoogle2FACode') ? _cfg('lockTimeGoogle2FACode') : 5;
                    $google2fa->rateLimiter(Auth2FAGoogle::POSTFIX_CODE2FA, $lockTime);
                }

                throw new ApiException("Two-factor authentication key is incorrect", 401);
            }
        }

        $email = $google2fa->getUserByAuthKey($request['authKey'])['email'];
        $google2fa->loginUserAfterCheck2FAGoogle($email, $request['authKey']);

        $data = (object) [
            'loggedIn' => "1",
            'tradingURL' => "" 
        ];

        return $data;
    }
}

