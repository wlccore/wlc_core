<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\Cache;
use eGamings\WLC\Config;

/**
 * @SWG\Tag(
 *     name="currencies",
 *     description="Currencies"
 * )
 */

/**
 * @class CurrencyResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Cache
 * @uses eGamings\WLC\Config
 */
class CurrencyResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/currencies",
     *     description="Returns the list of currencies",
     *     tags={"currencies"},
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Schema(
     *             type="object",
     *             example={"1": {"ID": "29", "Name": "CZK", "ExRate": "26.45257714"}}
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
     * Returns currencies list
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array}
     */
    public function get($request, $query, $params = [])
    {
        $result = [];
        
        $siteConfig = Cache::result('siteConfig', function() {
        	return Config::getSiteConfig();
        }, 60);

        if (is_array($siteConfig) && !empty($siteConfig['currencies'])) {
            $result = $siteConfig['currencies'];
        }

        return $result;
    }
}
