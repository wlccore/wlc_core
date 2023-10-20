<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Loyalty\LoyaltyInfoResource;
use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="loyalty",
 *     description="Loyalty"
 * )
 */

/**
 * @class LoyaltyResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Loyalty\LoyaltyInfoResource
 */
class LoyaltyResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/loyalty/levels",
     *     description="Returns user levels",
     *     tags={"loyalty"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns user levels",
     *         @SWG\Schema(
     *             type="object",
     *             example={"1": {"Name": "1", "Level": "1", "NextLevelPoints": "100", "ConfirmPoints": "30", "Coef": "1.000"}}
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
     * @SWG\Get(
     *     path="/loyalty/check_promocode",
     *     description="Check promo-code",
     *     tags={"loyalty"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="promocode",
     *         type="string",
     *         required=true,
     *         in="query"
     *     ),
     *     @SWG\Parameter(
     *         name="currency",
     *         type="string",
     *         required=true,
     *         in="query"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns user levels",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="result",
     *                 type="boolean"
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
     * Returns loyalty info by $params
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} $params
     * @return {array|mixed}
     * @throws {ApiException}
     */
    public function get($request, $query, $params)
    {
        $action = (!empty($params['action'])) ? $params['action'] : 'default';
        $result = [];

    	switch($action) {
            case 'levels':
                $result = LoyaltyInfoResource::UserLevels();
                break;
            case 'check_promocode':
                $user = new User();

                $status = ($user->isValidPromoCode(
                        $query['promocode'], 
                        $query['currency'], 
                        _cfg('language'), 
                        User::LOYALTY_LEVEL_INITIAL));

                $result = ["result" => $status];

                break;
        }

        return $result;
    }
}
