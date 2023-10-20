<?php

declare(strict_types=1);

namespace eGamings\WLC\Traits;

use eGamings\WLC\Logger;
use eGamings\WLC\RestApi\ApiException;
use eGamings\WLC\System;
use JsonException;

trait HasFuncoreRequests
{
    /**
     * @param string $uri
     * @param array $params
     * @param array $hashParams
     *
     * @throws ApiException
     *
     * @return array|int|null
     */
    private function request(string $uri, array $params = [], array $hashParams = [])
    {
        $transactionId = System::getInstance()->getApiTID($uri);

        $uriParts = explode('/', trim($uri, '/ '));
        $hashBase = implode('/', $hashParams ?: $params);

        $hash = md5(
            $h = implode('/', [
                implode('/', array_slice($uriParts, 0, 2)),
                '0.0.0.0',
                $transactionId,
                _cfg('fundistApiKey'),
                $hashBase,
                _cfg('fundistApiPass'),
            ])
        );

        $params = array_merge($params, [
            'TID' => $transactionId,
            'Hash' => $hash,
        ]);

        $response = System::getInstance()->runFundistAPI($uri . '?&' . http_build_query($params));
        [$code, $data] = explode(',', $response, 2);

        if ((int)$code !== 1) {
            Logger::log('Error: ' . $response);

            throw new ApiException(_($data), 400);
        }

        if ($data) {
            try {
                $result = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                Logger::log('Error: ' . $response);

                throw new ApiException(_($response), 400);
            }
        }

        return $result;
    }
}
