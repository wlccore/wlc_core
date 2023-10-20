<?php
namespace eGamings\WLC;

use eGamings\WLC\RestApi\ApiException;

class Youtube extends System
{
    /** 
     * @return array 
     */
    public function get($lang)
    {
        if(empty($lang)) {
            throw new ApiException('lang is required params', 404);
        }

        $response = $this->getPlaylistItems($lang);

        return $response;
    }

    /** 
     * @return array 
     */
    public function getPlaylistItems($lang)
    {
        $response = $this->SendRequest('GetPlaylistItemsWithVideoDuration', ['lang' => $lang]);

        $result = json_decode($response);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException($response, 404);
        }

        return $result;
    }

    /**
     * Send GET request
     *
     * @private
     * @method SendRequest
     * @param {string} $path
     * @param {array} $params
     *
     * @return string
     */
    private function SendRequest($path, $params = [], $post = false)
    {
        $login = Front::User('id');

        $url = '/YoutubeRouter/' . $path;

        $transactionId = $this->getApiTID($url);

        $hash = md5('YoutubeRouter/'. $path . '/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));

        $params = array_merge($params, [
            'TID' => $transactionId,
            'Hash' => $hash,
        ]);

        $url .= '?' . http_build_query($params);

        return $this->runFundistAPI($url);
    }

}

