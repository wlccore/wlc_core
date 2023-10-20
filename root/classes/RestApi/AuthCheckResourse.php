<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Cache;
use eGamings\WLC\Core;

/**
 * Class RefreshTokenResourse
 * @package eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Cache
 */
class AuthCheckResourse extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/auth/check",
     *     description="check jwt token",
     *     tags={"auth"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="is user logged in",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="result",
     *                 type="object",
     *                 @SWG\Property(
     *                     property="loggedIn",
     *                     type="int",
     *                     description="is user logged in"
     *                 ),
     *             )
     *         )
     *     ),
     * )
     */

    /**
     * @param $request
     * @param $query
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function get($request, $query, $params = [])
    {
        $jwt = Core::getAccessJwtToken();

        $accessTokenKey = 'Jwt_auth_key_' . _cfg('websiteName');
        $token = Cache::get($accessTokenKey, ['IDUser' => $jwt['user_id']]);

        $isLoggedIn = !empty($token) && !empty($_SERVER['HTTP_AUTHORIZATION']) && 'Bearer ' . $token == $_SERVER['HTTP_AUTHORIZATION'];
        return [
            'result' => [
                'loggedIn' => (int)$isLoggedIn,
            ]
        ];
    }
}
