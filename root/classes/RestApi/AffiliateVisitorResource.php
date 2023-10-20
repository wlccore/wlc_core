<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\Affiliate;

/**
 * @SWG\Tag(
 *     name="affVisitor",
 *     description="Affiliate visitor hits resource"
 * )
 */

/**
 * @class AffiliateVisitorResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 */
class AffiliateVisitorResource extends AbstractResource
{

    /**
     * @SWG\Post(
     *     path="/affVisitor",
     *     description="Affiliate visitors tracker",
     *     tags={"affVisitor"},
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 type="string",
     *                 example="ok"
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
     * Adds affiliate hit to db
     *
     * @method post
     * @param array $request
     * @param array $query
     * @param array $params
     * @return void
     */
    public function post(array $request, array $query, array $params = [])
    {
        if (_cfg('postTrackAffVisitors') && isset($request['faff']) && !empty($request['faff'])) {
            $affiliateClickId = Affiliate::appendCampaignName(
                http_build_query(
                    array_intersect_key($request, array_flip(Affiliate::$optionalFaffDataKeys)
                    )
                ), $request['sub']);
            Affiliate::affiliateUniqueVisitor($request['faff'], $affiliateClickId);
        } else {
            throw new ApiException(_('Method Not Allowed'), 400);
        }

    }
}
