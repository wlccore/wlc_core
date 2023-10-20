<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="wallet",
 *     description="Multicurrency wallet"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="Wallet",
 *     description="Wallet data",
 *     type="object",
 *     @SWG\Property(
 *         property="ID",
 *         type="integer",
 *         description="Wallet ID",
 *         example="11223344"
 *     ),
 *     @SWG\Property(
 *         property="Currency",
 *         type="string",
 *         description="Wallet currency",
 *         example="USD"
 *     ),
 *     @SWG\Property(
 *         property="Balance",
 *         type="number",
 *         description="Wallet balance",
 *         example="1500.68"
 *     ),
 *     @SWG\Property(
 *         property="Login",
 *         type="string",
 *         description="Wallet login",
 *         example="USD_482"
 *     ),
 *     @SWG\Property(
 *         property="Status",
 *         type="integer",
 *         description="Wallet status",
 *         example="1"
 *     )
 * )
 */

/**
 * @class WalletsResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 */
class WalletsResource extends AbstractResource
{
    /**
     * @SWG\Post(
     *     path="/wallets",
     *     description="Create a wallet with a specific currency",
     *     tags={"wallet"},
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="currency",
     *                 type="string",
     *                 description="Wallet currency",
     *                 example="CAD"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Schema(
     *              @SWG\Property(
     *                  property="result",
     *                  type="boolean",
     *                  example="true"
     *              ),
     *              @SWG\Property(
     *                  property="walletId",
     *                  type="integer",
     *                  example="11223344"
     *              )
     *          ),
     *    ),
     *     @SWG\Response(
     *         response="401",
     *         description="User is not authorized",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
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
    public function post(?array $request, array $query, array $params = [])
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        if (empty($request['currency'])) {
            throw new ApiException(_('Currency is required'), 400);
        }

        $url = '/Wallet/Add';
        $User = User::getInstance();
        $fundistUserId = $User->fundist_uid($User->userData->id);
        $transactionId = $User->getApiTID($url);
        $hash = md5('Wallet/Add/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $fundistUserId . '/' . $request['currency'] . '/' . _cfg('fundistApiPass'));
        $data = [
            'IDUser' => $fundistUserId,
            'Currency' => $request['currency'],
            'TID' => $transactionId,
            'Hash' => $hash,
        ];
        $url .= '?&' . http_build_query($data);

        $response = explode(',', $User->runFundistAPI($url));

        if ($response[0] !== '1') {
            throw new ApiException(_($response[1]), 400);
        }
        return [
            'result' => true,
            'walletId' => $response[1],
        ];

    }

    /**
     * @SWG\Get(
     *     path="/wallets",
     *     description="Returns a list of the user's wallets",
     *     tags={"wallet"},
     *     @SWG\Response(
     *         response="200",
     *         description="Wallets list",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/Wallet"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="User is not authorized",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
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
    public function get(?array $request, array $query, array $params = [])
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $url = '/Wallet/List';
        $User = User::getInstance();
        $fundistUserId = $User->fundist_uid($User->userData->id);
        $transactionId = $User->getApiTID($url);
        $hash = md5('Wallet/List/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $fundistUserId . '/' . _cfg('fundistApiPass'));
        $data = [
            'IDUser' => $fundistUserId,
            'TID' => $transactionId,
            'Hash' => $hash,
        ];
        $url .= '?&' . http_build_query($data);

        $response = explode(',', $User->runFundistAPI($url), 2);
        if ($response[0] !== '1') {
            throw new ApiException(_($response[1]), 400);
        }

        return [
            'result' => true,
            'wallets' => json_decode($response[1], true),
        ];

    }
}
