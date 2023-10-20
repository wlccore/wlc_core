<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="deposit prestep",
 *     description="Deposit prestep"
 * )
 *
 * Class DepositPrestepResource
 * @package eGamings\WLC\RestApi
 */
class DepositPrestepResource extends AbstractResource
{
    /**
     * @SWG\Post(
     *     path="/deposits/prestep",
     *     description="Make deposit prestep action",
     *     tags={"deposits", "prestep"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"systemId", "amount"},
     *             @SWG\Property(
     *                 property="systemId",
     *                 type="integer",
     *                 description="Payment system id",
     *                 example="123"
     *             ),
     *             @SWG\Property(
     *                 property="amount",
     *                 type="number",
     *                 description="Amount",
     *                 example=300.50
     *             ),
     *             @SWG\Property(
     *                 property="additional",
     *                 type="object",
     *                 description="Additional params",
     *                 example={"phone": "79115554433", "account": "ASDER111"}
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success"
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * @param array $request
     * @param array $query
     * @param array $params
     * @return array|null
     * @throws \eGamings\WLC\RestApi\ApiException
     */
    public function post(array $request, array $query, array $params = []): ?array
    {
        $data = $request;
        $data['additional']['origUrl'] = $_SERVER['HTTP_HOST'] ?? '';

        $response = User::getInstance()->depositPrestep($data);
        $result = explode(',', $response, 2);

        if (!isset($result[1])) {
            throw new ApiException(_('Request invalid. Error: ') . $response, 400);
        }

        [$code, $message] = $result;

        if ((int)$code !== 1) {
            throw new ApiException(sprintf(_('Deposit failed: %s'), $message), 400);
        }

        try {
            return 1 === (int)$message ? null : json_decode($message, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $ex) {
            throw new ApiException(sprintf('JSON decode error: %s', $ex->getMessage()), 400);
        }
    }
}
