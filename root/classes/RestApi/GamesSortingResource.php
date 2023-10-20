<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\Cache;
use eGamings\WLC\Logger;
use eGamings\WLC\System;

/**
 * @SWG\Tag(
 *     name="games sorting",
 *     description="Returns newest or popular game list"
 * )
 */

/**
 * @class GamesSortingResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Logger
 * @uses eGamings\WLC\System
 */
class GamesSortingResource extends AbstractResource
{

    /**
     * @SWG\Get(
     *     path="/games/sorting/{type}",
     *     description="Returns popular or newest games list",
     *     tags={"games sorting"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="type",
     *         type="string",
     *         in="path",
     *         description="Type of sorting",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         type="number",
     *         in="query",
     *         description="Page"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns games list",
     *         @SWG\Schema(
     *             ref="#/definitions/Game"
     *         ),
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
    public function get($request, $query, $params = [])
    {
        $type = !empty($params['type']) ? $params['type'] : (!empty($query['type']) ? $query['type'] : '');
        $page = $request['page'] ?? 0;

        if (!in_array($type, ['new', 'popular'])) {
            throw new ApiException('Wrong type', 400);
        }
        if (!is_numeric($page)) {
            throw new ApiException('Wrong page', 400);
        }

        $list = Cache::result('api_games_sorting_' . $type . '_' . $page, function () use ($type, $page) {

            $url = '/Game/Sorting';
            $system = System::getInstance();
            $transactionId = $system->getApiTID($url);

            $hash = md5('Game/Sorting/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
            $params = [
                'TID' => $transactionId,
                'Hash' => $hash,
                'Type' => $type,
                'Page' => $page,
            ];
            $url .= '?&' . http_build_query($params);

            $response = $system->runFundistAPI($url);
            $list = json_decode($response, true);

            if (!$list || !is_array($list)) {
                return null;
            } else {
                foreach ($list as &$game) {
                    $launchCode = explode('/', $game['Url'], 2)[1];
                    $game['LaunchCode'] = str_replace(':', '--', $launchCode);
                }

                return $list;
            }

        },60 * 5, [$type, $page]);

        if ($list === null) {
            throw new ApiException('Empty list or unable fetch games sorting '. $type .' list', 400);
        }

        return $list;
    }
}
