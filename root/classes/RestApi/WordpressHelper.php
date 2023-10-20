<?php

namespace eGamings\WLC\RestApi;

class WordpressHelper
{
    public static function getDataFromWordpress(string $pathInWordpress, array $data, array $query, bool $isSeoData = false)
    {
        $lang = $query['lang'] ?? 'en';
        $qtranslateMode = _cfg('qtranslateMode');

        if (empty($qtranslateMode)) {
            throw new ApiException("Config error (qtranslateMode is empty)");
        }

        if ($qtranslateMode == 'query') {
            $data['lang'] = $lang;
        }

        $url = KEEPALIVE_PROXY . '/self_' .  $_SERVER['SERVER_NAME'] . '/content/' . ($qtranslateMode == 'pre-path' ? $lang : '') ."/wp-json/wp/v2/$pathInWordpress?" . http_build_query($data);

        if ($isSeoData) {
            $url = KEEPALIVE_PROXY . '/self_' .  $_SERVER['SERVER_NAME'] . '/content/' . ($qtranslateMode == 'pre-path' ? $lang : '') ."/wp-json/seo-plugin/v1/$pathInWordpress?" . http_build_query($data);
        }

        if (isset($query['debug']) && $query['debug'] == 1 && _cfg('env') != 'prod') {
            return [$url];
        }

        $ch = curl_init();
        $curlOptions = array(
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => true,
        );
        curl_setopt_array($ch, $curlOptions);

        $response = curl_exec($ch);

        if ($errno = curl_errno($ch)) {
            $message = curl_strerror($errno);
            throw new ApiException("cURL error ({$errno}):\n {$message}");
        }

        curl_close($ch);

        try {
            $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            return $result;
        } catch (\Exception $e) {
            throw new ApiException($e->getMessage());
        }
    }
}