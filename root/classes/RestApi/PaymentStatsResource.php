<?php

namespace eGamings\WLC\RestApi;

use Egamings\UserDataMasking\UserDataMasking;
use eGamings\WLC\Config;
use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="payment stats",
 *     description="Withdrawal and deposit stats"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="PaymentStatsRank",
 *     description="Withdrawal rank stats",
 *     type="object",
 *     @SWG\Property(
 *         property="Email",
 *         type="string",
 *         description="User obfuscated email"
 *     ),
 *     @SWG\Property(
 *         property="AmountConverted",
 *         type="string",
 *         description="User withdrawal amount in EUR"
 *     ),
 *     @SWG\Property(
 *         property="Amount",
 *         type="string",
 *         description="User withdrawal amount"
 *     ),
 *     @SWG\Property(
 *         property="Currency",
 *         type="string",
 *         description="User currency"
 *     )
 * )
 */

/**
 * @SWG\Definition(
 *     definition="PaymentStats",
 *     description="Withdrawal and deposit stats",
 *     type="object",
 *     @SWG\Property(
 *         property="Email",
 *         type="string",
 *         description="User obfuscated email"
 *     ),
 *     @SWG\Property(
 *         property="AmountConverted",
 *         type="string",
 *         description="User withdrawal amount in EUR"
 *     ),
 *     @SWG\Property(
 *         property="Amount",
 *         type="string",
 *         description="User withdrawal amount"
 *     ),
 *     @SWG\Property(
 *         property="TimeStamp",
 *         type="integer",
 *         description="TimeStamp of transaction"
 *     ),
 *     @SWG\Property(
 *         property="Currency",
 *         type="string",
 *         description="user currency"
 *     )
 * )
 */

/**
 * @class PaymentStatsResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\User
 */

class PaymentStatsResource extends AbstractResource
{

    /**
     * @SWG\Get(
     *     path="/stats/withdrawal/rank",
     *     description="Returns top withdrawals, group by user",
     *     tags={"payment stats"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="Days",
     *         type="integer",
     *         in="query",
     *         description="Number of days for which to display statistics, by default 7"
     *     ),
     *     @SWG\Parameter(
     *         name="Rows",
     *         type="integer",
     *         in="query",
     *         description="Number of output records, by default 5"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns withdrawal stats",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/PaymentStatsRank"
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
     * @SWG\Get(
     *     path="/stats/withdrawal/status",
     *     description="Returns top withdrawals",
     *     tags={"payment stats"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="Rows",
     *         type="integer",
     *         in="query",
     *         description="Number of output records, by default 5"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns withdrawal stats",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/PaymentStats"
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
     * @SWG\Get(
     *     path="/stats/deposit/status",
     *     description="Returns top deposits",
     *     tags={"payment stats"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="Rows",
     *         type="integer",
     *         in="query",
     *         description="Number of output records, by default 5"
     *     ),
     *     @SWG\Parameter(
     *         name="Manual",
     *         type="integer",
     *         in="query",
     *         description="To include a manual deposits Manual=1"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns deposit stats",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/PaymentStats"
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
     * Returns withdrawal and weposit stats
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array}
     */
    public function get($request, $query, $params = [])
    {
        $action = !empty($params['action']) ? $params['action'] : (!empty($query['action']) ? $query['action'] : '');
        $type = !empty($params['type']) ? $params['type'] : (!empty($query['type']) ? $query['type'] : '');
        $url = "Stats/";

        switch ($action) {
            case 'withdrawal':
                if (in_array($type, ['rank','status'])) {
                    $url .= ucfirst($action) . ucfirst($type);
                } else {
                    throw new ApiException('Wrong stats type', 400);
                }

                break;
            case 'deposit':
                if ($type == 'status') {
                    $url .= ucfirst($action) . ucfirst($type);
                } else {
                    throw new ApiException('Wrong stats type', 400);
                }

                break;
            default:
                throw new ApiException('Wrong stats action', 400);

                break;
        }

        $User = new User();
        $transactionId = $User->getApiTID($url);
        $hash = md5($url .'/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));

        $params =
            [
                'TID' => $transactionId,
                'Hash' => $hash
            ];

        foreach (['Rows', 'Days', 'Manual'] as $param) {
            if (!empty($query[$param])) {
                $params[$param] = $query[$param];
            }
        }

        $url ='/' . $url .  '?&' . http_build_query($params);

        $response = $User->runFundistAPI($url);
        $answ = json_decode($response);

        if (is_array($answ)) {
            $siteConfig = Config::getSiteConfig();

            foreach ($answ as &$row) {
                $row->ScreenName = (new UserDataMasking(
                    $siteConfig['MaskTypeForNameAndLastName'] ?? 'none',
                    $row->Name ?? '',
                    $row->LastName ?? '',
                    $row->Email ?? ''
                ))->getScreenName();
            }
        }

        return $answ ? $answ : $response;
    }

}