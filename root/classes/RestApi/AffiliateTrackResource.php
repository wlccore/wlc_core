<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\System;
use eGamings\WLC\Affiliate; 

/**
 * @SWG\Tag(
 *     name="affTrack",
 *     description="Affiliate tracking resource"
 * )
 */

/**
 * @class AffiliateTrackResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Cache
 */
class AffiliateTrackResource extends AbstractResource
{
    static $image_base64 = 'R0lGODlhAQABAIAAAP///wAAACwAAAAAAQABAAACAkQBADs=';
    static $ttl = 3600;

    /**
     * @SWG\Get(
     *     path="/affTrack",
     *     description="Affiliate cookie tracker. Integration hook: 'api:afftracker'",
     *     tags={"affTrack"},
     *     @SWG\Response(
     *         response="200",
     *         description="Empty gif image binary",
     *         @SWG\Schema(type="file")
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
     * Returns empty gif image
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {string}
     * @throws {\Exception}
     */
    function get($request, $query, $params = [])
    {
        $affData = Affiliate::getAffiliateData();
        if (!empty($affData) && $affData['affiliateSystem'] == 'faff' && $redis = System::redis()) {
            $data = ['ip' => System::getUserIP(), 'data' => $affData];
            $keyData = json_encode($data);
            $key = 'affvisit:' . $data['ip'];
            if (!$redis->get($key)) {
                $result = $redis->setex($key, self::$ttl, $keyData);
                Affiliate::affiliateUniqueVisitor($affData['affiliateId'], $affData['affiliateClickId']);
            }
        }
        System::hook('api:afftracker', $affData);
        
        header('Content-Type: image/gif');
        header('Cache-Control: no-cache, no-store');
        echo base64_decode(self::$image_base64);
        exit();
    }
}
