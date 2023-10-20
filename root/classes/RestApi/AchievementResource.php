<?php

namespace eGamings\WLC\RestApi;
use eGamings\WLC\Ajax;

/**
 * @SWG\Tag(
 *     name="achievement",
 *     description="Achievements"
 * )
 */


/**
 * @SWG\Definition(
 *     definition="Achievement",
 *     description="Achievement",
 *     type="object",
 *     @SWG\Property(
 *         property="Description",
 *         type="string",
 *         description="Achievement description"
 *     ),
 *     @SWG\Property(
 *         property="GroupName",
 *         type="string",
 *         description="Achievement group name"
 *     ),
 *     @SWG\Property(
 *         property="IDGroup",
 *         type="integer",
 *         example="12345",
 *         description="Achievement group ID"
 *     ),
 *     @SWG\Property(
 *         property="ID",
 *         type="integer",
 *         example="12345",
 *         description="Achievement ID"
 *     ),
 *     @SWG\Property(
 *         property="ImageActive",
 *         type="object",
 *         example={"en": "http://google.com/image.jpg"},
 *         description="Achievement image if user reached it"
 *     ),
 *     @SWG\Property(
 *         property="ImageNotActive",
 *         type="object",
 *         example={"en": "http://google.com/image.jpg"},
 *         description="Achievement image if user hadn't reached it"
 *     ),
 *     @SWG\Property(
 *         property="Name",
 *         type="string",
 *         description="Achievement name"
 *     ),
 *     @SWG\Property(
 *         property="Status",
 *         type="string",
 *         enum={"0", "1"},
 *         description="Achievement status (0 - not active, 1 - activated) *For specific user"
 *     )
 * )
 */

/**
 * @class BonusResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Ajax
 * @uses eGamings\WLC\Loyalty\LoyaltyBonusesResource
 */
class AchievementResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/achievements",
     *     description="Returns achievements list.",
     *     tags={"achievement"},
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Property(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/Achievement"
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
     * Returns achievements list
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     * @return {array|mixed}
     * @throws {ApiException}
     */
    public function get($request, $query, $params)
    {
        $ajax = new Ajax();
        $achievementsList = [];
        
        try {
            $achievementsList = $ajax->Achievement($query);
        } catch (\Exception $ex) {
            throw new ApiException($ex->getMessage(), $ex->getCode());
        }

        if (!is_array($achievementsList)) {
            throw new ApiException(_('Achievement result is not list'), 400);
        }

        return $achievementsList;
    }

}
