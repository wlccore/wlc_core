<?php
namespace eGamings\WLC;

class Balance extends System
{

    /**
     * Balance/Get
     *
     * @public
     * @method get
     * @param {string} $userId
     * @param {int} $systemId
     * @return {array}
     */
    public function get($userId, $systemId)
    {
        $url = "/Balance/Get?";

        $transactionId = $this->getApiTID($url);

        $hash = md5(
            "Balance/Get/0.0.0.0/" .
            $transactionId . '/' .
            _cfg('fundistApiKey') . '/' .
            $systemId . '/' .
            $userId . '/' .
            _cfg('fundistApiPass')
        );
        $params = [
            'TID' => $transactionId,
            'Hash' => $hash,
            'System' => $systemId,
            'Login' => $userId,
        ];

        $url .= '?&' . http_build_query($params);
        $response = $this->runFundistAPI($url);
        $result = explode(',', $response, 2);

        if ($result[0] == 1) {
            $balance = explode(',', $result[1]);
            return [
                'available' => floatval(number_format($balance[0], 2, '.', '')),
                'currency' => $balance[1],
                'loyaltyblock' => intval($balance[2]),
            ];
        }
        else {
            return [
                'code' => $result[0],
                'error' => $result[1],
            ];
        }
    }

    /**
     * WLCAccount/Merchant/Credit
     *
     * @public
     * @method credit
     * @param {string} $userId
     * @param {int} $systemId
     * @param {float} $amount
     * @param {string} $currency
     * @return {array}
     * @throws {ApiException}
     */
    public function credit($userId, $systemId, $amount, $currency)
    {
        $user = new User($userId);

        if ($amount <= 0) {
            throw new ApiException('Amount should be positive', 400);
        }

        return $user->merchantCredit($amount, $currency, $systemId);
    }

    /**
     * WLCAccount/Merchant/Withdraw
     *
     * @public
     * @method withdraw
     * @param {string} $userId
     * @param {int} $systemId
     * @param {float} $amount
     * @param {string} $currency
     * @return {array}
     * @throws {ApiException}
     */
    public function withdraw($userId, $systemId, $amount, $currency)
    {
        $user = new User($userId);

        if ($amount <= 0) {
            throw new ApiException('Amount should be positive', 400);
        }

        return $user->merchantWithdraw($amount, $currency, $systemId);
    }

}
