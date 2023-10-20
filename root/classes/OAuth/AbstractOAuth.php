<?php

namespace eGamings\WLC\OAuth;


abstract class AbstractOAuth
{
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @return string
     */
    abstract public function getAuthUrl();

    /**
     * @param array $request
     * @param array $query
     * @return array
     */
    abstract public function getCodeVerificationRequestParams($request, $query);

    /**
     * @param array $accessToken
     * @return array
     */
    abstract public function getUserInfoRequestParams($accessToken);

    /**
     * @param $response
     * @return array
     */
    abstract public function parseUserInfoResponse($response);

    /**
     * @param array $request
     * @param array $query
     * @return string
     */
    abstract public function getBaseUrl($request, $query);
}
