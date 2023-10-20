<?php

namespace eGamings\WLC\RestApi;

class WordpressSeoResource extends AbstractResource
{

    /**
     * @SWG\Get(
     *     path="/static/seo/data",
     *     description="Returns wordpress seo data.",
     *     tags={"Wordpress seo"},
     *     @SWG\Parameter(ref="#/parameters/lang"),
     *     @SWG\Response(
     *         response="200",
     *         description="Wordpress seo data",
     *         @SWG\Property(type="object")
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
     * @SWG\Get(
     *     path="/static/seo/games",
     *     description="Returns wordpress seo games.",
     *     tags={"Wordpress seo"},
     *     @SWG\Parameter(ref="#/parameters/lang"),
     *     @SWG\Response(
     *         response="200",
     *         description="Wordpress seo games",
     *         @SWG\Property(type="object")
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
     *
     * @public
     * @method get
     * @param array|null $request
     * @param array $query
     * @param array $params
     * @return array {array}
     * @throws ApiException
     */
    public function get(?array $request, array $query, array $params = []): array
    {
        $params['dataType'] = $params['dataType'] ?? '';

        switch ($params['dataType']) {
            case 'data':
                return WordpressHelper::getDataFromWordpress('seo-data', [], $query, true);
            case 'games':
                return WordpressHelper::getDataFromWordpress('seo-data-games', [], $query, true);
            default:
                throw new ApiException('Bad data type');
        }
    }
}
