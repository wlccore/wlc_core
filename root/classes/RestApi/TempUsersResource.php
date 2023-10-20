<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Db;
use eGamings\WLC\User;


/**
 * @SWG\Tag(
 *     name="tempUsers",
 *     description="Getting list of the temp users"
 * )
 */

/**
 * @class TempUsersResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\User
 * @uses eGamings\WLC\System
 * @uses eGamings\WLC\Front
 */
class TempUsersResource extends AbstractResource
{
    /**
     * @SWG\Post(
     *     path="/tempUsers",
     *     description="Get temp users (not confirmed email)",
     *     tags={"tempUsers"},
     *      @SWG\Parameter(
     *         name="action",
     *         type="string",
     *         in="query",
     *     ),
     *     @SWG\Parameter(
     *         name="limit",
     *         type="integer",
     *         in="body",
     *     ),
     *     @SWG\Parameter(
     *         name="offset",
     *         type="integer",
     *         in="body",
     *     ),
     *      @SWG\Parameter(
     *         name="page",
     *         type="integer",
     *         in="body",
     *     ),
     *      @SWG\Parameter(
     *         name="csv",
     *         type="boolean",
     *         in="body",
     *     ),
     *      @SWG\Parameter(
     *         name="email",
     *         type="string",
     *         in="body",
     *     ),
     *      @SWG\Parameter(
     *         name="sort",
     *         type="string",
     *         in="body",
     *         example="id,-email",
     *         description="Fields to sort"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Schema(
     *              @SWG\Property(
     *                  property="id",
     *                  type="number"
     *              ),
     *              @SWG\Property(
     *                  property="email",
     *                  type="string"
     *              )
     *          ),
     *    ),
     *     @SWG\Response(
     *          response="400",
     *          description="Error",
     *          @SWG\Schema(
     *              ref="#/definitions/ApiException"
     *          )
     *     )
     * )
     */

     /**
     * @SWG\Post(
     *     path="/tempUsers/Activation",
     *     description="Finish registration",
     *     tags={"tempUsers"},
     *     @SWG\Response(
     *         response="200",
     *         description="Successful activation",
     *         @SWG\Schema(
     *              ref="#/definitions/UserProfile"
     *          ),
     *    ),
     * )
     */

         /**
     * @SWG\Post(
     *     path="/tempUsers/ResendEmail",
     *     description="Resend email",
     *     tags={"tempUsers"},
     *     @SWG\Response(
     *         response="200",
     *         description="Successful activation",
     *    ),
     * )
     */

    /**
     * @public
     * @method post
     * @param {array} $request
     * @param {array} $query
     * @param {array} [$params=[]]
     * @return {array}
     * @throws {ApiException}
     */
    public function post($request, $query, $params = [])
    {
        if (_cfg('newTempUsersEndpoint')) {
            throw new ApiException(_('Method Not Allowed'), 405);
        }

        $response = ApiEndpoints::buildResponse(200, 'success', User::tempUsers($request ?? [], $params['action'] ?? ''));
        exit(json_encode($response, JSON_UNESCAPED_UNICODE));
    }
}
