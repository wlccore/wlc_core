<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Front;
use eGamings\WLC\Service\CountryNonResidence;
use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="user info",
 *     description="User info"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="UserInfo",
 *     description="User info",
 *     type="object",
 *     @SWG\Property(
 *         property="balance",
 *         type="number",
 *         description="User balance"
 *     ),
 *     @SWG\Property(
 *         property="idUser",
 *         type="string",
 *         description="User unique identifier (from fundist)"
 *     ),
 *     @SWG\Property(
 *         property="firstName",
 *         type="string",
 *         description="User first name"
 *     ),
 *     @SWG\Property(
 *         property="lastName",
 *         type="string",
 *         description="User last name"
 *     ),
 *     @SWG\Property(
 *         property="email",
 *         type="string",
 *         description="User email"
 *     ),
 *     @SWG\Property(
 *         property="availableWithdraw",
 *         type="number",
 *         description="Balance available for withdrawal"
 *     ),
 *     @SWG\Property(
 *         property="pincode",
 *         type="string",
 *         description="Pincode for user"
 *     ),
 *     @SWG\Property(
 *         property="loyalty",
 *         type="object",
 *         @SWG\Property(
 *             property="IDUser",
 *             type="string",
 *             description="User id in loyalty",
 *             example="140802"
 *         ),
 *         @SWG\Property(
 *             property="Balance",
 *             type="string",
 *             description="User loyalty balance"
 *         ),
 *         @SWG\Property(
 *             property="Login",
 *             type="string",
 *             description="User login"
 *         ),
 *         @SWG\Property(
 *             property="Country",
 *             type="string",
 *             description="User country (iso3)",
 *             example="rus"
 *         ),
 *         @SWG\Property(
 *             property="Language",
 *             type="string",
 *             description="User language",
 *             example="en"
 *         ),
 *         @SWG\Property(
 *             property="Currency",
 *             type="string",
 *             description="User currency",
 *             example="EUR"
 *         ),
 *         @SWG\Property(
 *             property="Level",
 *             type="string",
 *             description="User loyalty level",
 *             example="9"
 *         ),
 *         @SWG\Property(
 *             property="Points",
 *             type="string",
 *             description="Loyalty points at current level",
 *             example="10.0000"
 *         ),
 *         @SWG\Property(
 *             property="NextLevelPoints",
 *             type="string",
 *             description="The number of loyalty points to the next level",
 *             example="90"
 *         ),
 *         @SWG\Property(
 *             property="TotalPoints",
 *             type="string",
 *             description="Total number of loyalty points of the user",
 *             example="275.3000"
 *         ),
 *         @SWG\Property(
 *             property="TotalBets",
 *             type="string",
 *             description="Bets for all time",
 *             example="333"
 *         ),
 *         @SWG\Property(
 *             property="CheckDate",
 *             type="string",
 *             description="Date when recalculate users level",
 *             example="2017-03-25"
 *         ),
 *         @SWG\Property(
 *             property="ConfirmPoints",
 *             type="string",
 *             description="Points that player need to earn to keep at current level in a check date",
 *             example="9"
 *         ),
 *         @SWG\Property(
 *             property="LevelName",
 *             type="object",
 *             description="Level name translates",
 *             example={"en": "Level name"}
 *         ),
 *         @SWG\Property(
 *             property="LevelCoef",
 *             type="string",
 *             description="Coefficient multiplied with users bet for earned points calculation",
 *             example="1.000"
 *         ),
 *         @SWG\Property(
 *             property="Block",
 *             type="object",
 *             description="Total amount of funds blocked by active users bonuses",
 *             example="30"
 *         ),
 *         @SWG\Property(
 *             property="LevelUp",
 *             type="boolean",
 *             description="Transition of the user to the following level"
 *         ),
 *     )
 * )
 */

/**
 * @class UserInfoResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Front
 * @uses eGamings\WLC\User
 */
class UserInfoResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/UserInfo",
     *     description="Returns information of the current user",
     *     tags={"user info"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="User info",
     *         @SWG\Schema(
     *             ref="#/definitions/UserInfo"
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
     * Get current user info like balance
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array}
     * @throws {\Exception}
     */
    public function get($request, $query, $params = [])
    {
        if (empty($_SESSION['user'])) {
            throw new \Exception('', 401);
        }

        $login = Front::User('id');
        $userInfo = User::getInfo($login, false);
        $userInfo['blockByLocation'] = (new CountryNonResidence(User::getInstance()))->isBlocked('api/v1/userInfo', '', [], true);

	      if (_cfg('userInfoExpires')) {
            header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + _cfg('userInfoExpires') ));
            header('Cache-Control: private, max-age=' . _cfg('userInfoExpires'));
            header('Pragma: cache');
        }

        User::checkAndFixUserForBadAdditionalFieldsAfterAcceptTOS();

        return $userInfo;
    }

}
