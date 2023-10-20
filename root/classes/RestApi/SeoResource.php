<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Cache;
use eGamings\WLC\Seo;

class SeoResource extends AbstractResource
{
    /**
     *  @SWG\Definition(
     *     definition="seo",
     *     description="seo",
     *     type="object",
     * )
     */

    /**
     * @SWG\Get(
     *     path="/seo",
     *     description="Returns seo.",
     *     tags={"Seo"},
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Property(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/seo"
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
     * @public
     * @method get
     * @param $request
     * @param $query
     * @param $params
     * @return mixed {array}
     * @throws ApiException
     */
    public function get($request, $query, $params)
    {
        return self::getSeo();
    }

    /**
     * @return array
     */
    public static function getSeo(): array
    {
        return Cache::result('seo', function() {
            return Seo::getData();
        });
    }
}