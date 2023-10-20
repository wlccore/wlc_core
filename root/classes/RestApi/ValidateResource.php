<?php

namespace eGamings\WLC\RestApi;

/**
 * @SWG\Tag(
 *     name="validation",
 *     description="Validation"
 * )
 */

/**
 * @class ValidateResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Front
 * @uses eGamings\WLC\User
 */
class ValidateResource extends AbstractResource
{
    /**
     * @SWG\Post(
     *     path="/validate/{validator}",
     *     description="Data validation",
     *     tags={"validation"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="validator",
     *         type="string",
     *         in="path",
     *         required=true,
     *         description="Type of the validator",
     *         enum={"user-profile", "user-register"}
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"data", "fields"},
     *            @SWG\Property(
     *                property="data",
     *                type="object",
     *                description="Data for validation",
     *                example={"email": "test@egamings.com", "password": "123321"}
     *            ),
     *            @SWG\Property(
     *                property="fields",
     *                type="array",
     *                description="Validation fields (if empty then all)",
     *                example={"email", "pasword"},
     *                @SWG\Items(
     *                    type="object"
     *                )
     *            )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns validation result",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="result",
     *                 type="boolean"
     *             ),
     *             @SWG\Property(
     *                 property="errors",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object"
     *                 )
     *             )
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
     * Validate data with optional fields array
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array}
     * @throws {\Exception}
     */
    public function post($request, $query, $params = [])
    {
        $rulesClassName = str_replace('-','',ucwords($params['type'],'-')).'ValidatorRules';
        $rulesClassPath = 'eGamings\WLC\Validators\Rules\\'.$rulesClassName;

        if (!class_exists($rulesClassPath)) {
            return [
                'result' => false,
                'errors' => [
                    '$server' => 'Class '.$rulesClassPath.' not found.'
                ]
            ];
        }

        $rules = new $rulesClassPath();
        if (!$rules->checkInputParams($request['data'], $request['fields'])) {
            return [
                'result' => false,
                'errors' => [
                    '$server' => 'Empty or broken body payload. Example: {data : {name : \'bob\', surname: \'foo\'}, fields : [\'name\']}'
                ]
            ];
        }

        return $rules->validate($request['data'], $request['fields']);
    }
}
