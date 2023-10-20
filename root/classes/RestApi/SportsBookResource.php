<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\Games;
use eGamings\WLC\Cache;
use eGamings\WLC\SportsBook\ApiClient;
use eGamings\WLC\SportsBook\ApiClientConfig;

/**
 * @class SportsBookResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Games
 * @uses eGamings\WLC\Cache
 * @uses eGamings\WLC\SportsBook\ApiClient
 * @uses eGamings\WLC\SportsBook\ApiClientConfig
 */
class SportsBookResource extends AbstractResource
{
    /**
     * Calls sportsbook api methods
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @params {array} $params
     * @return {array}
     * @throws {\Exception}
     */
    public function get($request, $query, $params)
    {
        try {
            $action = !empty($params['action']) ? $params['action'] : '';

            switch ($action) {
                case 'widgets':
                    if (empty($query['widget']) || !is_string($query['widget'])) {
                        throw new ApiException('Widget type not specified');
                    }

                    $widget = $query['widget'];
                    $lang = isset($query['lang']) && is_string($query['lang']) ? $query['lang'] : _cfg('language');
                    $output = isset($query['output']) && is_string($query['output']) ? $query['output'] : 'json';

                    $result = Cache::result('sportbook:widgets', function() use ($widget, $lang, $output) {
                        $apiClient = $this->getApiClient();
                        return $apiClient->getWidget($widget, $lang, $output);
                    }, 60 * 5, [$widget, $lang, $output]);

                    if ($output === 'html') {
                        header('Content-Type: text/html; charset=utf-8');
                        echo $result;
                        exit();
                    }
                    
                    return json_decode($result, true);
                    
            }
        } catch (\Exception $e) {
            throw new ApiException($e->getMessage(), 400);
        }

        throw new ApiException('Method is not supported', 404);
    }

    /**
     * Returns Api Client
     * 
     * @return ApiClient
     */
    private function getApiClient(): ApiClient
    {
        $clientId = $this->getClientId();
        $apiURL   = _cfg('sportsbookApiURL');

        if (empty($clientId)) {
            throw new ApiException('Sportsbook is not configured');
        }

        if (empty($apiURL)) {
            throw new ApiException('Sportsbook api url is not configured');
        }

        $apiClientConfig = new ApiClientConfig();
        $apiClientConfig
            ->setClientId($clientId)
            ->setURL($apiURL);

        return new ApiClient($apiClientConfig);
    }

    /**
     * Returns client id
     * 
     * @return ApiClient
     */
    private function getClientId(): int
    {
        $clientId = Cache::get('sportsbook:client-id');

        if (isset($clientId)) {
            return $clientId;
        }

        $games = new Games();
        $result = $games->LaunchHTML(958, 'sportsbookNEW', true, true);

        if (is_array($result) && !empty($result['error'])) {
            throw new ApiException($result['error'], 400);
        }

        $params = json_decode($result, true);
        
        if (!isset($params['config']['siteId'])) {
            throw new ApiException('Sportsbook is not configured', 400);
        }

        Cache::set('sportsbook:client-id', $clientId, 60 * 60 * 3);

        return $params['config']['siteId'];
    }
}
