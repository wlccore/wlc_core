<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="user self exclusion",
 *     description="Get, save and clear User's self exclusion on deposits, wins and losts"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="UserSelfExclusion",
 *     description="User's self exclusions on deposits, wins and losts",
 *     type="object",
 *     @SWG\Property(
 *         property="MaxDepositSumDay",
 *         type="number",
 *         description="Maximum deposits sum per day"
 *     ),
 *     @SWG\Property(
 *         property="MaxDepositSumWeek",
 *         type="number",
 *         description="Maximum deposits sum for each calendar week"
 *     ),
 *     @SWG\Property(
 *         property="MaxDepositSumMonth",
 *         type="number",
 *         description="Maximum deposits sum for each calendar month"
 *     ),
 *     @SWG\Property(
 *         property="MaxBetSumDay",
 *         type="number",
 *         description="Maximum bets sum per day"
 *     ),
 *     @SWG\Property(
 *         property="MaxBetSumWeek",
 *         type="number",
 *         description="Maximum bets sum for each calendar week"
 *     ),
 *     @SWG\Property(
 *         property="MaxBetSumMonth",
 *         type="number",
 *         description="Maximum bets sum for each calendar month"
 *     ),
 *     @SWG\Property(
 *         property="MaxLossSumDay",
 *         type="number",
 *         description="Maximum losts sum per day"
 *     ),
 *     @SWG\Property(
 *         property="MaxLossSumWeek",
 *         type="number",
 *         description="Maximum losts sum for each calendar week"
 *     ),
 *     @SWG\Property(
 *         property="MaxLossSumMonth",
 *         type="number",
 *         description="Maximum losts sum for each calendar month"
 *     ),
 * )
 */

/**
 * @SWG\Definition(
 *     definition="UserSelfExclusionHistoryObjectRow",
 *     description="User's self exclusions history on deposits, wins and losts",
 *     type="object",
 *     @SWG\Property(property="name", type="string", description="exclusion name"),
 *     @SWG\Property(property="before", type="number", example="10"),
 *     @SWG\Property(property="after", type="number", example="100")
 * )
 */

/**
 * @SWG\Definition(
 *     definition="UserSelfExclusionHistoryObject",
 *     description="User's self exclusions history on deposits, wins and losts",
 *     type="object",
 *     @SWG\Property(property="id", type="integer", description="id in Updates"),
 *     @SWG\Property(property="date", type="string", example="2017-07-18 14:07:02"),
 *     @SWG\Property(property="currency", type="string", example="EUR"),
 *     @SWG\Property(property="exclusions", type="array", @SWG\Items(ref="#/definitions/UserSelfExclusionHistoryObjectRow"))
 * )
 */

/**
 * Class UserSelfExclusionResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\User
 */
class UserSelfExclusionResource extends AbstractResource
{

    /**
     * @param $request
     * @param $query
     * @param $params
     * @return array
     * @throws ApiException
     */
    public function get($request, $query, $params = []): array
    {
        if (empty($params['action'])) {
            return $this->getCurrentExclusion();
        } elseif ($params['action'] == 'history') {
            return $this->getHistory();
        }

        throw new ApiException('Bad action for userSelfExclusion', 400);
    }

    /**
     * @SWG\Get(
     *     path="/userSelfExclusion",
     *     description="Get User's current self exclusions on deposit, bet and lost",
     *     tags={"user self exclusion"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="User's current self exclusions on deposit, bet and lost",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/UserSelfExclusion"
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
    private function getCurrentExclusion(): array
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }
        $User = User::getInstance();
        $login = $User->userData->id;

        $url = '/WLCAccount/SelfExclusion/?&Login=' . $login;
        $transactionId = $User->getApiTID($url, $login);
        $hash = md5('WLCAccount/SelfExclusion/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));
        $params = [
            'TID' => $transactionId,
            'Hash' => $hash,
            'Method' => 'get',
        ];
        $url .= '&' . http_build_query($params);
        $response = $User->runFundistAPI($url);

        $res = json_decode($response);
        if (json_last_error() === JSON_ERROR_NONE) {
            return [$res];
        } else {
            throw new ApiException(_('Request invalid. Error: ') . $response, 400);
        }
    }

    /**
     * @return array
     * @throws ApiException
     *
     * @SWG\Get(
     *     path="/userSelfExclusion/history",
     *     description="Get User's self exclusions history on deposit, bet and lost",
     *     tags={"user self exclusion"},
     *     @SWG\Response(
     *         response="200",
     *         description="User's self exclusions history on deposit, bet and lost",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/UserSelfExclusionHistoryObject"
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
    private function getHistory(): array
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $user = User::getInstance();
        $login = $user->userData->id;

        $url = '/WLCAccount/SelfExclusionHistory/?&Login=' . $login;
        $transactionId = $user->getApiTID($url, $login);
        $hash = md5('WLCAccount/SelfExclusionHistory/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));
        $params = [
            'TID' => $transactionId,
            'Hash' => $hash,
        ];
        $url .= '&' . http_build_query($params);
        $response = $user->runFundistAPI($url);

        $res = json_decode($response);

        if (json_last_error() === JSON_ERROR_NONE) {
            return [$res];
        }

        throw new ApiException(_('Request invalid. Error: ') . $response, 400);
    }

    /**
     * @SWG\Post(
     *     path="/userSelfExclusion",
     *     description="Save/update User's self exclusions on deposit, bet and lost",
     *     tags={"user self exclusion"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *      @SWG\Parameter(
     *         name="single",
     *         type="boolean",
     *         in="query",
     *         description="Single"
     *     ),
     *     @SWG\Parameter(
     *         name="MaxDepositSumDay",
     *         type="number",
     *         in="query",
     *         description="Maximum deposits sum per day"
     *     ),
     *     @SWG\Parameter(
     *         name="MaxDepositSumWeek",
     *         type="number",
     *         in="query",
     *         description="Maximum deposits sum for each calendar week"
     *     ),
     *     @SWG\Parameter(
     *         name="MaxDepositSumMonth",
     *         type="number",
     *         in="query",
     *         description="Maximum deposits sum for each calendar month"
     *     ),
     *     @SWG\Parameter(
     *         name="MaxBetSumDay",
     *         type="number",
     *         in="query",
     *         description="Maximum bets sum per day"
     *     ),
     *     @SWG\Parameter(
     *         name="MaxBetSumWeek",
     *         type="number",
     *         in="query",
     *         description="Maximum bets sum for each calendar week"
     *     ),
     *     @SWG\Parameter(
     *         name="MaxBetSumMonth",
     *         type="number",
     *         in="query",
     *         description="Maximum bets sum for each calendar month"
     *     ),
     *     @SWG\Parameter(
     *         name="MaxLossSumDay",
     *         type="number",
     *         in="query",
     *         description="Maximum losts sum per day"
     *     ),
     *     @SWG\Parameter(
     *         name="MaxLossSumWeek",
     *         type="number",
     *         in="query",
     *         description="Maximum losts sum for each calendar week"
     *     ),
     *     @SWG\Parameter(
     *         name="MaxLossSumMonth",
     *         type="number",
     *         in="query",
     *         description="Maximum losts sum for each calendar month"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 type="integer"
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
    public function post($request, $query, $params = [])
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }
        $availableLimits = [
            'MaxDepositSumDay',
            'MaxDepositSumWeek',
            'MaxDepositSumMonth',
            'MaxBetSumDay',
            'MaxBetSumWeek',
            'MaxBetSumMonth',
            'MaxLossSumDay',
            'MaxLossSumWeek',
            'MaxLossSumMonth',
        ];
        $limits = [];
        foreach ($availableLimits as $field) {
            if (isset($request[$field]) && is_numeric($request[$field]) && $request[$field] >= 0) {
                $limits[$field] = $request[$field];
            }
        }

        $single = isset($query['single']) && $query['single'] == 1;

        if ($single && count($limits) !== 1) {
            throw new ApiException(_('In single mode only one limit can be specified'), 400);
        }

        $User = User::getInstance();
        $login = $User->userData->id;

        $url = '/WLCAccount/SelfExclusion/?&Login=' . $login;
        $transactionId = $User->getApiTID($url, $login);
        $hash = md5('WLCAccount/SelfExclusion/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));
        $params = [
            'TID' => $transactionId,
            'Hash' => $hash,
            'Method' => __FUNCTION__,
            'Limits' => $limits,
            'Single' => $single,
        ];
        $url .= '&' . http_build_query($params);
        $response = $User->runFundistAPI($url);

        $result = explode(',', $response, $single ? 2 : 3);

        if ($result[0] == 1) {
            if ($single) {
               return json_decode($result[1]);
            }

            return [$response];
        }

        $response = _($result[1]);

        if (count($result) > 2) {
            $response = sprintf($response, $result[2]);
        }
        throw new ApiException(trim($response), 400);

    }

    /**
     * @SWG\Delete(
     *     path="/userSelfExclusion",
     *     description="Clear all User's current self exclusions on deposit, bet and lost",
     *     tags={"user self exclusion"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 type="integer"
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
    public function delete($request, $query, $params = [])
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }
        $User = User::getInstance();
        $login = $User->userData->id;

        $url = '/WLCAccount/SelfExclusion/?&Login=' . $login;
        $transactionId = $User->getApiTID($url, $login);
        $hash = md5('WLCAccount/SelfExclusion/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));
        $params = [
            'TID' => $transactionId,
            'Hash' => $hash,
            'Method' => __FUNCTION__,
        ];
        $url .= '&' . http_build_query($params);
        $response = $User->runFundistAPI($url);

        if (explode(',', $response)[0] == 1) {
            return [$response];
        } else {
            throw new ApiException(_('Request invalid. Error: ') . $response, 400);
        }
    }
}
