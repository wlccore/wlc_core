<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\TablesInfo;

/**
 * @SWG\Tag(
 *     name="providers tables info",
 *     description="Get live providers's table info"
 * )
 */

/**
 * @class TablesInfoResource
 * @namespace eGamings\WLC\RestApi
 * @codeCoverageIgnore
 * @extends AbstractResource
 */
class ProvidersTablesInfoResource extends AbstractResource
{

    /**
     * @SWG\Get(
     *     path="/providers/tablesInfo",
     *     description="Get live providers's table info",
     *     tags={"providers tables info"},
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"provider"},
     *             @SWG\Property(
     *                 property="provider",
     *                 type="string",
     *                 description="Provider id"
     *             )
     *         )
     *     ),
     *     @SWG\Parameter(
     *         ref="#/parameters/lang",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="providers's table info"
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
     * Get live providers's table info
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
        $provider = strtolower($request['provider'] ?? $query['provider'] ?? '');
        $lang = strtolower($request['lang'] ?? $query['lang'] ?? 'en');

        if ($provider === '') {
            throw new ApiException(_('Empty required parameter'), 400);
        }

        $result = TablesInfo::getTablesInfo($provider, $lang);

        if ($result === null) {
            throw new ApiException(_('system_error'), 403);
        }

        return $result;
    }
}
