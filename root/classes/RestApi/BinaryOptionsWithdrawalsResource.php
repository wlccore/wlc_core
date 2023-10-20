<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\User;

/**
 * @class BinaryOptionsWithdrawalsResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\User
 */
class BinaryOptionsWithdrawalsResource extends AbstractResource
{
    /**
     * Make withdraw of merchant
     *
     * @public
     * @method post
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {boolean}
     * @throws {ApiException}
     */
    public function post($request, $query, $params = [])
    {
        $user = new User();
        $systemId = _cfg('spotoptionSystemId');

        $amount = $request['amount'];

        if ($amount <= 0) {
            throw new ApiException('Amount should be positive', 400);
        }

        return $user->merchantWithdraw($amount, $request['currency'], $systemId);
    }
}
