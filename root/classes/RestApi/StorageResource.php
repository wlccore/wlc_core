<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Front;

/**
 * @SWG\Tag(
 *     name="storage",
 *     description="User storage"
 * )
 */


/**
 * Work with current user storage
 *
 * @class StorageResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Front
 */
class StorageResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/storage/{key}",
     *     description="Returns the stored data for the key",
     *     tags={"storage"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="key",
     *         type="string",
     *         in="path",
     *         required=true
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Stored data",
     *         @SWG\Schema(
     *             type="object",
     *             example={"storageKey": {"storedDataKey1": "storedDataValue1", "storedDataKey2": "storedDataValue2"}}
     *         )
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Error",
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
     * Returns storage data by current user
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     * @return {array}
     * @throws {ApiException}
     */
    public function get($request, $query, $params)
    {
        $storage = Front::Storage();

        $user_id = (int)Front::User('id');

        $data_key = $this->getKey($params);

        $storageList = $storage->getStorageData($user_id, $data_key);

        if (!$storageList) {
            throw new ApiException('Not found', 404);
        }

        return $storageList;
    }

    /**
     * @SWG\Post(
     *     path="/storage/{key}",
     *     description="Create new or update user storage record by key",
     *     tags={"storage"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="key",
     *         type="string",
     *         in="path",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"value"},
     *             @SWG\Property(
     *                 property="value",
     *                 type="string"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="object",
     *             example={"storageKey": {"storedDataKey1": "storedDataValue1", "storedDataKey2": "storedDataValue2"}}
     *         )
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Error",
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
     * Create new or update user storage record by key
     *
     * @public
     * @method post
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     * @return {array}
     * @throws {ApiException}
     */
    public function post($request, $query, $params)
    {
        $user_id = (int)Front::User('id');

        if (!$user_id) {
            throw new ApiException('User is not authorized', 401);
        }

        $data_key = $this->getKey($params);

        if (!is_null($data_key)) {
            $request['key'] = $data_key;
        }

        $storage = Front::Storage();

        if (!$this->validateRequest($request)) {
            throw new ApiException('Bad request', 400);
        }

        $result = $storage->setRecord($user_id, $request);

        return $result;

    }

    /**
     * @SWG\Put(
     *     path="/storage/{key}",
     *     description="Update current user storage record by key",
     *     tags={"storage"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="key",
     *         type="string",
     *         in="path",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"value"},
     *             @SWG\Property(
     *                 property="value",
     *                 type="string",
     *                 description="JSON"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="object",
     *             example={"storageKey": {"storedDataKey1": "storedDataValue1", "storedDataKey2": "storedDataValue2"}}
     *         )
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Error",
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
     * Update current user storage record by key
     *
     * @public
     * @method put
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     * @return {array}
     * @throws {ApiException}
     */
    public function put($request, $query, $params)
    {
        $result = $this->post($request, $query, $params);

        return $result;
    }

    /**
     * @SWG\Delete(
     *     path="/storage/{key}",
     *     description="Delete current user storage record by key",
     *     tags={"storage"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="key",
     *         type="string",
     *         in="path",
     *         required=true
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Success",
     *         @SWG\Schema(
     *             type="object",
     *             example={"storageKey": {"storedDataKey1": "storedDataValue1", "storedDataKey2": "storedDataValue2"}}
     *         )
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Error",
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
     * Delete current user storage record by key
     *
     * @public
     * @method delete
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     * @return {array}
     * @throws {ApiException}
     */
    public function delete($request, $query, $params)
    {
        $user_id = (int)Front::User('id');

        if (!$user_id) {
            throw new ApiException('User is not authorized', 401);
        }

        $data_key = $this->getKey($params);

        if (is_null($data_key)) {
            throw new ApiException('Method not allowed', 405);
        }

        $storage = Front::Storage();

        $record = $storage->deleteStorageRecord($user_id, $data_key);

        return $record;

    }

    /**
     * Get key value from params
     *
     * @private
     * @method getKey
     * @param {array} $params
     * @return {mixed|null}
     */
    private function getKey($params)
    {
        return isset($params['key']) ? $params['key'] : null;
    }

    /**
     * Validate request params
     *
     * @private
     * @method validateRequest
     * @param {array} $request
     * @return {boolean}
     */
    private function validateRequest($request)
    {
        return !empty($request['key']) && !empty($request['value']);
    }

}
