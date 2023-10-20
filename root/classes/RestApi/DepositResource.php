<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Front;
use eGamings\WLC\User;
use eGamings\WLC\System;

/**
 * @SWG\Tag(
 *     name="deposit",
 *     description="Deposit"
 * )
 */

/**
 * @class DepositResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Front
 */
class DepositResource extends AbstractResource
{
    /**
     * @SWG\Post(
     *     path="/deposits",
     *     description="Initializes payment transaction",
     *     tags={"deposit"},
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
     *                 description="Payment system id",
     *                 example="123"
     *             ),
     *             @SWG\Property(
     *                 property="amount",
     *                 type="number",
     *                 description="Amount",
     *                 example=300.50
     *             ),
     *             @SWG\Property(
     *                 property="additional",
     *                 type="object",
     *                 description="Additional params",
     *                 example={"bonusId": "123", "bonusCode": "ASDER"}
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
     *         description="Returns the payment options"
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
     * Initializes payment transaction
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
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User not authorized'), 401);
        }

        if (_cfg('requiredFieldsList')) {
            $userCheck = new \eGamings\WLC\User();
            $userCheck->checkRequiredFields(_cfg('requiredFieldsList'));
        }

        $user = Front::User();
        $hookResult = System::hook('api:deposit', $request);
        if (!empty($hookResult)) {
            return $hookResult;
        }

        $compatibility_request = $request;
        $compatibility_request['system'] = $request['systemId'];
        $compatibility_request['additional']['origUrl'] = $_SERVER['HTTP_HOST'];
        $compatibility_request['version'] = $query['version'] ?? '';

        $compatibility_request['wallet'] = !empty($request['wallet']) ? (int)$request['wallet'] : '';
        $compatibility_request['walletCurrency'] = !empty($request['walletCurrency']) ? $request['walletCurrency'] : '';

        $compatibility_request['BonusID'] = !empty($request['bonusId']) ? $request['bonusId'] : (!empty($request['additional']['bonusId']) ? $request['additional']['bonusId'] : '');

        if (in_array($compatibility_request['BonusID'], ['code', 'voucher'])) {
            unset($compatibility_request['BonusID']);
            $compatibility_request['BonusCode'] = !empty($request['bonusCode']) ? $request['bonusCode'] : (!empty($request['additional']['bonusCode']) ? $request['additional']['bonusCode'] : '');
        }

        $response = Front::User('credit', [$compatibility_request]);
        $result = explode(',', $response, 2);

        if ($result[0] != 1) {
            $errorData = explode(',', $result[1]);
            if ($result[1][0] === '[') {
                $errorData = json_decode($result[1], true);

                if ($result[0] == 50) {
                    $errorData = $this->translateIncompleteProfileError($errorData[0]);
                }
                throw new ApiException('', 400, null, $errorData);
            }

            $errorString = _('Deposit failed: %s');
            $errorParams = [$errorData[0]];
            $errorCode = (!empty($result[0])) ? $result[0]: 'unknown';

            switch($errorCode) {
                case "24":
                    $errorString = _('You have enabled %s limit: %s %s until %s. Available deposit amount: %s %s');
                    if (in_array($errorData[3], ['Day', 'Week', 'Month'])) {
                        $errorData[3] = _($errorData[3]);
                    }
                    $errorParams = [$errorData[0], $errorData[1], $user->currency, $errorData[3], $errorData[2], $user->currency];
                    break;
            }

            throw new ApiException(call_user_func_array('sprintf', array_merge([$errorString], $errorParams)), 400);
        }

        if (strpos($result[1], '["markup"') === 0) {
            $result[1] = str_replace(
                [
                    'To complete the deposit, make a bank transfer using the following details',
                    'Amount',
                    'Branch code',
                    'Bank name',
                    'Branch name',
                    'Account type',
                    'Account number',
                    'Account name',
                    'Reference number'
                ],
                [
                    _('To complete the deposit, make a bank transfer using the following details'),
                    _('Amount'),
                    _('Branch code'),
                    _('Bank name'),
                    _('Branch name'),
                    _('Account type'),
                    _('Account number'),
                    _('Account name'),
                    _('Reference number')
                ],
                $result[1]
            );
        }

        return $result[1] == 1 ? null : json_decode($result[1], true);
    }

    /**
     * @SWG\Delete(
     *     path="/deposits",
     *     description="Cancel cryptoinvoice",
     *     tags={"deposit"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="systemId",
     *         type="integer",
     *         in="query",
     *         required=true,
     *         description="Payment system id",
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
     * Cancels cryptoinvoice.
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
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User not authorized'), 401);
        }

        $result = explode(',',
                          Front::User('cancelInvoice', [['system_id' => $request['systemId']]]),
                          2);

        if ($result[0] != 1) {
            throw new ApiException($result[1], 400);
        }

        return null;
    }
}
