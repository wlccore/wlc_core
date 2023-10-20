<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\HotEvents;

/**
 * @SWG\Tag(
 *     name="providers hot events",
 *     description="Get providers's hot events"
 * )
 */

/**
 * @class ProvidersHotEventsResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\HotEvents
 */
class ProvidersHotEventsResource extends AbstractResource
{
    public static $allowedMerchants = [
        'pinnacle',
        'pinnaclesw'
    ];

    /**
     * @SWG\Get(
     *     path="/providers/hotEvents",
     *     description="Get providers's hot events",
     *     tags={"providers hot events"},
     *     @SWG\Parameter(
     *         in="query",
     *         name="merchant",
     *         type="string",
     *         description="Merchant name",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         ref="#/parameters/lang",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Hot events",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/Win"
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
     * Get providers's hot events
     *
     * @public
     * @method get
     * @param array $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array}
     * @throws {\Exception}
     */
    public function get($request, $query, $params = [])
    {
        $merchant = strtolower($request['merchant'] ?? '');
        $lang = strtolower($request['lang'] ?? $query['lang'] ?? 'en');

        if (!in_array($merchant, self::$allowedMerchants)) {
            throw new ApiException(_('Empty required parameter'), 400);
        }

        $hotEventHandler = new HotEvents();

        $result = $hotEventHandler->getEvents($merchant, $lang);

        if ($result === null) {
            throw new ApiException(_('system_error'), 403);
        }

        return $result;
    }
}
