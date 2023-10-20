<?php

namespace eGamings\WLC\SportsBook;

/**
 * Class ApiClientConfig
 * @package eGamings\WLC\SportsBook
 */
class ApiClientConfig {
    protected $clientId = 0;
    protected $url = '';

    protected $endPoints = [
        'widgets' => '/static/v1/widgets'
    ];

    /**
     * Set client id
     * 
     * @param int $id
     * @return ApiClientConfig
     */
    public function setClientId(int $id): ApiClientConfig 
    {
        $this->clientId = $id;
        return $this;
    }

    /**
     * Get client id
     * 
     * @return int
     */
    public function getClientId(): int {
        return $this->clientId;
    }

    /**
     * Set base api URL
     * 
     * @param string $url
     * @return ApiClientConfig
     */
    public function setURL(string $url): ApiClientConfig 
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Return base api URL
     * 
     * @return string
     */
    public function getURL(): string {
        return $this->url;
    }

    /**
     * Return end point by name
     * 
     * @return string
     */
    public function getEndPoint(string $type): string 
    {
        return isset($this->endPoints[$type]) ? $this->endPoints[$type] : '';
    }
}
