<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\Cache;
use eGamings\WLC\Classifier;
use eGamings\WLC\Config;

/**
 * @SWG\Tag(
 *     name="countries",
 *     description="Countries"
 * )
 */

/**
 * @class CountryResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Cache
 * @uses eGamings\WLC\Classifier
 */
class CountryResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/countries",
     *     description="Returns the translated list of countries",
     *     tags={"countries"},
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
     *                 property="countries",
     *                 type="array",
     *                 description="Country list",
     *                 example={{"value": "afg", "title": "Afghanistan", "phoneCode": "93"}},
     *                 @SWG\Items(
     *                     type="object"
     *                 )
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
     * Returns country list
     *
     * @protected
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array|mixed}
     */
    function get($request, $query, $params = [])
    {
        //If no language given, setting to default/current language
        $lang = !empty($request['lang']) ? $request['lang'] : _cfg('language');

        //Setting how should we order it, Z to A or A to Z (last one is default)
        $orderBy = isset($request['orderHow']) && $request['orderHow'] == 'desc' ? 'desc' : 'asc';

        $countries = Classifier::getCountryList($orderBy, $lang);

        $response = array(
            'lang' => $lang,
            'countries' => $countries
        );

        return $response;
    }

    public static function buildCountryList(): string
    {
        $countryResponse = (new CountryResource())->get([], []);

        return json_encode($countryResponse, JSON_UNESCAPED_UNICODE) ?: '';
    }
}
