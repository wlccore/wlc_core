<?php

namespace eGamings\WLC\OAuth;

/**
 * @codeCoverageIgnore
 */
class OAuthInstagram extends AbstractOAuth
{
    public function getAuthUrl()
    {
        $url = 'https://api.instagram.com/oauth/authorize'
            . '?client_id=' . $this->config['id']
            . '&redirect_uri=' . urlencode($this->config['redirect_uri'] . '?lang=' . _cfg('language'))
            . '&scope=user_profile,user_media'
            . '&response_type=code';

        return $url;
    }

    public function getCodeVerificationRequestParams($request, $query)
    {
        if (empty($request['code'])) {
            throw new \Exception(empty($request['error']) ? 'OAuth error' : $request['error'], 400);
        }

        $params = [
            'url' => 'https://api.instagram.com/oauth/access_token',
            'post' => [
                'client_id' => $this->config['id'],
                'client_secret' => $this->config['private'],
                'code' => $request['code'],
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->config['redirect_uri'] . '?lang=' . _cfg('language'),
            ]
        ];

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

        $accessToken = json_decode($accessToken, true);

        $params = [
            'url' => 'https://graph.instagram.com/me',
            'get' => [
                'access_token' => $accessToken['access_token'],
                'fields' => 'id,account_type,media_count,username'
            ]
        ];

        return $params;
    }

    public function parseUserInfoResponse($response)
    {
        $response = json_decode($response, true);

        $userInfo = [
            'social' => 'in',
            'firstName' => $response['username'] ?? '',
            'lastName' => '',
            'social_uid' => $response['id'],
            'photo' => ''
        ];

        if (isset($response['email'])) {
            $userInfo['email'] = $response['email'];
        }

        return $userInfo;
    }
}
