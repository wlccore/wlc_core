<?php

namespace eGamings\WLC\OAuth;


class OAuthYandex extends AbstractOAuth
{
    public function getAuthUrl()
    {
        $url = 'https://oauth.yandex.ru/authorize'
            . '?client_id=' . $this->config['id']
            . '&state=' . urlencode(_cfg('site'))
            . '&response_type=code';

        return $url;
    }

    public function getCodeVerificationRequestParams($request, $query)
    {
        if (empty($query['code'])) {
            throw new \Exception(empty($query['error']) ? 'OAuth error' : $query['error'], 400);
        }

        $params = array(
            'url' => 'https://oauth.yandex.ru/token',
            'post' => array(
                'code' => $query['code'],
                'redirect_uri' => urlencode($this->config['redirect_uri'] . '?lang=' . _cfg('language')),
                'grant_type' => 'authorization_code',
                'client_id' => $this->config['id'],
                'client_secret' => $this->config['private'],
            ),
        );

        return $params;
    }

    public function getBaseUrl($request, $query)
    {
        return empty($query['state']) ? '' : $query['state'];
    }

    public function getUserInfoRequestParams($accessToken)
    {
    	if ($accessToken === false) {
    		throw new \Exception('Access token is missing', 400);
    	}
    	
        $accessToken = json_decode($accessToken, true);

        $params = array(
            'url' => 'https://login.yandex.ru/info',
            'headers' => array('Authorization: OAuth ' . $accessToken['access_token']),
            'get' => array(
                'format' => 'json',
                'access_token' => $accessToken['access_token'],
            ),
        );

        return $params;
    }

    public function parseUserInfoResponse($response)
    {
        $response = json_decode($response, true);
        $userInfo = array(
            'social' => 'ya',
            'firstName' => $response['first_name'],
            'lastName' => $response['last_name'],
            'social_uid' => $response['id'],
            'photo' => 'https://graph.facebook.com/' . $response['social_uid'] . '/picture?type=large',
            //'originalResponse' => $response
        );

        if (isset($response['default_email'])) {
            $userInfo['email'] = $response['default_email'];
            $userInfo['i_agree'] = 1;
        }

        return $userInfo;
    }
}
