<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\Trading;

/**
 * @class ThirdPartySystemsResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Trading
 */
class ThirdPartySystemsResource extends AbstractResource
{
    /**
     * List of services
     *
     * @property $servicesList
     * @type array
     * @private
     * @static
     */
    private static $servicesList = ['trading'];

    /**
     * Returns list of services
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @return {array}
     */
    public function get($request, $query, $params = [])
    {
        return ['ServicesAvailable' => self::$servicesList];
    }

    /**
     * Login user and call service
     *
     * @public
     * @method post
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     * @return {string}
     */
    public function post($request, $query, $params)
    {
        $service = !empty($params['service']) ? strtolower($params['service']) : '';

        if (!empty($request['login']) && !empty($request['password'])) {

            if (!empty($service) && !in_array($service, self::$servicesList))
                throw new ApiException(_('Service not found'), 404);

            $authResource = new AuthResource();
            $authResult = $authResource->put($request, $query);

            if (!empty($authResult['result']['loggedIn'])) {
                unset($authResult['result']['tradingURL']);

                switch ($service) {
                    case 'trading':
                        $trading = Trading::login('json')['config'];
                        unset($trading['userIP']);

                        if (empty($trading['token']))
                            throw new ApiException(_('Unauthorized'), 401);

                        return $trading;
                        break;
                    default:
                        return $authResult;
                        break;
                }

            }
        } else throw new ApiException(_('Invalid request parameters'), 400);
    }
}