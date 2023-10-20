<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="reality check",
 *     description="Get sum of Deposits, Wins and Losses of player for the specified period or for 24 hours"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="RealityCheck",
 *     description="Sum of Deposits, Wins and Losses of player",
 *     type="object",
 *     @SWG\Property(
 *         property="Deposits",
 *         type="number",
 *         description="Sum of deposits"
 *     ),
 *     @SWG\Property(
 *         property="Wins",
 *         type="number",
 *         description="Sum of wins"
 *     ),
 *     @SWG\Property(
 *         property="Losses",
 *         type="number",
 *         description="Sum of losses"
 *     ),
 *     @SWG\Property(
 *         property="FromTime",
 *         type="string",
 *         description="Start dateTime of the Reality-check statistics period in 'Y-m-d H:i:s' format"
 *     )
 * )
 */

/**
 * Class RealityCheckResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\User
 */

class RealityCheckResource extends AbstractResource
{

    /**
     * @SWG\Get(
     *     path="/realityCheck",
     *     description="Get sum of Deposits, Wins and Losses of playe for the specified period or for 24 hours",
     *     tags={"reality check"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="from",
     *         type="string",
     *         in="query",
     *         description="Start dateTime of the Reality-check statistics period in 'Y-m-d H:i:s' format"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns withdrawal stats",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/RealityCheck"
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
    public function get($request, $query)
    {
        $from = !empty($request['from']) ? $request['from'] : (!empty($query['from']) ? $query['from'] : '');
        if (!empty($from) && \DateTime::createFromFormat('Y-m-d H:i:s', $from) === false) {
            throw new ApiException(_('Parameter from must be in format \'Y-m-d H:i:s\' or empty'), 400);
        }

        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }
        $User = User::getInstance();
        $login = $User->userData->id;

        $url = '/WLCAccount/RealityCheck/?&Login=' . $login;
        $transactionId = $User->getApiTID($url, $login);
        $hash = md5('WLCAccount/RealityCheck/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));
        $params = [
            'TID'  => $transactionId,
            'Hash' => $hash,
            'From' => $from,
        ];
        $url .= '&' . http_build_query($params);

        $response = $User->runFundistAPI($url);
        $res = json_decode($response, JSON_OBJECT_AS_ARRAY);
        if (json_last_error() === JSON_ERROR_NONE) {
            foreach (['Deposits', 'Wins', 'Losses'] as $field) {
                if (!empty($res[$field])) {
                    $res[$field] = floatval(number_format($res[$field], 2, '.', ''));
                }
            }
            return [$res];
        } else {
            throw new ApiException(_('Request invalid. Error: ') . $response, 400);
        }
    }
}
