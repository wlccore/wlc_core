<?php

namespace eGamings\WLC\RestApi;

use eGamings\WLC\System;
use eGamings\WLC\PrometheusKeys;

/**
 * @class AbstractResource
 * @namespace eGamings\WLC\RestApi
 */
abstract class AbstractResource
{
    /**
     * Run method of class
     *
     * @public
     * @method handle
     * @param {string} $method
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     * @return {mixed}
     * @throws {ApiException}
     */
    public function handle($method, $request, $query, $params = [])
    {
        if ($method == 'options') {
            return $this->handleOptions();
        }

        if (!method_exists($this, $method)) {
            throw new ApiException(_('Method not allowed'), 405);
        }

        $body = $this->$method($request, $query, $params);

        return $body;
    }

    private function handleOptions() {
        $httpMethods = ['get','post','put','patch','delete'];
        $result = [];
        $apiClass = get_class($this);
        $apiMethods = get_class_methods($apiClass);
        $apiReflection = null;
        try {
            $apiReflection = new \ReflectionClass($apiClass);
        } catch (\Exception $ex) {
        }

        foreach($httpMethods as $httpMethod) {
            if (($pos = array_search($httpMethod, $apiMethods)) === false) {
                continue;
            }
            
            $methodInfo = array();

            if ($apiReflection) {
                try {
                    $apiReflectionMethod = $apiReflection->getMethod($httpMethod);
                    // @TODO convert description to normal doc format
                    //$methodInfo['description'] = $apiReflectionMethod->getDocComment();
                    $methodInfo['name'] = $httpMethod;
                } catch (\Exception $ex) {
                    
                }
            }
            
            $result[strtoupper($httpMethod)] = $methodInfo;
        }
        
        $allowedMethods = array_keys($result);
        if (!empty($allowedMethods)) {
            header('Access-Control-Allow-Methods: ' . implode(',', $allowedMethods));
        }

        $allowedHeaders = array_merge([
            'Authorization',
            'Origin',
            'X-Requested-With',
            'Content-Type',
            'Accept',
            'X-UA-Fingerprint',
        ], _cfg('accessControlAllowHeaders') ?: []);

        header(sprintf('Access-Control-Allow-Headers: %s', implode(', ', $allowedHeaders)));
        
        return $result;
}

    protected function checkCountryForbidden() {
        if (System::isCountryForbidden(_cfg('userCountry'), System::getUserIP())) {
            PrometheusKeys::getInstance()->AUTH_FORBIDDEN_COUNTRY->store();
            throw new ApiException(_('Your country is on the forbidden list'), 403);
        }
    }

    protected function translateIncompleteProfileError(?string $error = ''): array
    {
        if (!$error) {
            PrometheusKeys::getInstance()->AUTH_PROFILE_INCOMPLETE->store();
            return [_('User profile is incomplete')];
        }

        $message = substr($error, 0, strpos($error, '(') - 1);
        preg_match('/\(([^)]+)\)/',$error, $fields);
        if ($fields) {
            $fields = array_map(static function($f) {
                return _(trim($f));
            }, explode(',',$fields[1]));

            return [_($message) . ' (' . implode(', ', $fields) . ')'];
        }

        return [$error];
    }
}
