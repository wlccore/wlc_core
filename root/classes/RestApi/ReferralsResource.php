<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\System;
use eGamings\WLC\User;
use eGamings\WLC\Front;

/**
 * @SWG\Tag(
 *     name="referrals",
 *     description="Referrals resource"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="ReferralRecord",
 *     description="Referral information record",
 *     type="object",
 *     @SWG\Property(
 *         property="ID",
 *         type="string",
 *         description="User ID"
 *     ),
 *     @SWG\Property(
 *         property="Email",
 *         type="string",
 *         description="User obfuscated email"
 *     ),
 *     @SWG\Property(
 *         property="ProfitRS",
 *         type="number",
 *         example="1.00",
 *         description="Profit revenue share"
 *     ),
 *     @SWG\Property(
 *         property="ProfitCPA",
 *         type="number",
 *         example="2.00",
 *         description="Profit cost per action"
 *     ),
 *     @SWG\Property(
 *         property="TotalProfitRS",
 *         type="number",
 *         example="3.00",
 *         description="Total revenue share profit"
 *     ),
 *     @SWG\Property(
 *         property="TotalProfitCPA",
 *         type="number",
 *         example="4.00",
 *         description="Total cost per action profit"
 *     )
 * )
 */

/**
 * @class ReferralsResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\System
 */
class ReferralsResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/referrals",
     *     description="Referrals information for auth user",
     *     tags={"referrals"},
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Property(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/ReferralRecord"
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
     * Returns list of registered refferals
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {string}
     * @throws {\Exception}
     */
    function get($request, $query, $params = [])
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User not authorized'), 401);
        }

        $system = System::getInstance();

        $fundistData = User::getInfo(Front::User('id'));
        if (empty($fundistData['idUser'])) {
            throw new ApiException(_('Unable find user identifier'), 401);
        }

        $url = '/Referrals/List/?';
        $transactionId = $system->getApiTID($url);
        $hash = md5('Referrals/List/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
        $params = [ 'TID' => $transactionId, 'Hash' => $hash, 'referrerID' => $fundistData['idUser']];

        $url .= '&' . http_build_query($params);

        $response = $system->runFundistAPI($url);
        $result = explode(',', $response, 2);
        if ($result[0] !== '1') {
            throw new ApiException(!empty($result[1]) ? $result[1] : _('Unknown error occured'), 400);
        }
        return json_decode($result[1], true);
    }
}
