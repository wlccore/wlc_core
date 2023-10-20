<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\User;
/**
 * @SWG\Tag(
 *     name="payments",
 *     description="Payments"
 * )
 */


/**
 * @class LastSuccessfulDepositResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 */
class LastSuccessfulDepositResource extends AbstractResource
{

    /**
     * @SWG\Get(
     *     path="/lastSuccessfulDeposit",
     *     description="Returns last successful deposit made by a user.",
     *     tags={"payments"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Property(
     *             type="string",
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
     * Get last successful deposit
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     *
     * @return {array}
     * @throws {ApiException}
     */
    public function get($request, $query, $params)
    {
        if (empty($_SESSION['user'])) {
            throw new ApiException('User is not authorized', 401);
        }
        $user = new User();

        $result = $user->fetchLastSuccessfulPayment();

        return $result;
    }
}
