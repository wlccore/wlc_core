<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Banners;
use eGamings\WLC\Cache;

class BannersResource extends AbstractResource
{
    /**
     *  @SWG\Definition(
     *     definition="bannerList",
     *     description="List of banners",
     *     type="object",
     *     @SWG\Property(
     *         property="html",
     *         type="string",
     *         description="html",
     *         example="<div></div>"
     *     ),
     *     @SWG\Property(
     *         property="platform",
     *         type="array",
     *         description="platform",
     *         example={"any", "desktop", "mobile"},
     *         @SWG\Items(
     *             type="string"
     *         )
     *     ),
     *     @SWG\Property(
     *         property="tags",
     *         type="array",
     *         description="tags",
     *         example={"home"},
     *         @SWG\Items(
     *             type="string"
     *         )
     *     ),
     *     @SWG\Property(
     *         property="visibility",
     *         type="array",
     *         description="visibility",
     *         example={"anyone", "anonymous", "authenticated"},
     *         @SWG\Items(
     *             type="string"
     *         )
     *     ),
     * )
     */

    /**
     * @SWG\Definition(
     *  definition="BannerV2",
     *  title="Banner resource",
     * 	@SWG\Property(property="id", type="integer", example="1"),
     * 	@SWG\Property(property="name", type="string", example="name"),
     * 	@SWG\Property(property="position", type="string", enum={"home", "catalog"}, example="home"),
     * 	@SWG\Property(property="sort", type="integer", example="5"),
     * 	@SWG\Property(property="dateStart", type="string", format="date-time", example="2020-10-01 16:00:00"),
     * 	@SWG\Property(property="dateEnd", type="string", format="date-time", example="2020-10-05 16:00:00"),
     * 	@SWG\Property(property="platform", type="string", enum={"desktop", "mobile"}, example="desktop"),
     * 	@SWG\Property(property="visibility", type="string", enum={"all", "auth", "noAuth"}, example="all"),
     * 	@SWG\Property(property="locale", type="string", example="en"),
     * 	@SWG\Property(
     *     property="countries",
     *     type="array",
     *      @SWG\Items(
     *          type="string",
     *          example="en"
     *      )
     * 	),
     * 	@SWG\Property(
     * 		property="langs",
     * 		type="array",
     *      @SWG\Items(
     *          type="string",
     *          example="en"
     *      )
     * 	),
     * 	@SWG\Property(
     *     property="source",
     *     type="object",
     *     additionalProperties={"type": "string"},
     *     example={"foo": "bar"}
     *  ),
     * 	@SWG\Property(
     *     property="button",
     *     type="object",
     *     additionalProperties={"type": "string"},
     *     example={"foo": "bar"}
     *  )
     * )
     */

    /**
     * @SWG\Get(
     *     path="/banners",
     *     description="Returns banners.",
     *     tags={"Banners"},
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Property(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/bannerList"
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
     * @SWG\Get(
     *     path="/banners2",
     *     description="Returns banners v2 (need change v1 to v2 in url and delete '2' in world 'banners2')",
     *     tags={"Banners"},
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Property(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/BannerV2")
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
        return self::getBanners($params['version'] ?? 'v1');
    }

    /**
     * @return array
     */
    public static function getBanners(string $version = 'v1'): array
    {
        $platform = _cfg('mobileDetected') ? 'mobile' : 'desktop';

        return Cache::result('apiFundistBanners', function() use ($version) {
            return _cfg('useFundistBanners') !== false
                ? ($version == 'v1' ? Banners::getBannersList() : Banners::getBannersListV2())
                : [];
        }, 60, [$platform, $version]);
    }
}