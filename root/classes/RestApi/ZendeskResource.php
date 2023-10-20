<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\User;
use \Firebase\JWT\JWT;

/**
 * @SWG\Tag(
 *     name="zendesk resource",
 *     description="Get Zendesk JWT token"
 * )
 */

/**
 * Class ZendeskResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\User
 * @uses \Firebase\JWT\JWT
 */
class ZendeskResource extends AbstractResource
{
    private $key;
    private $ttl;

    /**
     * ZendeskResource constructor.
     * @throws ApiException
     */
    function __construct()
    {
        $config = _cfg('zendesk');
        if (empty($config['key'])) {
            throw new ApiException(_('Zendesk not configured'), 400);
        }

        $this->key = $config['key'];
        $this->ttl = empty($config['ttl']) || (int)$config['ttl'] < 0 || (int)$config['ttl'] > 7
            ? 5
            : (int)$config['ttl'];
    }

    /**
     * @SWG\Get(
     *     path="/zendesk",
     *     description="Get Zendesk JWT token",
     *     tags={"zendesk resource"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
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
     * @param $request
     * @param $query
     * @param array $params
     * @return string
     * @throws ApiException
     */
    public function get($request, $query, $params = [])
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $User = User::getInstance();

        $payload = [
            'name' => $User->userData->first_name . ' ' . $User->userData->last_name,
            'email' => $User->userData->email,
            'external_id' => _cfg('websiteName') . '_' . $User->userData->id,
            'iat' => time(),
            'exp' => time() + $this->ttl * 60,
        ];

        return JWT::encode($payload, $this->key, 'HS256');
    }

}
