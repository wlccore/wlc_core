<?php

declare(strict_types=1);

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Estimate;

/**
 * @SWG\Tag(
 *     name="EstimateResource",
 *     description="Get estimate"
 * ),
 * @SWG\Definition(
 *     definition="Estimate",
 *     description="Estimate object",
 *     type="object",
 *     @SWG\Property(
 *         property="data",
 *         type="string",
 *         example=""
 *     ),
 * )
 */

/**
 * @package YoutubeResource
 * @see AbstractResource
 */
class EstimateResource extends AbstractResource
{

    /**
     * @SWG\Get(
     *     path="/youtube",
     *     description="Returns all videos from playlist",
     *     tags={"youtube"},
     *     @SWG\Response(
     *         response="200",
     *         description="Video list",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/Youtube"
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
     *
     * @param ?array $request
     * @param ?array $query
     * @param ?array $params
     */
    public function get(?array $request, ?array $query, ?array $params)
    {
        return (new Estimate())->get($query['currencyFrom'], $query['currencyTo'], $query['amount']);
    }
}

