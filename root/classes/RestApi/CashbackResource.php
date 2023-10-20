<?php

declare(strict_types=1);

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Cashback;
use eGamings\WLC\Config;
use eGamings\WLC\Front;
use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="cashback",
 *     description="Cashback"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="Cashback",
 *     description="Cashback tariff object",
 *     type="object",
 *     @SWG\Property(
 *         property="ID",
 *         type="integer",
 *         description="ID",
 *         example="638"
 *     ),
 *     @SWG\Property(
 *         property="Name",
 *         type="string",
 *         description="Name",
 *         example="Daily Rakeback"
 *     ),
 *     @SWG\Property(
 *         property="Period",
 *         type="string",
 *         enum={"Daily", "Weekly", "Biweekly", "Monthly"},
 *         description="Period of payouts",
 *         example="Daily"
 *     ),
 *     @SWG\Property(
 *         property="Amount",
 *         type="number",
 *         description="Award amount",
 *         example="128.00"
 *     ),
 *     @SWG\Property(
 *         property="AvailableAt",
 *         type="string",
 *         description="Date when payout will be available",
 *         example="2021-07-30 01:00:00"
 *     ),
 *     @SWG\Property(
 *         property="ExpiresAt",
 *         type="string",
 *         description="Expiration date",
 *         example="2021-07-29 17:00:00"
 *     ),
 *     @SWG\Property(
 *         property="Available",
 *         type="boolean",
 *         description="Claim available",
 *         example=true
 *     )
 * )
 */
class CashbackResource extends AbstractResource
{
    /**
     * @var Cashback
     */
    private $cashbackService;

    public function __construct(?Cashback $cashbackService = null)
    {
        $this->cashbackService = $cashbackService ?? new Cashback();
    }

    public function handle($method, $request, $query, $params = [])
    {
        if (!(Config::getSiteConfig()['AllowPayCashbackByClaim'] ?? false)) {
            throw new ApiException(_('Unknown route path'), 404);
        }

        return parent::handle($method, $request, $query, $params);
    }

    /**
     * @SWG\Get(
     *     path="/cashback",
     *     description="Returns cashback tariffs available to user",
     *     tags={"cashback"},
     *     @SWG\Response(
     *         response="200",
     *         description="Cashback tariffs list",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(
     *                 ref="#/definitions/Cashback"
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
     * @throws ApiException
     */
    public function get(?array $request, array $query, array $params = []): array
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        $user = Front::User();

        return $this->cashbackService->getListForUser($user);
    }

    /**
     * @SWG\Post(
     *     path="/cashback/{id}",
     *     description="Pay cashback for user",
     *     tags={"cashback"},
     *     @SWG\Parameter(
     *         name="id",
     *         required=true,
     *         type="integer",
     *         in="path",
     *         description="Cashback tariff ID"
     *     ),
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
    public function post(?array $request, array $query, array $params = []): array
    {
        if (!User::isAuthenticated()) {
            throw new ApiException(_('User is not authorized'), 401);
        }

        if (!$cashbackId = $params['id'] ?? null) {
            throw new ApiException(_('Empty cashback id'), 400);
        }

        $user = Front::User();

        return $this->cashbackService->payForUser($user, (int)$cashbackId);
    }
}
