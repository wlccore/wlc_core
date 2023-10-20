<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\States;

/**
 * @SWG\Tag(
 *     name="states",
 *     description="States"
 * )
 */

/**
 * @class StatesResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 */
class StatesResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/states",
     *     description="Returns the translated list of states sorted by countries",
     *     tags={"states"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="orderHow",
     *         in="query",
     *         type="string",
     *         enum={"asc", "desc"},
     *         description="Sorting",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="lang",
     *                 type="string",
     *                 example="en",
     *                 description="Language"
     *             ),
     *             @SWG\Property(
     *                 property="states",
     *                 type="object",
     *                 description="States list",
     *                 example={"usa":{{"value":"US-OK","title":"Oklahoma"}}}
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
     * Returns states list
     *
     * @protected
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array|mixed}
     * @throws \Exception
     */
    protected function get($request, $query, $params = []): array
    {
        //If no language given, setting to default/current language
        $lang = !empty($request['lang']) ? $request['lang'] : _cfg('language');

        //Setting how should we order it, Z to A or A to Z (last one is default)
        $orderBy = isset($request['orderHow']) && $request['orderHow'] == 'desc' ? 'desc' : 'asc';

        return [
            'lang' => $lang,
            'states' => States::getStatesList($orderBy, $lang)
        ];
    }
}
