<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\System;

/**
 * @SWG\Tag(
 *     name="paycryptos",
 *     description="Paycryptos payment system"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="Paycryptos",
 *     description="Paycryptos payment system",
 *     type="object",
 *     @SWG\Property(
 *         property="base",
 *         type="string",
 *         description="Base currency"
 *     ),
 *     @SWG\Property(
 *         property="rate",
 *         type="string",
 *         description="Rate"
 *     ),
 *     @SWG\Property(
 *         property="crypto_rate",
 *         type="string",
 *         description="Crypto rate"
 *     ),
 *     @SWG\Property(
 *         property="fiat_rate",
 *         type="string",
 *         description="Fiat rate"
 *     )
 * )
 */

class PaycryptosResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/paycryptos/currency/rate/{currencies}",
     *     description="Return cryptocurrency rate",
     *     tags={"paycryptos"},
     *     @SWG\Parameter(
     *         name="currencies",
     *         type="string",
     *         in="path",
     *         description="currencies through underscore (like 'btc_usd')",
     *         required=true
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns rate object",
     *         @SWG\Schema(
     *             ref="#/definitions/Paycryptos"
     *         ),
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
     * @return array|mixed
     * @throws ApiException
     */
    public function get($request, $query, $params = [])
    {
        $action = !empty($params['action']) ? $params['action'] : (!empty($query['action']) ? $query['action'] : null);
        $type = !empty($params['type']) ? $params['type'] : (!empty($query['type']) ? $query['type'] : null);
        $currencies = !empty($params['currencies']) ? $params['currencies'] : (!empty($query['currencies']) ? $query['currencies'] : null);

        $this->validateAction($action);
        $this->validateType($type);
        $this->validateCurrency($currencies);

        $url = strtolower($action) . '/' . strtolower($type) . ($currencies ? '/' . strtolower($currencies) : '');

        return json_decode(System::getInstance()->runPaycryptosAPI($url, false), true);
    }

    /**
     * @param string $action
     * @throws ApiException
     */
    private function validateAction(string $action)
    {
        if ($action !== 'currency') {
            throw new ApiException('Wrong paycryptos action', 400);
        }
    }

    /**
     * @param string $type
     * @throws ApiException
     */
    private function validateType(string $type)
    {
        if ($type !== 'rate') {
            throw new ApiException('Wrong paycryptos type', 400);
        }
    }

    private function validateCurrency(string $currencies)
    {
        $currenciesArray = explode('_', $currencies);

        if (!is_array($currenciesArray) || count($currenciesArray) !== 2) {
            throw new ApiException('Wrong currency format', 400);
        }
    }
}