<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Provider\Service\ITrustDevice;
use eGamings\WLC\Provider\IUser;
use eGamings\WLC\Core;
use eGamings\WLC\Service\TrustDevice;

/**
 * @SWG\Tag(
 *     name="trustDevices",
 *     description="Control of the user's trust devices"
 * )
 */

/**
 * @class TrustDevicesResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 */
class TrustDevicesResource extends AbstractResource
{
    /**
     * @SWG\Get(
     *     path="/trustDevices",
     *     description="Get user's devices",
     *     tags={"trustDevices"},
     *     @SWG\Response(
     *         response="200",
     *         description="List of user's devices",
     *         @SWG\Schema(
     *              @SWG\Property(
     *                  property="id",
     *                  type="number"
     *              ),
     *              @SWG\Property(
     *                  property="email",
     *                  type="string"
     *              )
     *          ),
     *    ),
     *     @SWG\Response(
     *          response="400",
     *          description="Error",
     *          @SWG\Schema(
     *              ref="#/definitions/ApiException"
     *          )
     *     )
     * )
     */

    /**
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @param {array} [$params=[]]
     * @return {array}
     * @throws {ApiException}
     */
    public function get($request, $query, $params = [])
    {
        try {
            return $this->getTrustDeviceService([
                'user' => $this->getUser()
            ])->fetchAllDevices();
        } catch (\ErrorException $e) {
            throw new ApiException($e->getMessage(), 403);
        }
    }

    /**
     * @SWG\Post(
     *     path="/trustDevices",
     *     description="Add a trust device",
     *     tags={"trustDevices"},
     *     @SWG\Parameter(
     *         name="code",
     *         type="integer",
     *         required=true,
     *         in="query",
     *         description="Confirmation code"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Result of the request",
     *         @SWG\Schema(
     *              @SWG\Property(
     *                  property="result",
     *                  type="boolean"
     *              )
     *          ),
     *    ),
     *     @SWG\Response(
     *          response="400",
     *          description="Error",
     *          @SWG\Schema(
     *              ref="#/definitions/ApiException"
     *          )
     *     )
     * )
     */

    /**
     * @public
     * @method post
     * @param {array} $request
     * @param {array} $query
     * @param {array} [$params=[]]
     * @return {array}
     * @throws {ApiException}
     */
    public function post($request, $query, $params = []): array
    {
        try {
            $code = $request['code'] ?? false;
            $login = $request['login'] ?? false;
            if ($code === false || $login === false) {
                throw new ApiException(_('Empty required parameter'), 403);
            }

            $loginType = (bool)filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'login';

            $result = $this->getTrustDeviceService()->processCode($code, $login, $loginType);

            $message = _('Code accepted, the device has been added');

            if (_cfg('trustDevicesEnabled') === TrustDevice::$STATUS_ALWAYS) {
                $message = _('Code accepted');
            }

            return [
                'result' => $result,
                'message' => $result ? $message : ''
            ];
        } catch (\ErrorException $e) {
            throw new ApiException($e->getMessage(), 403);
        }
    }

    /**
     * @SWG\Delete(
     *     path="/trustDevices",
     *     description="Delete a trust device from the user's devices",
     *     tags={"trustDevices"},
     *     @SWG\Parameter(
     *         name="deviceId",
     *         type="integer",
     *         required=true,
     *         in="query",
     *         description="Device ID to delete"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Result of the request",
     *         @SWG\Schema(
     *              @SWG\Property(
     *                  property="result",
     *                  type="boolean"
     *              )
     *          ),
     *    ),
     *     @SWG\Response(
     *          response="400",
     *          description="Error",
     *          @SWG\Schema(
     *              ref="#/definitions/ApiException"
     *          )
     *     )
     * )
     */

    /**
     * @public
     * @method delete
     * @param {array} $request
     * @param {array} $query
     * @param {array} [$params=[]]
     * @return {array}
     * @throws {ApiException}
     */
    public function delete($request, $query, $params = [])
    {
        try {
            $deviceId = (int) ($query['deviceId'] ?? false);

            if ($deviceId === 0) {
                throw new ApiException(_('Empty required parameter'), 403);
            }

            return [
                'result' => $this->getTrustDeviceService([
                    'user' => $this->getUser()
                ])->setDeviceTrustStatus($deviceId, false)
            ];
        } catch (\ErrorException $e) {
            throw new ApiException($e->getMessage(), 403);
        }
    }

    /**
     * @SWG\Patch(
     *     path="/trustDevices",
     *     description="Adds a device to trusted",
     *     tags={"trustDevices"},
     *     @SWG\Parameter(
     *         name="deviceId",
     *         type="integer",
     *         required=true,
     *         in="query",
     *         description="Device ID to add"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Result of the request",
     *         @SWG\Schema(
     *              @SWG\Property(
     *                  property="result",
     *                  type="boolean"
     *              )
     *          ),
     *    ),
     *     @SWG\Response(
     *          response="400",
     *          description="Error",
     *          @SWG\Schema(
     *              ref="#/definitions/ApiException"
     *          )
     *     )
     * )
     */

    /**
     * @public
     * @method patch
     * @param {array} $request
     * @param {array} $query
     * @param {array} [$params=[]]
     * @return {array}
     * @throws {ApiException}
     */
    public function patch($request, $query, $params = [])
    {
        try {
            $deviceId = (int) ($query['deviceId'] ?? false);

            if ($deviceId === 0) {
                throw new ApiException(_('Empty required parameter'), 403);
            }

            return [
                'result' => $this->getTrustDeviceService([
                    'user' => $this->getUser(),
                ])->setDeviceTrustStatus($deviceId, true)
            ];
        } catch (\ErrorException $e) {
            throw new ApiException($e->getMessage(), 403);
        }
    }


    protected function getTrustDeviceService(array $params = []): ITrustDevice
    {
        return Core::DI()->make('service.trust_device', $params);
    }

    /**
     * @throws ApiException
     */
    protected function getUser(): IUser
    {
        $user = Core::DI()->get('user');

        if (!$user->isUser(false)) {
            throw new ApiException(_('User is not authorized'), 403);
        }

        return $user;
    }
}
