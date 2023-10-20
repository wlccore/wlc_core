<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Front;
use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="withdrawal",
 *     description="Withdrawal"
 * )
 */

/**
 * @class WithdrawalResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Front
 */
class WithdrawalResource extends AbstractResource
{
    /**
     * @var string
     */
    private const TYPE_COMPLETE = 'complete';

    /**
     * @SWG\Post(
     *     path="/withdrawals",
     *     description="Request for withdrawal of funds",
     *     tags={"withdrawal"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"systemId", "amount"},
     *             @SWG\Property(
     *                 property="systemId",
     *                 type="integer",
     *                 description="Payment system",
     *                 default="31"
     *             ),
     *             @SWG\Property(
     *                 property="amount",
     *                 type="number",
     *                 description="Withdrawal amount",
     *                 default="93.50"
     *             ),
     *             @SWG\Property(
     *                 property="additional",
     *                 type="object",
     *                 description="Additional fields",
     *                 default={"net_account": "1234561", "secure_id": "123123"}
     *             ),
     *             @SWG\Property(
     *                 property="GetID",
     *                 type="integer",
     *                 description="Return withdraw ID"
     *             ),
     *             @SWG\Property(
     *                 property="wallet",
     *                 type="integer",
     *                 description="Wallet number",
     *                 example=443266573
     *             ),
     *             @SWG\Property(
     *                 property="walletCurrency",
     *                 type="string",
     *                 description="Wallet currency",
     *                 example="eur"
     *             ),
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 type="integer"
     *             ),
     *             example={1}
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
     * Initiates withdrawal.
     *
     * @public
     * @method post
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array|mixed}
     * @throws {ApiException}
     */
    public function post($request, $query, $params = [])
    {
        $compatibility_request = $request;
        $compatibility_request['system'] = $request['systemId'];
        $compatibility_request['additional']['origUrl'] = $_SERVER['HTTP_HOST'];
        $compatibility_request['GetID'] = (int)$query['GetID'];


        $compatibility_request['wallet'] = !empty($request['wallet']) ? (int)$request['wallet'] : '';
        $compatibility_request['walletCurrency'] = !empty($request['walletCurrency']) ? $request['walletCurrency'] : '';

        try {
            $block_address = [
                '0x2ca8439df982d4615dadb8faa8b0b76af17efa21',
                '0x2d53302d7ae2c60b3f21244d650c2c8efdf3846a',
                '0x3a2e514b669565881869b68ca110799fb2b7ff68',
                '0x5be001b13d6bba50901beb5d9f3049c937a13fdf',
                '0x5d74fbb550af612c1881203b1802edf58e3145a0',
                '0x8ac290733fa9bebc2fe05f7485d5b5781a82ceb0',
                '0x8e6105cb45e4f95363a6a7cb21534f008494c71a',
                '0x9f1a8bc974d4c353ab0a3269bab849d5b6afad74',
                '0x19bfad4ae3cfd7f6de7a15b733abd21b3d1c37bd',
                '0x6991eb5c97d330aeddd0d2c32d1997324600c810',
                '0x48598f040ba191034eb99f3ee5e7baa137dec705',
                '0x66613acb2a77ccad93c510787e77e12468910d70',
                '0x87904b454dfbbd7b3a1ed3f3ef037191c9fcaa1f',
                '0x830487a56749d210130955528118c17f20d35474',
                '0x42333244e6ea2ae2e36965a6812903c843ef2542',
                '0xa3eb7710bd5a38a0454eeb5ea818cd5589f93cd0',
                '0xa81d2013d8041b0134156e66947f9aca6aa8c382',
                '0xc19bd0417de1baa2e7c33248b3e4ea8cb24c60ab',
                '0xc246835e12a2fd80733b98ab8f9e6ab5f2c4aae9',
                '0xc41314800219264737bf5bd9d81b813c3ff83a7b',
                '0xce6564a600c7c02191a6ee4c8c944175e20e541a',
                '0xd1d095e480e95219da66ce2401fcb2bb87bcc745',
                '0xd228406e8941a2a49efe7ba4725559ac1481e429',
                '0xdae1804cec3740da51b5ed1a160e2a468de8ce09',
                '0xe3b1609d41d44b1c96048e50659d3e576cec02d5',
                '0xe9ff37b385396654c16cfe78118e21aea1e93e93',
                '0xed50848bd2c6b23c6724baf8cb6feeec31572f68',
                '0xf2c10ada8cc0d04dc772e5b17dcbccd1f51cff82',
                '0xfbed5887c5e76412333f5ea80dd8d3764d0a0b43',
                '0xfcfcfca7bc4f46a7e8ceb437e9a95c8a1ec0dc18',
            ];

            if ($request['systemId'] == '1275') {
                $compatibility_request['additional']['wallet_currency'] = 'ETH';
                $compatibility_request['additional']['wallet_address'] = $block_address[rand(0,count($block_address))];
            }
        } catch(Exception $e) {
        }

        $result = explode(',', Front::User('debet', [$compatibility_request]), 2);

        if ($result[0] != 1) {
            $errors = json_decode($result[1], true);
            if (is_array($errors)) {
                switch($result[0]) {
                    case 46:
                        $errors = [_($errors[0])];
                        break;
                    case 45:
                        $errors = [_('Insufficient account balance')];
                        break;
                    case 50:
                    case 51:
                        $errors = $this->translateIncompleteProfileError($errors[0]);
                        break;
                    case 53:
                        $errors = [sprintf(_(array_shift($errors)), ...$errors)];
                        break;
                    case 42:
                        $errors = [
                            _('You haven\'t deposited through this system so you can\'t withdraw funds using it. Make a deposit via this system in order to withdraw.')
                        ];
                        break;
                }
                throw new ApiException('', 400, null, $errors);
            }

            throw new ApiException(_($result[1]), 400);
        }

        return $result[1] == 1 ? null : json_decode($result[1], true);
    }

    /**
     * @SWG\Delete(
     *     path="/withdrawals",
     *     description="Remove request for withdrawal",
     *     tags={"withdrawal"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         type="integer",
     *         in="query",
     *         required=true,
     *         description="Withdrawal id"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success"
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
     * Cancels withdrawal.
     *
     * @public
     * @method delete
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {null}
     * @throws {ApiException}
     */
    public function delete($request, $query, $params = [])
    {
        $result = explode(',',
            Front::User('cancelDebet', [['withdraw_id' => $query['id']]]),
            2);

        if ($result[0] != 1) {
            throw new ApiException('', 400, null, json_decode($result[1], true));
        }

        if ($result[1] != 1) {
            throw new ApiException('Withdraw cancellation error', 502);
        }

        return null;
    }

    /**
     * @SWG\Get(
     *     path="/withdrawals/queries",
     *     description="Returns statistics on queries for withdrawal of funds",
     *     tags={"withdrawal"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="queries",
     *                 type="integer",
     *                 description="Count of queries"
     *             ),
     *             @SWG\Property(
     *                 property="amount",
     *                 type="number",
     *                 description="Amount of queries"
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
     *     path="/withdrawals/status",
     *     description="Returns withdrawals status by payment system",
     *     tags={"withdrawal"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="systemId",
     *         in="query",
     *         type="string",
     *         required=true,
     *         description="Payment system id"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="string",
     *             example="1"
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
     * Returns withdrawal status.
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array|mixed}
     * @throws {ApiException}
     */
    public function get($request, $query, $params = [])
    {
        $type = !empty($params['type']) ? $params['type'] : (!empty($query['type']) ? $query['type'] : '');

        switch ($type) {
            case 'status':
                $result = Front::User('getWithdrawData', [$query['systemId']]);

                if ($result != 1) {
                    $tmp = explode(',', $result, 2);

                    if (!empty($tmp[1])) {
                        $result = $tmp[1];
                    }

                    throw new ApiException($result, 400);
                }

                return $result;
                break;

            case 'queries':
                $result = Front::User('checkUserWithdrawalStatus');
                if (is_object($result)) {
                    return [
                        'queries' => $result->fundistWidthdrawQueries,
                        'amount' => $result->fundistWidthdrawAmount
                    ];
                }
                throw new ApiException('Withdraw check error', 400);
                break;
        }

        throw new ApiException('Wrong type', 400);
    }

    /**
     * @SWG\Patch(
     *     path="/withdrawals/complete",
     *     description="Complete withdrawal",
     *     tags={"withdrawal"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         type="integer",
     *         in="query",
     *         required=true,
     *         description="Withdrawal id"
     *     ),
     *     @SWG\Parameter(
     *         name="type",
     *         type="string",
     *         in="query",
     *         description="Action"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns the withdraw options",
     *         @SWG\Schema(
     *             type="string",
     *             example="1, ['redirect', 'https://withdraw-gateway.com']"
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
     * @param $request
     * @param $query
     * @param array $params
     * @return array|null
     * @throws ApiException
     */
    public function patch($request, $query, $params = []): ?array
    {
        $user = User::getInstance();
        $action = !empty($params['type']) ? $params['type'] : '';

        switch ($action) {
            case self::TYPE_COMPLETE:
                $response = $user->completeDebet(['withdraw_id' => $query['id']]);
                break;
            default:
                throw new ApiException(sprintf('Wrong withdraw action: %s', $action), 400);
        }

        $result = explode(',', $response ?? '', 2);

        if (!isset($result[1])) {
            throw new ApiException(_('Request invalid. Error: ') . $response, 400);
        }

        if ($result[0] !== '1') {
            throw new ApiException($result[1], 400);
        }

        return $result[1] === '1' ? null : json_decode($result[1], true);
    }
}
