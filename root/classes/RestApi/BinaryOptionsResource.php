<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\System;
use eGamings\WLC\Trading;

/**
 * @class BinaryOptionsResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\System
 * @uses eGamings\WLC\Trading
 */
class BinaryOptionsResource extends AbstractResource {

    /**
     * Login of trading system and get url
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array}
     */
    function get($request, $query, $params = []) {
        $result = (object) [];

        //System::hook('api:binary-options:get', $result);
        
        $refresh = (!empty($query['refresh']) && $query['refresh'] == 'true') ? true : false;

        $result = Trading::login('url', $refresh);
        
        $tradingProxy = _cfg('tradingProxy');
        if ($tradingProxy) {
        	$urlParts = parse_url($result);
        	$result = $tradingProxy . '/' . _cfg('language') . ((isset($urlParts['query'])) ? '?' . $urlParts['query'] : '');
        }

        return [
            'result' => [ 'tradingURL' => $result ]
        ];
    }

    /**
     * Logout of trading system
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array}
     */
    function delete($request, $query, $params = []) {
    	$result = Trading::logout();
    	return [
    		'result' => $result
    	];
    }
}
