<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\System;
use eGamings\WLC\User;
use Exception;

/**
     * @SWG\GET(
     *     path="/withdrawalRequests",
     *     description="Withdrawal requests",
     *     tags={"withdrawalRequests"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang",
     *     ),
     *     @SWG\Parameter(
     *         name="DateFrom",
     *         in="path",
     *         type="string",
     *         description="Date from. Format d.m.Y, example: '01.06.2022'",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         name="DateTo",
     *         in="path",
     *         type="string",
     *         description="Date to. Format d.m.Y, example: '29.08.2022'",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         name="Limit",
     *         in="path",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="Status",
     *         in="path",
     *         type="string",
     *         example="Declined,Completed,New",
     *         description="Return withdrawal requests only with given statuses, comma separated (
                  New -> 0,1
                  Checked -> 50
                  UserActionRequired -> 90
                  Pending -> 95
                  Completed -> 100,105,110,115
                  AutoWithdrawal -> 101
                  Regenerated -> -30
                  Declined -> -50,5
                  Splitted -> -55
                  Failed -> -60
               )"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Withdrawal requests data",
     *         @SWG\Property(
     *             type="array",
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
 * @class WithdrawalRequestsResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Cache
 * @uses eGamings\WLC\Social
 * @uses eGamings\WLC\Games
 * @uses eGamings\WLC\Front
 * @uses eGamings\WLC\Config
 * @uses eGamings\WLC\Cache
 */
class WithdrawalRequestsResource extends AbstractResource {

    private const AVAILABLE_STATUSES = [
        'New' => [0, 1],
        'Checked' => [50],
        'UserActionRequired' => [90],
        'Pending' => [95],
        'Completed' => [100, 105, 110, 115],
        'AutoWithdrawal' => [101],
        'Regenerated' => [-30],
        'Declined' => [-50, 5],
        'Splitted' => [-55],
        'Failed' => [-60],
    ];

    /**
     * Bootstrap
     *
     * @public
     * @method get
     * @param {array} $request
     * @param array $query
     * @param array $params
     * @return string {string}
     * @throws ApiException
     * @throws Exception
     */
    public function get($request, $query = [], $params = []): string
    {
        if(!_cfg('withdrawalRequestsEnable')) {
            throw new ApiException(_('No access to view withdrawal requests'), 405);
        }

        $url = '/WLCAccount/WithdrawalRequests/?';
        $system = System::getInstance();
        $transactionId = $system->getApiTID($url);

        $hash = md5('WLCAccount/WithdrawalRequests/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '//' . _cfg('fundistApiPass'));
        $params = [
            'Language' => _cfg('language'),
            'TID' => $transactionId,
            'Hash' => $hash,
            'DateFrom' => $query['DateFrom'] ?? '',
            'DateTo' => $query['DateTo'] ?? '',
            'Limit' => $query['Limit'] ?? '',
        ];

        $Status = [];
        if (!empty($query['Status'])) {
            $statuses = explode(',', $query['Status']);
            foreach ($statuses as $status) {
                if (isset(self::AVAILABLE_STATUSES[$status])) {
                    $Status = array_merge($Status, self::AVAILABLE_STATUSES[$status]);
                }
            }
        }
        if (!empty($Status)) {
            $params['Status'] = array_unique($Status);
        }

        $url .= '&' . http_build_query($params);

        return $system->runFundistAPI($url);
    }
}
