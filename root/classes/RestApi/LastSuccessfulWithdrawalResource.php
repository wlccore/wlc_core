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
 * @class LastSuccessfulWithdrawalResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 */
class LastSuccessfulWithdrawalResource extends AbstractResource
{

    /**
     * @SWG\Get(
     *     path="/lastSuccessfulWithdrawal",
     *     description="Returns last successful withdrawal made by a user.",
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
     * Get last successful withdrawal
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
        if (!User::isAuthenticated()) {
            throw new ApiException('User is not authorized', 401);
        }

        $user = User::getInstance();

        return $user->fetchLastSuccessfulPayment('Withdrawal');
    }
}

