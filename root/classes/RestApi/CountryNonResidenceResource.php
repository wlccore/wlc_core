<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Service\CountryNonResidence;
use eGamings\WLC\User;

/**
 * @SWG\Tag(
 *     name="CountryNonResidenceConfirmation",
 *     description="Country non-residence confirmation"
 * )
 */
class CountryNonResidenceResource extends AbstractResource
{
    /**
     * @SWG\Post(
     *     path="/countrynonresidence",
     *     description="Confirmation that the user is not physically in a forbidden country",
     *     tags={"CountryNonResidenceConfirmation"},
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="confirm",
     *                 type="number",
     *                 description="Confirm",
     *                 example=1
     *             )
     *         )
     *     ),
     *     @SWG\Parameter(
     *         ref="#/parameters/lang",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="If confirmation is successful",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="result",
     *                 type="boolean"
     *            )
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
     * @param $request
     * @param $query
     * @param $params
     * @return true[]
     * @throws ApiException
     */
    public function post($request, $query, $params = [])
    {
        if (empty($request['confirm']) || $request['confirm'] !== 1) {
            throw new ApiException(_('Empty required parameter') . ': confirm', 400);
        }
        try {
            $CNR = new CountryNonResidence(User::getInstance());
            $CNR->saveConfirmation();
        } catch (\ErrorException $ex) {
            throw new ApiException($ex->getMessage(), 400);
        }

        return ['result' => true];
    }
}
