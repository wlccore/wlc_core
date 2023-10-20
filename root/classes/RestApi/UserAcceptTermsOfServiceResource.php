<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Front;
use eGamings\WLC\User;

/**
 * @SWG\Definition(
 *     definition="TermsOfService",
 *     description="Terms of service",
 *     type="object",
 *     @SWG\Property(
 *         property="ToSVersion",
 *         type="string",
 *         description="Terms of Service version"
 *     ),
 *     @SWG\Property(
 *         property="AcceptDateTime",
 *         type="datetime",
 *         description="Accepted data-time"
 *     ),
 * )
 */

/**
 * @class UserInfoResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Front
 * @uses eGamings\WLC\User
 */
class UserAcceptTermsOfServiceResource extends AbstractResource
{
    /**
     * @SWG\Post(
     *     path="/TermsOfService",
     *     description="Accept terms of service to the current user",
     *     tags={"user"},
     *     @SWG\Response(
     *         response="200",
     *         description="Terms of service",
     *         @SWG\Schema(
     *             ref="#/definitions/TermsOfService"
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
     * Accept terms of service to the current user
     *
     * @public
     * @method post
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array}
     * @throws {\Exception}
     */
    public function post($request, $query, $params = [])
    {
        if (empty($_SESSION['user'])) {
            throw new ApiException(_('No user in session'), 401);
        }

        $login = Front::User('id');
        $termsOfService = User::acceptTersmOfService($login);

        if($termsOfService === false){
            throw new ApiException(_('Invalid user login or the parameter is not specified in the config.'), 401);
        }

        return $termsOfService;
    }

}
