<?php

declare(strict_types=1);

namespace eGamings\WLC\RestApi;

use eGamings\WLC\PrometheusKey;
use eGamings\WLC\PrometheusKeys;
use eGamings\WLC\System;
use eGamings\WLC\User;

/**
 * @class MetricsResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 */
class MetricsResource extends AbstractResource
{

    public function __construct()
    {
        $this->URL = 'https://' . $_SERVER['SERVER_NAME'];
    }

    /**
     * @SWG\Get(
     *     path="/metrics",
     *     description="Get metrics",
     *     tags={"pdf"},
     *     @SWG\Response(
     *         response="200",
     *         description="metrics as array",
     *         @SWG\Schema(
     *             type="array",
     *         )
     *     ),
     * )
     */

    /**
     * @throws ApiException
     */
    public function get(?array $request, array $query, array $params = [])
    {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $token);

        if ($_SERVER['MetricsBearer'] !== $token || empty($token)) {
            throw new ApiException(_('User not authorized'), 401);
        }

        return \eGamings\WLC\PrometheusKeys::getInstance()->getRedisKeys();
    }
}
