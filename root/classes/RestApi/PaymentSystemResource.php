<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\Front;
use eGamings\WLC\States;
use eGamings\WLC\System;

/**
 * @SWG\Tag(
 *     name="payment systems",
 *     description="Payment systems"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="PaymentSystem",
 *     description="Payment system",
 *     type="object",
 *     @SWG\Property(
 *         property="id",
 *         type="string",
 *         description="ID of payment sytem"
 *     ),
 *     @SWG\Property(
 *         property="name",
 *         type="string",
 *         description="Name of payment system"
 *     ),
 *     @SWG\Property(
 *         property="description",
 *         type="string",
 *         description="Description of payment system"
 *     ),
 *     @SWG\Property(
 *         property="additional",
 *         type="string",
 *         description="Additional params of payment system (not parsed)"
 *     ),
 *     @SWG\Property(
 *         property="showfor",
 *         type="string",
 *         description="The use of the payment system",
 *         enum={"All", "Deposits", "Withdraws"}
 *     ),
 *     @SWG\Property(
 *         property="allowiframe",
 *         type="string",
 *         description="Allow iframe",
 *         enum={"0", "1"}
 *     ),
 *     @SWG\Property(
 *         property="image",
 *         type="string",
 *         description="Path to image of the payment system"
 *     ),
 *     @SWG\Property(
 *         property="depositMin",
 *         type="integer",
 *         description="Minimum sum of deposit"
 *     ),
 *     @SWG\Property(
 *         property="depositMax",
 *         type="integer",
 *         description="Maximum sum of deposit"
 *     ),
 *     @SWG\Property(
 *         property="withdrawMin",
 *         type="integer",
 *         description="Minimum sum of withdraw"
 *     ),
 *     @SWG\Property(
 *         property="withdrawMax",
 *         type="integer",
 *         description="Maximum sum of withdraw"
 *     ),
 *     @SWG\Property(
 *         property="additionalParams",
 *         type="object",
 *         description="Additional params of payment system (parsed)"
 *     )
 * )
 */

/**
 * @class PaymentSystemResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Front
 */
class PaymentSystemResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/paymentSystems",
     *     description="Returns payment systems with their settings",
     *     tags={"payment systems"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="version",
     *         type="string",
     *         in="query",
     *         description="Payment systems list version",
     *         required=false
     *     ),
     *     @SWG\Parameter(
     *         name="currency",
     *         type="string",
     *         in="query",
     *         description="Currency of wallet for get payment systems list",
     *         required=false
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns payment systems",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/PaymentSystem"
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
     * Returns payment systems with their settings
     * If user is authorized then returns payment systems available for users currency
     * If not then returns all payment systems
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array}
     * @throws ApiException
     */
    public function get($request, $query, $params = [])
    {
        $paySystems = Front::PaySystems();
        foreach($paySystems as &$paySystem) {
            $additionalParams = [];
            @parse_str($paySystem['additional'], $additionalParams);
            $paySystem['additionalParams'] = $additionalParams;
            $paySystem['appearance'] = (_cfg('mobile') || _cfg('mobileDetected')) ? $paySystem['appearanceMobile'] : $paySystem['appearance'];
            unset($paySystem['appearanceMobile']);
        }

        if (_cfg('requiredFieldsList')) {
            $user = new \eGamings\WLC\User();
            $paySystems = $user->addRequiredFieldsResources($paySystems, 'required');
        }

        $hookPaySystems = System::hook('api:paysystems', $paySystems);
        $paySystems = array_merge($paySystems, (is_array($hookPaySystems)) ? $hookPaySystems : []);
        
        return (array) $paySystems;
    }
}
