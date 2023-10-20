<?php

declare(strict_types=1);

namespace eGamings\WLC\RestApi;

use eGamings\WLC\StreamWheel;
use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="StreamWheelParticipants",
 *     description="Stream wheel participants"
 * )
 */
class StreamWheelParticipantsResource extends AbstractResource
{
    /**
     * @var StreamWheel
     */
    private $service;

    public function __construct(?StreamWheel $service = null)
    {
        $this->service = $service ?? new StreamWheel();
    }

    /**
     * @SWG\Post(
     *     path="/streamWheel/participants",
     *     description="Join to stream wheel",
     *     tags={"StreamWheel"},
     *
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="id",
     *                 type="integer",
     *                 description="Wheel ID",
     *                 example="234"
     *             ),
     *         )
     *     ),
     *
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Schema(
     *              @SWG\Property(
     *                  property="result",
     *                  type="boolean",
     *                  example="true"
     *              )
     *          ),
     *    ),
     *    @SWG\Response(
     *         response="401",
     *         description="User is not authorized",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *    ),
     *    @SWG\Response(
     *         response="405",
     *         description="Stream wheel not allowed",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *    ),
     *    @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *    )
     * )
     */

    /**
     * @param array|null $request
     * @param array $query
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public function post(?array $request, array $query, array $params = []): array
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $User = User::getInstance();

        $this->service->join($User->userData, (int)($request['id'] ?? 0));

        return [
            'result' => true,
        ];
    }
}
