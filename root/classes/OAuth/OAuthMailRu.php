<?php

namespace eGamings\WLC\OAuth;


class OAuthMailRu extends AbstractOAuth
{
    public function getAuthUrl()
    {
        $url = 'https://connect.mail.ru/oauth/authorize'
            . '?client_id=' . $this->config['id']
            . '&response_type=code'
            . '&redirect_uri=' . urlencode($this->config['redirect_uri'] . '?lang=' . _cfg('language'));

        return $url;
    }

    public function getCodeVerificationRequestParams($request, $query)
    {
        if (empty($request['code'])) {
            throw new \Exception(empty($request['error']) ? 'OAuth error' : $request['error'], 400);
        }

        $params = array(
            'url' => 'https://connect.mail.ru/oauth/token',
            'post' => array(
                'code' => $query['code'],
                'redirect_uri' => $this->config['redirect_uri'] . '?lang=' . _cfg('language'),
                'grant_type' => 'authorization_code',
                'client_id' => $this->config['id'],
                'client_secret' => $this->config['private'],
            ),
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
    	
        $accessTokenData = json_decode($accessToken, true);

        $getParams = array(
            'app_id' => $this->config['id'],
            'session_key' => $accessTokenData['access_token'],
            'secure' => '1',
            'method' => 'users.getInfo'
        );

        ksort($getParams);
        $signString = '';
        foreach($getParams as $getParamName => $getParamValue) {
            $signString .= $getParamName.'='.$getParamValue;
        }

        $getParams['sig'] = md5($signString . $this->config['private']);

        $params = array(
            'url' => 'http://www.appsmail.ru/platform/api',
            'get' => $getParams,
        );

        return $params;
    }

    public function parseUserInfoResponse($response)
    {
        $response = json_decode($response, true);
        if (!$response || empty($response[0])) return false;
        $response = $response[0];

        $userInfo = array(
            'social' => 'ml',
            'firstName' => $response['first_name'],
            'lastName' => $response['last_name'],
            'social_uid' => $response['uid'],
            'photo' => $response['pic'],
            //'originalResponse' => $response
        );

        if (isset($response['email'])) {
            $userInfo['email'] = $response['email'];
            $userInfo['i_agree'] = 1;
        }

        return $userInfo;
    }
}
