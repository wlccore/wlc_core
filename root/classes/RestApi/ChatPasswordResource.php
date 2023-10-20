<?php

declare(strict_types=1);

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Config;
use eGamings\WLC\Front;
use eGamings\WLC\User;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;

/**
 * @SWG\Tag(
 *     name="chat password",
 *     description="Chat password"
 * )
 *
 * @SWG\Definition(
 *     definition="ChatPassword",
 *     description="Chat password object",
 *     type="object",
 *     @SWG\Property(
 *         property="login",
 *         type="string",
 *         description="Login",
 *         example="2043",
 *     ),
 *     @SWG\Property(
 *         property="password",
 *         type="string",
 *         description="Password",
 *         example="test123",
 *     ),
 * )
 */
class ChatPasswordResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/chat/password",
     *     description="Returns generated TOTP password for user",
     *     tags={"chat"},
     *     @SWG\Response(
     *         response="200",
     *         description="Chat password object",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/ChatPassword"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="User is not authorized",
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
     *
     * @throws ApiException
     */
    public function get(?array $request, array $query, array $params = []): array
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $user = Front::User();
        $siteConfig = Config::getSiteConfig();

        $secret = md5($user->api_password);
        $token = TOTP::create(Base32::encodeUpper($secret))->now();

        return [
            'login' => "{$siteConfig['IDApi']}_{$user->id}",
            'password' => $token,
        ];
    }
}
