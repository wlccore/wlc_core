<?php

namespace eGamings\WLC\OAuth;


class OAuthVkontakte extends AbstractOAuth
{
    public function getAuthUrl()
    {
        $url = 'http://oauth.vk.com/authorize'
            . '?client_id=' . $this->config['id']
            . '&redirect_uri=' . urlencode($this->config['redirect_uri'] . '?lang=' . _cfg('language'))
            . '&scope=email'
            . '&response_type=code';

        return $url;
    }

    public function getCodeVerificationRequestParams($request, $query)
    {
        if (empty($request['code'])) {
            throw new \Exception(empty($request['error']) ? 'OAuth error' : $request['error'], 400);
        }

        $params = array(
            'url' => 'https://oauth.vk.com/access_token',
            'get' => array(
                'client_id' => $this->config['id'],
                'client_secret' => $this->config['private'],
                'code' => $_GET['code'],
                'redirect_uri' => $this->config['redirect_uri'] . '?lang=' . _cfg('language')
            ),
        );

        return $params;
    }

    public function getBaseUrl($request, $query)
    {
        return '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getUserInfoRequestParams($accessToken)
    {
    	if ($accessToken === false) {
            throw new \Exception(_('Access token is missing'), 400);
    	}

        $accessToken = json_decode($accessToken, true);

        if (!isset($accessToken['user_id'])) {
            throw new \Exception(_('Empty access token user identifier'), 400);
        }

        if (!isset($accessToken['access_token'])) {
            throw new \Exception(_('Empty access token identifier'), 400);
        }

        $params = array(
            'url' => 'https://api.vk.com/method/users.get',
            'get' => array(
                'v' => '5.131',
                'user_id' => $accessToken['user_id'],
                'access_token' => $accessToken['access_token'],
                'fields' => 'first_name,last_name,email,photo_200',
            ),
        );

        return $params;
    }

    public function parseUserInfoResponse($response, $accessToken = null)
    {
        $response = json_decode($response, true);
   	   	$accessToken = ($accessToken) ? json_decode($accessToken, true) : [];

        if (empty($response['response'][0])) {
            throw new \Exception('Response is empty', 400);
        }

        $response = $response['response'][0];

        $userInfo = array(
            'social' => 'vk',
            'firstName' => $response['first_name'],
            'lastName' => $response['last_name'],
            'social_uid' => $response['id'],
            'photo' => $response['photo_200'],
            //'originalResponse' => $response
        );

        if (!empty($accessToken['email'])) {
            $userInfo['email'] = $accessToken['email'];
        }

        return $userInfo;
    }
}
