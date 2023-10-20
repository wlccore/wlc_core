<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\Ajax;

/**
 * @class BinaryOptionsFeedResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses  eGamings\WLC\Ajax
 */
class BinaryOptionsFeedResource extends AbstractResource {

    /**
     * Returns last options
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array}
     */
    function get($request, $query, $params = []) {
        $ajax = new Ajax();
        $feed = $ajax->fetchLastOptions();

        return [
            'result' => ($feed != '') ? json_decode($feed) : []
        ];
    }
}
