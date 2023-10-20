<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Sms\LinkMobilityProvider;
use eGamings\WLC\Sms\MrMessagingProvider;

/**
 * @SWG\Tag(
 *     name="sms callback handler",
 *     description="Sms DLR callbacks handler"
 * )
 */

/**
 * Class SmsCallbackResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Sms\MrMessagingProvider
 */
class SmsCallbackResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/sms/callback/{provider}",
     *     description="Handle SMS Providers DLR callbacks",
     *     tags={"sms callback handler"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="provider",
     *         in="path",
     *         type="string",
     *         required=true,
     *         description="Provider name"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="string",
     *             example="OK"
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
     * Handle DLR callbacks.
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {string}
     * @throws {ApiException}
     */
    public function get($request, $query, $params = [])
    {
        if (empty($params['provider'])) {
            throw new ApiException(_('Wrong request'), 400);
        }

        switch ($params['provider']) {
            case 'mrmessaging':
                MrMessagingProvider::hanldeCallback($request);
                return 'OK';
            default:
                throw new ApiException(_('Unknown provider'), 400);
        }
    }

    /**
     * @SWG\Post(
     *     path="/sms/callback/{provider}",
     *     description="Handle Link Mobility DLR callbacks",
     *     tags={"sms callback handler"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="provider",
     *         in="path",
     *         type="string",
     *         required=true,
     *         description="Provider name"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="string",
     *             example="OK"
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
     * Handle DLR callbacks.
     *
     * @public
     * @method post
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {string}
     * @throws {ApiException}
     */
    public function post($request, $query, $params = [])
    {
        if (empty($params['provider'])) {
            throw new ApiException(_('Wrong request'), 400);
        }

        switch ($params['provider']) {
            case 'linkmobility':
                LinkMobilityProvider::hanldeCallback($request);
                return 'OK';
            default:
                throw new ApiException(_('Unknown provider'), 400);
        }
    }

}
