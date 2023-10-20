<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Cache;
use eGamings\WLC\System;

/**
 * @SWG\Tag(
 *     name="games sorts",
 *     description="Returns only list of sorts for games by sort type"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="gamesSorts",
 *     description="List of sorts for games (key is game id, value is sort value)",
 *     type="array",
 *     example={{"1555771": 10}, {"1555772": 20}},
 *     @SWG\Items(
 *         type="object"
 *     )
 * )
 */

/**
 * @class GamesSortsResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Logger
 * @uses eGamings\WLC\System
 */
class GamesSortsResource extends AbstractResource
{
    private const TYPES = [
        'new',
        'popular'
    ];

    private const SORT_TYPES = [
        'all',
        'auto' ,
        'global',
        'globalByCategories',
        'globalByLanguages',
        'globalByCountries',
        'globalPerCategoriesByCountries',
        'local',
        'localByCategories',
        'localByLanguages',
        'localByCountries',
        'localPerCategoriesByCountries',
    ];

    /**
     * @SWG\Get(
     *     path="/games/sorts/{sorttype}/{type}",
     *     description="Returns only list of sorts for games by sort type",
     *     tags={"games sorts"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="sorttype",
     *         type="string",
     *         in="path",
     *         description="Type of sort",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         name="type",
     *         type="string",
     *         in="path",
     *         description="Type of sorting (new/popular)",
     *         required=false
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns only list of sorts for games by sort type",
     *         @SWG\Schema(
     *             ref="#/definitions/gamesSorts"
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
     * @throws ApiException
     * @throws \Exception
     */
    public function get($request, $query, $params = []): ?array
    {
        $type = !empty($params['type']) ? $params['type'] : (!empty($query['type']) ? $query['type'] : '');
        $sortType = !empty($params['sorttype']) ? $params['sorttype'] : (!empty($query['sorttype']) ? $query['sorttype'] : '');

        if ($type && !in_array($type, self::TYPES)) {
            throw new ApiException('Wrong type', 400);
        }

        if (!$sortType || !in_array($sortType, self::SORT_TYPES)) {
            throw new ApiException('Wrong or empty sorttype', 400);
        }

        if ($sortType === 'auto' && !$type) {
            throw new ApiException('For auto-sorting, specify the type', 400);
        }

        $fullSortsList = $this->getFullSortsList($sortType, $type);

        if ($fullSortsList === null) {
            throw new ApiException('Empty list or unable fetch ' . $sortType . ' sort for games', 400);
        }

        return $this->getSortByType($fullSortsList, $sortType);
    }

    /**
     * Get all sorts for games
     *
     * @param string $sortType
     * @param string $type
     * @return array|null
     * @throws \Exception
     */
    private function getFullSortsList(string $sortType, string $type = ''): ?array
    {
        $sortType = ($sortType == 'auto') ? 'auto' : 'all';
        $typeNone = $type ?: 'none';

        $withName = isset($_GET['withname']) && (int)$_GET['withname'] === 1;
        $ignoreCache = isset($_GET['force']) && (int)$_GET['force'] === 1;

        $getFullSortsList = function () use ($type, $sortType, $withName) {
            $url = '/Game/Sorts';
            $system = System::getInstance();
            $transactionId = $system->getApiTID($url);

            $hash = md5('Game/Sorts/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
            $params = [
                'TID'      => $transactionId,
                'Hash'     => $hash,
                'Type'     => $type,
                'SortType' => $sortType,
                'WithName' => (int)$withName
            ];

            $url .= '?&' . http_build_query(array_filter($params));
            $response = $system->runFundistAPI($url);

            $list = json_decode($response, true);

            if (!$list || !is_array($list)) {
                return null;
            }

            return $list;
        };

        return $ignoreCache
            ? $getFullSortsList()
            : Cache::result('api_sorts_for_games_' . $sortType . '_' . $typeNone, $getFullSortsList, 5 * 60, [$sortType, $typeNone]);
    }

    /**
     * @param array $fullSortsList
     * @param string $sortType
     * @return array|null
     */
    private function getSortByType(array $fullSortsList, string $sortType): ?array
    {
        if (in_array($sortType, ['all', 'auto'], true)) {
            return $fullSortsList;
        }

        return array_column($fullSortsList, $sortType, 'ID');
    }
}
