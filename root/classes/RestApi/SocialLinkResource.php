<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\SocialAuth;

/**
 * @class SocialLinkResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\SocialAuth
 */
class SocialLinkResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/auth/socialLink",
     *     description="Returns list of social systems connected for user",
     *     tags={"oauth"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns list of social systems connected for user",
     *         @SWG\Schema(
     *             type="array",
     *             example={"tw", "vk"},
     *             @SWG\Items(
     *                 type="string"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Error",
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
     * Returns list of social systems connected for user
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @return {array}
     * @throws {ApiException}
     */
    public function get($request, $query)
    {
        if (empty($_SESSION['user'])) {
            throw new ApiException('', 401);
        }

        $social = new SocialAuth();
        $userId = (int)$_SESSION['user']['id'];

        return $social->getConnectedProviderList($userId);
    }

    /**
     * @SWG\Put(
     *     path="/auth/socialLink",
     *     description="Returns url for social account connection",
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
     *         description="Returns url for social account connection",
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
     * Returns url for social account connection.
     *
     * @public
     * @method put
     * @param {array} $request
     * @param {array} $query
     * @return {array}
     * @throws {ApiException}
     */
    public function put($request, $query)
    {
        if (empty($request['provider'])) {
            throw new ApiException('Unknown provider', 400);
        }

        $social = new SocialAuth();
        $provider = $request['provider'];
        $response = array('authUrl' => $social->getAuthUrl($provider));

        return $response;
    }

    /**
     * @SWG\Post(
     *     path="/auth/socialLink",
     *     description="Associates social account with wlc account. This must be used when social registration has detects existing email in WLC.",
     *     tags={"oauth"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"provider", "email"},
     *             @SWG\Property(
     *                 property="provider",
     *                 type="string",
     *                 description="Social network",
     *                 example="tw"
     *             ),
     *             @SWG\Property(
     *                 property="email",
     *                 type="string"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="boolean",
     *             example="true"
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
     * Associates social account with wlc account.
     * This must be used when social registration has detects existing email in WLC.
     *
     * @public
     * @method post
     * @param {array} $request
     * @param {array} $query
     * @return {array}
     * @throws {\Exception}
     */
    public function post($request, $query)
    {
        $social = new SocialAuth();
        $email = $request['email'];
        $provider = $request['provider'];

        return $social->linkSocialAccount($provider, $email);
    }

    /**
     * @SWG\Delete(
     *     path="/auth/socialLink",
     *     description="Disconnect social account",
     *     tags={"oauth"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="provider",
     *         in="query",
     *         required=true,
     *         type="string",
     *         description="Social network",
     *         default="tw"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="boolean",
     *             example="true"
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
     * Disconnect social account
     *
     * @public
     * @method delete
     * @param {array} $request
     * @param {array} $query
     * @return {array}
     */
    public function delete($request, $query)
    {
        $social = new SocialAuth();
        $provider = $query['provider'];

        return $social->disconnectSocialAccount($provider);
    }
}
