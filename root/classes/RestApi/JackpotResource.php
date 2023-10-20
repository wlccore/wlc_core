<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\System;
use eGamings\WLC\Front;
use eGamings\WLC\Cache;
use eGamings\WLC\Config;

/**
 * @SWG\Tag(
 *     name="jackpots",
 *     description="Jackpots"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="Jackpots",
 *     description="List of jackpots",
 *     type="array",
 *     example={{"LaunchCode": "Game launch code", "MerchantID": "997", "amount": "23031.35", "game": "Game name", "id": "123", "image": "/path/to/game/image.jpg"}},
 *     @SWG\Items(
 *         type="object"
 *     )
 * )
 */

/**
 * @class JackpotResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\System
 * @uses eGamings\WLC\Front
 */
class JackpotResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/jackpots",
     *     description="Returns list of jackpots",
     *     tags={"jackpots"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="merchant",
     *         in="query",
     *         description="merchant id",
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns list of jackpots",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/Jackpots"
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
     * Returns list of jackpots
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
        $jackpots = self::buildJackpots($request, $query, $params, false);

        header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 10)); // 10 seconds
        return $jackpots;
    }

    public static function buildJackpots($request, $query = [], $params = [], bool $serialize = true)
    {
        $siteConfig = Cache::result('siteConfig', function() {
            return Config::getSiteConfig();
        }, 60);

        if (is_array($siteConfig) && !empty($siteConfig['systemsGamePlayInfo'])) {
            $merchants = $siteConfig['systemsGamePlayInfo'];
        }

        $data = ['currency'=>Front::User('currencySign') ?: 'EUR'];
        if (isset($request['currency'])) {
            $data['currency'] = $request['currency'];
        }

        if (!empty($request['merchant'])) {
            $data['merchant'] = $request['merchant'];
        }

        $data['platform'] = (_cfg('mobile') || _cfg('mobileDetected')) ? 'mobile' : 'desktop';
        $jackpots = Cache::result(
            (isset($data['merchant']) ? $data['merchant'] . '_' : '') .
            $data['platform'] . '_jackpots_'.$data['currency'],
            function () use ($data) {
                $g = Front::Games();
                $jackpots = $g->getJackpots($data);

                $jackpots = json_decode($jackpots, 1);
                if (!is_array($jackpots)) {
                    return [];
                }

                $gameImages = GamesResource::getGameImages(['platform' => $data['platform']]);
                foreach($jackpots as &$jackpot) {
                    if (!empty($jackpot['image'])) {
                        $imageNames = [
                            $jackpot['MerchantID'].':'.pathinfo($jackpot['Image'],  PATHINFO_FILENAME) . '.svg',
                            $jackpot['MerchantID'].':'.pathinfo($jackpot['Image'],  PATHINFO_FILENAME) . '.jpg',
                        ];
                        foreach ($imageNames as $imageName) {
                            if (array_key_exists($imageName, $gameImages)) {
                                $jackpot['Image'] = $gameImages[$imageName];
                                break;
                            }
                        }
                    }

                    $jackpot['LaunchCode'] = str_replace(':', '--', $jackpot['LaunchCode']);
                }

                return $jackpots;
            },
            15, // ttl 15 sec
            [$data]
        );

        return $serialize ? (json_encode($jackpots, JSON_UNESCAPED_UNICODE) ?: '') : $jackpots;
    }
}
