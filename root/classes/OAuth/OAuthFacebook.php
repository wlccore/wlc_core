<?php

namespace eGamings\WLC\OAuth;


class OAuthFacebook extends AbstractOAuth
{
    public function getAuthUrl()
    {
        $url = 'https://www.facebook.com/dialog/oauth'
            . '?client_id=' . $this->config['id']
            . '&redirect_uri=' . urlencode($this->config['redirect_uri'] . '?lang=' . _cfg('language'))
            . '&scope=email,public_profile'
            . '&response_type=code';

        return $url;
    }

    public function getCodeVerificationRequestParams($request, $query)
    {
        if (empty($request['code'])) {
            throw new \Exception(empty($request['error']) ? 'OAuth error' : $request['error'], 400);
        }

        $params = array(
            'url' => 'https://graph.facebook.com/oauth/access_token',
            'get' => array(
                'code' => $request['code'],
                'redirect_uri' => $this->config['redirect_uri'] . '?lang=' . _cfg('language'),
                'client_id' => $this->config['id'],
                'client_secret' => $this->config['private']
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
    	
        $accessToken = json_decode($accessToken, true);

        $params = array(
            'url' => 'https://graph.facebook.com/me',
            'get' => array(
                'access_token' => $accessToken['access_token'],
                'fields' => 'id,name,picture,email'
            ),
        );

        return $params;
    }

    public function parseUserInfoResponse($response)
    {
        $response = json_decode($response, true);
        $name_parts = explode(' ', $response['name'], 2);

        $userInfo = array(
            'social' => 'fb',
            'firstName' => $name_parts[0],
            'lastName' => empty($name_parts[1]) ? '' : $name_parts[1],
            'social_uid' => $response['id'],
            'photo' => 'https://graph.facebook.com/' . $response['id'] . '/picture?type=large',
            //'originalResponse' => $response
        );

        if (isset($response['email'])) {
            $userInfo['email'] = $response['email'];
        }

        return $userInfo;
    }
}
