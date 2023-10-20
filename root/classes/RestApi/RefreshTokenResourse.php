<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Cache;
use eGamings\WLC\Core;
use eGamings\WLC\User;
use eGamings\WLC\RestApi\AuthResource;
use \Firebase\JWT\JWT;

/**
 * Class RefreshTokenResourse
 * @package eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Cache
 * @uses eGamings\WLC\User
 * @uses eGamings\WLC\RestApi\AuthResource
 * @uses \Firebase\JWT\JWT
 */
class RefreshTokenResourse extends AbstractResource
{
    private const TOKEN_LIFETIME_AFTER_REQUEST = 10;
    /**
     * @SWG\Put(
     *     path="/auth/refreshToken",
     *     description="Refresh jwt token",
     *     tags={"auth"},
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"token"},
     *             @SWG\Property(
     *                 property="token",
     *                 type="string",
     *                 description="Refresh token"
     *             )
     *         )
     *     ),
     *     @SWG\Parameter(
     *         ref="#/parameters/lang",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="New access and refresh jwt tokens",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="result",
     *                 type="object",
     *                 @SWG\Property(
     *                     property="jwtToken",
     *                     type="string",
     *                     description="New access jwt token"
     *                 ),
     *                 @SWG\Property(
     *                     property="refreshToken",
     *                     type="string",
     *                     description="New refresh jwt token"
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
     * @param $request
     * @param $query
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function put($request, $query, $params = [])
    {
        if (empty($request['token'])) {
            throw new ApiException('Token not provided', 400);
        }

        $jwt = $this->getRefreshJwtToken($request['token']);
        if (empty($jwt['user_id'])) {
            throw new ApiException('Invalid token', 400);
        }

        $key = 'Jwt_refresh_key_' . _cfg('websiteName');
        $oldTokenkey = 'Jwt_refresh_key_old_' . _cfg('websiteName');
        $storedToken = Cache::get($key, ['IDUser' => $jwt['user_id']], $key);

        if ($request['token'] === $storedToken) {
            $this->decreaseRefreshTokenTime($jwt, $storedToken, $oldTokenkey, $key);
            Core::getInstance()->sessionStart(false, true);
            $_SESSION['user'] = (array) (new User)->getUserById($jwt['user_id']);
            $AuthResource = new AuthResource();
            return [
                'result' => [
                    'jwtToken' => $AuthResource->setAccessJwtToken((int)$jwt['user_id']),
                    'refreshToken' => $AuthResource->setRefreshJwtToken($jwt['user_id']),
                ]
            ];
        }

        $oldStoredToken = Cache::get($oldTokenkey, ['IDUser' => $jwt['user_id']]);
        $accessTokenKey = 'Jwt_auth_key_' . _cfg('websiteName');
        if ($request['token'] === $oldStoredToken) {
            return [
                'result' => [
                    'jwtToken' => Cache::get($accessTokenKey, ['IDUser' => $jwt['user_id']]),
                    'refreshToken' => $storedToken,
                ]
            ];
        }
        
        throw new ApiException('Invalid Token', 400);
    }

    /**
     * @param array $jwt
     * @param string $storedToken
     * @param string $oldTokenkey
     * @param string $key
     * @return void
     */
    private function decreaseRefreshTokenTime(array $jwt, string $storedToken, string $oldTokenkey, string $key) : void
    {
        Cache::delete($key, ['IDUser' => $jwt['user_id']]);
        Cache::set($oldTokenkey, $storedToken, self::TOKEN_LIFETIME_AFTER_REQUEST, ['IDUser' => $jwt['user_id']]);
    }

    /**
     * @param string $token
     * @return array
     */
    private function getRefreshJwtToken(string $token): array
    {
        $key = 'Jwt_refresh_key_' . _cfg('websiteName');
        $jwt = null;
        try {
            $jwt = (array)JWT::decode($token, $key, ['HS256']);
            if (!empty($jwt['user_id'])) {
                return $jwt;
            }
        } catch (\Exception $e) {
        }

        return [];
    }
}
