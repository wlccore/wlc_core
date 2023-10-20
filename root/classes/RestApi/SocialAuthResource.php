<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\SocialAuth;
use eGamings\WLC\Storage\CookieStorage;
use eGamings\WLC\Config;
use eGamings\WLC\System;

/**
 * @class SocialAuthResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\SocialAuth
 */
class SocialAuthResource extends AbstractResource
{
    /**
     * @SWG\Post(
     *     path="/auth/social",
     *     description="Returns the url for oauth registration",
     *     tags={"oauth"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"provider"},
     *             @SWG\Property(
     *                 property="provider",
     *                 type="string",
     *                 description="Social network",
     *                 example="tw"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns the url for oauth registration",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="authUrl",
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
     * Social registration
     *
     * @public
     * @method post
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params Url route params
     * @return {array}
     * @throws {\Exception}
     */
    public function post($request, $query, $params)
    {
        $this->checkCountryForbidden();

    	if (empty($request['provider'])) {
            throw new ApiException('Unknown provider', 400);
        }

        $social = new SocialAuth();
        $provider = $request['provider'];
        $response = array('authUrl' => $social->getAuthUrl($provider));

        return $response;
    }

    /**
     * @SWG\Get(
     *     path="/auth/social",
     *     description="Returns social parameters for social registration completion",
     *     tags={"oauth"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns social parameters for social registration completion",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="social",
     *                 type="string",
     *                 example="tw"
     *             ),
     *             @SWG\Property(
     *                 property="firstName",
     *                 type="string"
     *             ),
     *             @SWG\Property(
     *                 property="lastName",
     *                 type="string"
     *             ),
     *             @SWG\Property(
     *                 property="social_uid",
     *                 type="string",
     *                 description="User id in social network"
     *             ),
     *             @SWG\Property(
     *                 property="photo",
     *                 type="string"
     *             ),
     *             @SWG\Property(
     *                 property="email",
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
     * Returns social parameters for social registration completion.
     *
     * @public
     * @method get
     * @param {array} $request Request
     * @param {array} $query Query string variables
     * @param {array} $params Route params
     * @return {mixed}
     */
    public function get($request, $query, $params)
    {
        $result = CookieStorage::getInstance()->get('social_user_info') ?: [];

        return $result;
    }

    /**
     * @SWG\Put(
     *     path="/auth/social",
     *     description="Completes social registration process",
     *     tags={"oauth"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"currency", "email", "firstName", "lastName", "password", "social", "social_uid"},
     *             @SWG\Property(
     *                 property="currency",
     *                 type="string"
     *             ),
     *             @SWG\Property(
     *                 property="email",
     *                 type="string"
     *             ),
     *             @SWG\Property(
     *                 property="firstName",
     *                 type="string"
     *             ),
     *             @SWG\Property(
     *                 property="lastName",
     *                 type="string"
     *             ),
     *             @SWG\Property(
     *                 property="password",
     *                 type="string"
     *             ),
     *             @SWG\Property(
     *                 property="promo",
     *                 type="string",
     *                 description="Promo code"
     *             ),
     *             @SWG\Property(
     *                 property="registrationBonus",
     *                 type="string",
     *                 description="Bonus id"
     *             ),
     *             @SWG\Property(
     *                 property="social",
     *                 type="string",
     *                 description="Social network",
     *                 example="vk"
     *             ),
     *             @SWG\Property(
     *                 property="social_uid",
     *                 type="string",
     *                 description="User id in social network"
     *             ),
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="boolean"
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
     * Completes social registration process.
     *
     * @public
     * @method put
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     * @return {boolean}
     */
    public function put($request, $query, $params)
    {
        $social = new SocialAuth();

        return $social->completeRegistration($request);
    }
}
