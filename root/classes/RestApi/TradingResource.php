<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\Trading;

/**
 * @class TradingResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Trading
 */
class TradingResource extends AbstractResource
{
    /**
     * Login user and returns url for trading system
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
	 * @params {array} [$params=[]]
     * @return {string}
     */
	public function get($request, $query, $params = []) {
		$result = Trading::login();
		return $result;
	}

    /**
     * Logout user of trading system
     *
     * @public
     * @method delete
     * @param {array} $request
     * @param {array} $query
	 * @params {array} [$params=[]]
     * @return {array}
     */
	public function delete($request, $query, $params = []) {
		$result = Trading::logout();
		return $result;
	}
}
