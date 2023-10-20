<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="session check",
 *     description="Get, save and clear User session limit"
 * )
 */

/**
 * Class SessionCheckResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\User
 */
class SessionCheckResource extends AbstractResource
{

    /**
     * @SWG\Get(
     *     path="/sessionCheck",
     *     description="Get User session limit",
     *     tags={"session check"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 type="string",
     *                 example="1,00:10,00:09"
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
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }
        $User = User::getInstance();
        $login = $User->userData->id;

        $url = '/WLCAccount/SessionCheck/?&Login=' . $login;
        $transactionId = $User->getApiTID($url, $login);
        $hash = md5('WLCAccount/SessionCheck/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));
        $params = [
            'TID' => $transactionId,
            'Hash' => $hash,
            'Method' => __FUNCTION__,
        ];
        $url .= '&' . http_build_query($params);

        $response = $User->runFundistAPI($url);

        if (explode(',', $response)[0] == 1) {
            return $response;
        } else {
            throw new ApiException($response, 400);
        }
    }

    /**
     * @SWG\Put(
     *     path="/sessionCheck",
     *     description="Check User session limit",
     *     tags={"session check"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 type="string",
     *                 example="1"
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
    public function put($request, $query, $params = [])
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }
        $User = User::getInstance();
        $login = $User->userData->id;

        $url = '/WLCAccount/SessionCheck/?&Login=' . $login;
        $transactionId = $User->getApiTID($url, $login);
        $hash = md5('WLCAccount/SessionCheck/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));
        $params = [
            'TID' => $transactionId,
            'Hash' => $hash,
            'Method' => __FUNCTION__,
        ];
        $url .= '&' . http_build_query($params);
        $response = $User->runFundistAPI($url);

        if (in_array(explode(',', $response)[0], ['-1', '1'])) {
            return $response;
        } else {
            throw new ApiException($response, 400);
        }
    }

    /**
     * @SWG\Post(
     *     path="/sessionCheck",
     *     description="Save/update User session linit",
     *     tags={"session check"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="Limit",
     *         type="string",
     *         in="query",
     *         description="Session limit in format HH:M0 (e.g., 5:40)"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 type="string",
     *                 example="1,Limit will be applied tomorrow"
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

        if (empty($request['Limit'])) {
            throw new ApiException(_('Limit not provided '), 400);
        }

        [$hours, $min] = explode(':', $request['Limit']);
        if (!is_numeric($hours) || !is_numeric($min)
            || $hours < 0 || $hours > 23
            || $min < 0 || $min > 50 || $min % 10 != 0
            || ($hours * 60 + $min) > 1380
        ) {
            throw new ApiException(_('Invalid Limit value '), 400);
        }

        $User = User::getInstance();
        $login = $User->userData->id;

        $url = '/WLCAccount/SessionCheck/?&Login=' . $login;
        $transactionId = $User->getApiTID($url, $login);
        $hash = md5('WLCAccount/SessionCheck/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));
        $params = [
            'TID' => $transactionId,
            'Hash' => $hash,
            'Method' => __FUNCTION__,
            'Limit' => $request['Limit'],
        ];
        $url .= '&' . http_build_query($params);
        $response = $User->runFundistAPI($url);

        if (explode(',', $response)[0] == 1) {
            return $response;
        } else {
            throw new ApiException($response, 400);
        }
    }

    /**
     * @SWG\Delete(
     *     path="/sessionCheck",
     *     description="Clear User session linit",
     *     tags={"session check"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 type="string",
     *                 example="1,Limit will be removed tomorrow"
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

        $url = '/WLCAccount/SessionCheck/?&Login=' . $login;
        $transactionId = $User->getApiTID($url, $login);
        $hash = md5('WLCAccount/SessionCheck/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));
        $params = [
            'TID' => $transactionId,
            'Hash' => $hash,
            'Method' => __FUNCTION__,
        ];
        $url .= '&' . http_build_query($params);
        $response = $User->runFundistAPI($url);

        if (explode(',', $response)[0] == 1) {
            return $response;
        } else {
            throw new ApiException($response, 400);
        }
    }
}
