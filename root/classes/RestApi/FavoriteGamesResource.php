<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\Config;
use eGamings\WLC\Front;

/**
 * @SWG\Tag(
 *     name="favorites",
 *     description="Favorite games"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="Favorites",
 *     description="Favorite games",
 *     type="object",
 *     @SWG\Property(
 *         property="game_id",
 *         type="string",
 *         description="Game id"
 *     )
 * )
 */

/**
 * @class FavoriteGamesResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Config
 * @uses eGamings\WLC\Front
 */
class FavoriteGamesResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/favorites",
     *     description="Returns favorites games list",
     *     tags={"favorites"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns favorites games list",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/Favorites"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="User is not authorized",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
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
     * Returns favorites games list
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array|boolean}
     * @throws {ApiException}
     */
    public function get($request, $query, $params = []){
        $user_id = (int)Front::User('id');

        if (!$user_id) {
            throw new ApiException('User is not authorized', 401);
        }

        $g = Front::Games();
        return $g->getFavoritesGames();
    }

    /**
     * @SWG\Post(
     *     path="/favorites/{id}",
     *     description="Add/Remove game from list games favorites of user",
     *     tags={"favorites"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         type="integer",
     *         required=true,
     *         description="Game id"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns favorites games list",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="game_id",
     *                 type="integer",
     *                 description="Game id"
     *             ),
     *             @SWG\Property(
     *                 property="favorite",
     *                 type="integer",
     *                 enum={0, 1},
     *                 description="Favorite status"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="User is not authorized",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     ),
     * )
     */

    /**
     * Add/Remove game from list games favorites of user
     *
     * @public
     * @method post
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     * @return {array|boolean}
     * @throws {ApiException}
     */
    public function post($request, $query, $params)
    {
        $user_id = (int)Front::User('id');

        if (!$user_id) {
            throw new ApiException('User is not authorized', 401);
        }

        $id = isset($params['id']) ? (int)$params['id'] : null;
        if (is_null($id)) {
            throw new ApiException('Bad request'.json_encode($request), 400);
        }
        $g = Front::Games();
        return $g->addRemoveGameFavorites($id);
    }
}
