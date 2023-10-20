<?php
namespace eGamings\WLC\OAuth;

use eGamings\WLC\Storage\CookieStorage;
use eGamings\WLC\RestApi\ApiException;
use eGamings\WLC\Logger;

class OAuthTwitter extends AbstractOAuth
{
    public function getAuthUrl()
    {
        $params = array(
            'url' =>  'https://api.twitter.com/oauth/request_token',
            'ka_url' => (defined('KEEPALIVE_PROXY') ? KEEPALIVE_PROXY : 'https:/') . '/api.twitter.com/oauth/request_token',
            'callback' => urlencode($this->config['redirect_uri'] . '?lang=' . _cfg('language')),
            'id' => $this->config['id'],
            'secret' => $this->config['private'],
            'nonce' => md5(uniqid(rand(), true)),
            'time' => time(),
        );

        // Parameter sequence is strict.
        $oauth_base_text = 'GET&'
            . urlencode($params['url']) . '&'
            . urlencode('oauth_callback=' . $params['callback'] . '&')
            . urlencode('oauth_consumer_key=' . $params['id'] . '&')
            . urlencode('oauth_nonce=' . $params['nonce'] . '&')
            . urlencode('oauth_signature_method=HMAC-SHA1&')
            . urlencode('oauth_timestamp=' . $params['time'] . '&')
            . urlencode('oauth_version=1.0');

        // Формируем ключ
        // На конце строки-ключа должен быть амперсанд & !!!
        $key = $params['secret'] . '&';

        // Формируем oauth_signature
        $signature = base64_encode(hash_hmac('sha1', $oauth_base_text, $key, true));

        // Формируем GET-запрос
        $url = $params['ka_url']
            . '?oauth_callback=' . $params['callback']
            . '&oauth_consumer_key=' . $params['id']
            . '&oauth_nonce=' . $params['nonce']
            . '&oauth_signature=' . urlencode($signature)
            . '&oauth_signature_method=HMAC-SHA1'
            . '&oauth_timestamp=' . $params['time']
            . '&oauth_version=1.0';

        // Выполняем запрос
        $response_data = @file_get_contents($url);
        $response = [];
        parse_str($response_data, $response);

        if (empty($response_data) || empty($response['oauth_token_secret'])) {
            Logger::log('Empty TW Oauth: ' . json_encode([$url, $response_data, $response]));
            throw new ApiException(_('Unable fetch oauth access token'), 400);
        }

        CookieStorage::getInstance()->set('social_oauth_token_secret', $response['oauth_token_secret']);

        $authUrl = 'https://api.twitter.com/oauth/authenticate?oauth_token=' . $response['oauth_token'];

        return $authUrl;
    }

    public function getCodeVerificationRequestParams($request, $query)
    {
        if (empty($request['oauth_token'])) {
            throw new \Exception(empty($request['error']) ? 'OAuth error' : $request['error'], 400);
        }

        $params = array(
            'url' => 'https://api.twitter.com/oauth/access_token',
            'callback' => urlencode(_cfg('site') . '/' . _cfg('language') . '/social/login/twitter'),
            'id' => $this->config['id'],
            'secret' => $this->config['private'],
            'token' => $request['oauth_token'],
            'verifier' => $request['oauth_verifier'],
            'nonce' => md5(uniqid(rand(), true)),
            'time' => time(),
        );

        // oauth_token_secret получаем из сессии, которую зарегистрировали
        // во время запроса request_token
        $oauth_token_secret = CookieStorage::getInstance()->get('social_oauth_token_secret');

        $oauth_base_text = "GET&"
            . urlencode($params['url']) . "&"
            . urlencode("oauth_consumer_key=" . $params['id'] . "&")
            . urlencode("oauth_nonce=" . $params['nonce'] . "&")
            . urlencode("oauth_signature_method=HMAC-SHA1&")
            . urlencode("oauth_token=" . $params['token'] . "&")
            . urlencode("oauth_timestamp=" . $params['time'] . "&")
            . urlencode("oauth_verifier=" . $params['verifier'] . "&")
            . urlencode("oauth_version=1.0");

        $key = $params['secret'] . "&" . $oauth_token_secret;
        $oauth_signature = base64_encode(hash_hmac("sha1", $oauth_base_text, $key, true));

        $params = array(
            'url' => $params['url'],
            'get' => array(
                'oauth_nonce' => $params['nonce'],
                'oauth_signature_method' => 'HMAC-SHA1',
                'oauth_timestamp' => $params['time'],
                'oauth_consumer_key' => $params['id'],
                'oauth_token' => $params['token'],
                'oauth_verifier' => $params['verifier'],
                'oauth_signature' => $oauth_signature,
                'oauth_version' => '1.0'
            )
        );

        return $params;
    }

    public function getBaseUrl($request, $query)
    {
        return '';
    }

    public function getUserInfoRequestParams($accessToken)
    {
        if ($accessToken === false) {
            throw new \Exception('Access token is missing', 400);
        }
        
        parse_str($accessToken, $accessToken);
        $oauth_nonce = md5(uniqid(rand(), true));

        // время когда будет выполняться запрос (в секундых)
        $oauth_timestamp = time();

        $oauth_token = $accessToken['oauth_token'];
        $oauth_token_secret = $accessToken['oauth_token_secret'];
        $screen_name = $accessToken['screen_name'];

        $oauth_base_text = "GET&"
            . urlencode('https://api.twitter.com/1.1/account/verify_credentials.json') . '&'
            . urlencode('include_email=true' . '&')
            . urlencode('oauth_consumer_key=' . $this->config['id'] . '&')
            . urlencode('oauth_nonce=' . $oauth_nonce . '&')
            . urlencode('oauth_signature_method=HMAC-SHA1&')
            . urlencode('oauth_timestamp=' . $oauth_timestamp . "&")
            . urlencode('oauth_token=' . $oauth_token . "&")
            . urlencode('oauth_version=1.0');

        $key = $this->config['private'] . '&' . $oauth_token_secret;
        $signature = base64_encode(hash_hmac("sha1", $oauth_base_text, $key, true));
        
        $signature_headers = [
            'oauth_consumer_key' => $this->config['id'],
            'oauth_nonce' => $oauth_nonce,
            'oauth_signature' => $signature,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $oauth_timestamp,
            'oauth_token' => $oauth_token,
            'oauth_version' => '1.0'
        ];
        
        $signature_headers_values = [];
        
        foreach($signature_headers as $sig_k => $sig_v) {
            $signature_headers_values[] = $sig_k . '="'.urlencode($sig_v).'"';
        }

        // Формируем GET-запрос
        $params = array(
            'url' => 'https://api.twitter.com/1.1/account/verify_credentials.json',
            'get' => array(
                'include_email' => 'true'
            ),
            'headers' => [
                'Authorization: OAuth ' . implode(', ', $signature_headers_values)
            ]
        );

        return $params;
    }

    public function parseUserInfoResponse($response)
    {
        $response = json_decode($response, true);
        $name_parts = explode(' ', $response['name'], 2);
        
        $userInfo = array(
            'social' => 'tw',
            'firstName' => $name_parts[0],
            'lastName' => empty($name_parts[1]) ? '' : $name_parts[1],
            'social_uid' => $response['id'],
            'photo' => $response['profile_image_url'],
            //'originalResponse' => $response
        );

        if (isset($response['email'])) {
            $userInfo['email'] = $response['email'];
        }

        return $userInfo;
    }
}
