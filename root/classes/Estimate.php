<?php
namespace eGamings\WLC;

use eGamings\WLC\RestApi\ApiException;

class Estimate extends System
{
    /** 
     * @return array 
     */
    public function get($currencyFrom, $currencyTo, $amount = null)
    {
        if(empty($currencyFrom)) {
            throw new ApiException('currencyFrom is required params', 404);
        }

        if(empty($currencyTo)) {
            throw new ApiException('currencyTo is required params', 404);
        }

        $response = $this->getEstimate($currencyFrom, $currencyTo, $amount);

        return $response;
    }

    /** 
     * @return array 
     */
    public function getEstimate($currencyFrom, $currencyTo, $amount)
    {
        $response = $this->SendRequest('GetEstimate', [
            'currencyFrom' => $currencyFrom,
            'currencyTo' => $currencyTo,
            'amount' => $amount,
        ]);

        $result = json_decode($response, true);
        
        if ($result['code'] == 200) {
            $result = $result['data'];
        } 

        if ($result['code'] == 404) {
            throw new ApiException($result['errors'][0], 404);
        } 


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

        $url = '/EstimateRouter/' . $path;

        $transactionId = $this->getApiTID($url);

        $hash = md5('EstimateRouter/'. $path . '/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));

        $params = array_merge($params, [
            'TID' => $transactionId,
            'Hash' => $hash,
        ]);

        $url .= '?' . http_build_query($params);

        return $this->runFundistAPI($url);
    }
 
}
