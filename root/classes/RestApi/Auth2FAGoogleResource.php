<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Auth2FAGoogle;
use eGamings\WLC\User;

/**
 * @SWG\Definition(
 *     definition="2FAGoogle",
 *     description="2FAGoogle",
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
 *         type="string",
 *         example="otpauth://totp/Templatecasino:nikita.tsurkan%2Bq1%40softgamings.com?secret=MW2Q4LWEO4UD5DRW&issuer=Templatecasino&algorithm=SHA1&digits=6&period=30",
 *         description="path for generate QR code"
 *     )
 * )
 */

/**
 * @SWG\Definition(
 *     definition="Reset2FAGoogle",
 *     description="Reset settings 2FAGoogle",
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
 *         description="Status reset 2FA Google"
 *     )
 * )
 */
class Auth2FAGoogleResource extends AbstractResource
{
     /**
     * @SWG\Put(
     *     path="/auth/2fa/google",
     *     description="Begin enable 2FA Google",
     *     tags={"auth"},
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Property(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/2FAGoogle"
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
    public function put($request, $query, $params = []) {

        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $google2fa = new Auth2FAGoogle();

        return $google2fa->enable();
    }

     /**
     * @SWG\Post(
     *     path="/auth/2fa/google",
     *     description="Verified enable 2FA Google",
     *     tags={"auth"},
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Property(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/2FAGoogle"
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
    public function post($request, $query, $params = []) {

        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $google2fa = new Auth2FAGoogle();

        return $google2fa->verifiedEnable($request['code2FA']);
    }

     /**
     * @SWG\Patch(
     *     path="/auth/2fa/google",
     *     description="Disable notify",
     *     tags={"auth"},
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Property(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/2FAGoogle"
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

    public function patch($request, $query, $params = []) {

        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $google2fa = new Auth2FAGoogle();

        return $google2fa->disableNotify();
    }

    /**
    * @SWG\Delete(
    *     path="/auth/2fa/google",
    *     description="Reset 2FA google",
    *     tags={"auth"},
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Property(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/Reset2FAGoogle"
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

    public function delete($request, $query, $params = []) {

        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $google2fa = new Auth2FAGoogle();

        return $google2fa->disable();
    }
}

