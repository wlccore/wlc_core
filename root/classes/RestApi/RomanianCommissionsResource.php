<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\User;
/**
 * @SWG\Tag(
 *     name="RomanianCommissionsResource",
 *     description="RomanianCommissionsResource"
 * )
 */


/**
 * @class RomanianCommissionsResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 */
class RomanianCommissionsResource extends AbstractResource
{

    /**
     * @SWG\Get(
     *     path="/commissions/romanian",
     *     description="Returns romanian tax",
     *     tags={"commissions"},
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
     * Get commission (tax) amount
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
    public function get(array $request, array $query, array $params): array
    {
        if (empty($_SESSION['user'])) {
            throw new ApiException('User is not authorized', 401);
        }
        $user = new User();

        $result = $user->getRomanianTax();

        return $result;
    }
}
