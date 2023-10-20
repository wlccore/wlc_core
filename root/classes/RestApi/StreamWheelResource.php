<?php

declare(strict_types=1);

namespace eGamings\WLC\RestApi;

use eGamings\WLC\StreamWheel;
use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="StreamWheel",
 *     description="Stream wheel"
 * )
 */
class StreamWheelResource extends AbstractResource
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
     * @SWG\Get(
     *     path="/streamWheel",
     *     description="Get info about stream wheel",
     *     tags={"StreamWheel"},
     *
     *     @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         type="integer",
     *         description="Wheel ID",
     *     ),
     *
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Schema(
     *              @SWG\Property(
     *                  property="id",
     *                  type="integer",
     *                  description="Wheel ID",
     *                  example="123"
     *              ),
     *              @SWG\Property(
     *                  property="amount",
     *                  type="float",
     *                  description="Amount of prize fund",
     *                  example="100.5"
     *              ),
     *              @SWG\Property(
     *                  property="duration",
     *                  type="integer",
     *                  description="Draw duration in seconds",
     *                  example="60"
     *              ),
     *              @SWG\Property(
     *                  property="winnersCount",
     *                  type="integer",
     *                  description="Winners count",
     *                  example="3"
     *              ),
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
     *
     * @throws ApiException
     *
     * @return array
     */
    public function get(?array $request, array $query, array $params = []): array
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $User = User::getInstance();

        return $this->service->get($User->userData, (int)($request['id'] ?? 0));
    }

    /**
     * @SWG\Post(
     *     path="/streamWheel",
     *     description="Create a stream wheel",
     *     tags={"StreamWheel"},
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="amount",
     *                 type="float",
     *                 description="Amount of prize fund",
     *                 example="100.5"
     *             ),
     *             @SWG\Property(
     *                 property="duration",
     *                 type="integer",
     *                 description="Draw duration in seconds",
     *                 example="60"
     *             ),
     *             @SWG\Property(
     *                 property="winnersCount",
     *                 type="integer",
     *                 description="Winners count",
     *                 example="3"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Schema(
     *              @SWG\Property(
     *                  property="result",
     *                  type="boolean",
     *                  example="true"
     *              ),
     *              @SWG\Property(
     *                  property="wheelId",
     *                  type="integer",
     *                  example="12345"
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

        $amount = (float)($request['amount'] ?? 0);
        $duration = (int)($request['duration'] ?? 0);
        $winnersCount = (int)($request['winnersCount'] ?? 0);

        if ($amount <= 0 || $duration <= 0 || $winnersCount <= 0) {
            throw new ApiException(_('Wrong incoming params'), 400);
        }

        $User = User::getInstance();

        $requestData = [
            'Amount' => $amount,
            'Duration' => $duration,
            'WinnersCount' => $winnersCount,
        ];

        if (!empty($request['currency'])) {
            $requestData['CurrencyName'] = $request['currency'];
        }

        return [
            'result' => true,
            'wheelId' => $this->service->add($User->userData, $requestData),
        ];
    }

    /**
     * @SWG\Put(
     *     path="/streamWheel",
     *     description="Finish the stream wheel",
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
     *
     * @throws ApiException
     *
     * @return array
     */
    public function put(?array $request, array $query, array $params = []): array
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $User = User::getInstance();

        $this->service->finish($User->userData);

        return [
            'result' => true,
        ];
    }

    /**
     * @SWG\Delete(
     *     path="/streamWheel",
     *     description="Cancel a stream wheel",
     *     tags={"StreamWheel"},
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
     * @return true[]
     * @throws ApiException
     */
    public function delete(?array $request, array $query, array $params = []): array
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $User = User::getInstance();

        $this->service->cancel($User->userData);

        return [
            'result' => true,
        ];
    }
}
