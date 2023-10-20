<?php

declare(strict_types=1);

namespace eGamings\WLC\RestApi;

use eGamings\WLC\System;

/**
 * @SWG\Tag(
 *     name="PublicSocketsData",
 *     description="Socket info"
 * )
 */
class PublicSocketsDataResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/publicSocketsData",
     *     description="get socket info",
     *     tags={"SocketInfo"},
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Schema(
     *              @SWG\Property(property="api", type="string", example="cf245e111f837c45a795578c9899f28b"),
     *              @SWG\Property(property="token",type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VySWQiOiIwIiwidXNlckxvZ2luIjoiYW5vbnltb3VzIn0.OZTtB93sIC3qxOj5v2grhvKw7IZEymauo6MMlWojtto"),
     *              @SWG\Property(property="server",type="string", example="wss://egamings.com/ws"),
     *              @SWG\Property(property="server2",type="string", example="wss://egamings.com/ws1"),
     *          ),
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
    public function get(?array $request, array $query, array $params = []): array
    {
        $system = System::getInstance();
        $url = '/WLCClassifier/PublicSocketsData';
        $transactionId = $system->getApiTID($url);
        $hash = md5('WLCClassifier/PublicSocketsData/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
        $data = [
            'TID' => $transactionId,
            'Hash' => $hash,
        ];
        $url .= '?&' . http_build_query($data);

        $response = $system->runFundistAPI($url);
        $result = explode(',', $response, 2);
        if ((int)$result[0] === 1) {
            return json_decode($result[1], true, 512, JSON_THROW_ON_ERROR);
        }

        throw new ApiException($result[1], 400);
    }
}
