<?php

declare(strict_types=1);

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Youtube;

/**
 * @SWG\Tag(
 *     name="YoutubeResource",
 *     description="Getting the youtube links and preview"
 * ),
 * @SWG\Definition(
 *     definition="Youtube",
 *     description="Youtube object",
 *     type="object",
 *     @SWG\Property(
 *         property="data",
 *         type="string",
 *         example="o"
 *     ),
 * )
 */

/**
 * @class YoutubeResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 */
class YoutubeResource extends AbstractResource
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
        return (new Youtube())->get($query['lang']);
    }
}

