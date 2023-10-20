<?php

declare(strict_types=1);

namespace eGamings\WLC\RestApi;

use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="SumDepositResource",
 *     description="Getting the amount of successful deposits"
 * )
 */

/**
 * @class SumDepositResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 */
class SumDepositResource extends AbstractResource
{
    public function get($request, $query, $params)
    {
        if (empty($_SESSION['user'])) {
            throw new ApiException('User is not authorized', 401);
        }
        $user = new User();

        $result = $user->fetchSumDeposits();

        return json_decode($result, true)['data'] ?? 0;
    }
}
