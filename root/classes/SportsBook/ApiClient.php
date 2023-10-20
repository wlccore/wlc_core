<?php

namespace eGamings\WLC\SportsBook;

/**
 * Class ApiClient
 * @package eGamings\WLC\SportsBook
 */
class ApiClient 
{
    protected $config;

    protected const METHOD_GET = 'GET';
    protected const METHOD_POST = 'POST';

    public const WIDGET_DAILY_MATCH = 'daily-match';
    public const WIDGET_POPULAR_EVENTS = 'popular-events';

    /**
     * Constructor
     * 
     * @param ApiClientConfig $config
     */
    public function __construct(ApiClientConfig $config) 
    {
        $this->config = $config;
    }

    /**
     * Returns widget by type
     *
     * @param string $type - widget type: daily-match, popular-events
     * @param string $language - data language
     * @param string $output - output type json/html
     * @return mixed
     */
    public function getWidget(string $type, string $language, string $output = 'json') 
    {
        return $this->request(
            self::METHOD_GET, 
            $this->getConfig()->getEndPoint('widgets') . '/get/' . $type, 
            [
                'clientId' => $this->getConfig()->getClientId(),
                'language' => $language,
                'output' => $output
            ]
        );
    }

    /**
     * Returns ApiClientConfig instance
     *
     * @return ApiClientConfig
     */
    protected function getConfig(): ApiClientConfig 
    {
        return $this->config;
    }

    /**
     * Returns full URI for request
     * 
     * @param string $path - request path
     * @param array $params - query params
     * @return string
     */
    protected function buildURI(string $path, array $params = []): string 
    {
        $query = http_build_query($params);
        return rtrim($this->getConfig()->getURL(), '/') . $path . (!empty($query) ? '?' . $query : '');
    }
    
    /**
     * Make http request
     * 
     * @param $method - http method
     * @param $path - request path
     * @param $params - request params
     * @return mixed
     */
    protected function request(string $method = 'GET', string $path, array $params = []) 
    {
        $isGET = $method === self::METHOD_GET;
        $uri = $this->buildURI($path, $isGET ? $params : []);
        
        $ch = curl_init();
        $curlOptions = [
            CURLOPT_URL => $uri,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => FALSE,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 30
        ];

        if (!$isGET) {
            $curlOptions[CURLOPT_POST] = 1;
            $curlOptions[CURLOPT_POSTFIELDS] = json_encode($params);
        }

        curl_setopt_array($ch, $curlOptions);
        return $this->executeRequest($ch);
    }

    /**
     * Curl request
     * 
     * @param $ch - curl instance
     * @return mixed
     */
    protected function executeRequest($ch)
    {
        $result = curl_exec($ch);
        $code = $this->getResponseCode($ch);

        if ($code !== 200) {
            throw new ApiClientException($result);
        }

        return $result;
    }

    /**
     * Get curl info
     * 
     * @param $ch - curl instance
     * @return int
     */
    protected function getResponseCode($ch): int
    {
        return curl_getinfo($ch, CURLINFO_HTTP_CODE);
    }
}
