<?php

declare(strict_types=1);

namespace eGamings\WLC\RestApi;

use eGamings\WLC\System;

/**
 * @SWG\Tag(
 *     name="LastNGameActions",
 *     description="Last N game actions"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="LastNGameActionsObject",
 *     description="List of last N game actions",
 *     type="array",
 *     example={"GameID":"4131891","GameName":"Main package","Currency":"RUB","BetAmountOrig":0,"BetAmountEUR":0,"WinAmountOrig":814.27,"WinAmountEUR":9.12,"Multiplier":1.2,"MaskedUserName":"k************m"},
 *     @SWG\Items(
 *         type="object"
 *     )
 * )
 */
class LastNGameActionsResource extends AbstractResource
{
    /**
     * @throws ApiException
     */
    public function __construct()
    {
        if (!_cfg('EnableLastNGameActions')) {
            throw new ApiException(_('This feature is disabled'), 400);
        }
    }

    /**
     * @SWG\Get(
     *     path="/LastNGameActions",
     *     description="Returns list of last N game actions",
     *     tags={"LastNGameActions"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="count",
     *         in="query",
     *         description="Items count",
     *         type="string",
     *         default="10"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns list of last N game actions",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/LastNGameActionsObject"
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
    public function get($request, $query, $params = [])
    {
        $count = $query['count'] ?? 10;
        $system = System::getInstance();
        $url = '/WLCInfo/LastNGameActions/Get';
        $transactionId = $system->getApiTID($url);
        $hash = md5('WLCInfo/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
        $data = [
            'TID' => $transactionId,
            'Hash' => $hash,
            'Count' => $count,
        ];
        $url .= '?&' . http_build_query($data);

        $response = $system->runFundistAPI($url);
        $result = explode(',', $response, 2);
        if ((int)$result[0] === 1) {
            return json_decode($result[1], true, 512, JSON_THROW_ON_ERROR);
        }

        throw new ApiException($result[1], 400);
    }
}
