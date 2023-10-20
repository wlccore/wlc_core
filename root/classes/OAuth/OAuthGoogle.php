<?php

namespace eGamings\WLC\OAuth;


use eGamings\WLC\Storage\CookieStorage;

class OAuthGoogle extends AbstractOAuth
{
    public function getAuthUrl()
    {
        // temporary register oauth callback language
        CookieStorage::getInstance()->set('oauth_lang', _cfg('language'));
        $state = [ 'lang' => _cfg('language')];
        
        $url = 'https://accounts.google.com/o/oauth2/auth'
            . '?redirect_uri=' . urlencode($this->config['redirect_uri'])
            . '&client_id=' . $this->config['id']
            . '&scope=https://www.googleapis.com/auth/userinfo.email'
            . '&response_type=code'
            . '&state='.base64_encode(json_encode($state));

        return $url;
    }

    public function getCodeVerificationRequestParams($request, $query)
    {
        if (empty($request['code'])) {
            throw new \Exception(empty($request['error']) ? 'OAuth error' : $request['error'], 400);
        }

        $params = array(
            'url' => 'https://accounts.google.com/o/oauth2/token',
            'post' => array(
                'code' => $request['code'],
                'redirect_uri' => $this->config['redirect_uri'],
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
        
        $params = array(
            'url' => 'https://www.googleapis.com/oauth2/v1/userinfo',
            'get' => array(
                'access_token' => $accessTokenData['access_token'],
            ),
        );

        return $params;
    }

    public function parseUserInfoResponse($response)
    {
        $response = json_decode($response, true);
        
        $userInfo = array(
            'social' => 'gp',
            'firstName' => $response['given_name'],
            'lastName' => $response['family_name'],
            'social_uid' => $response['id'],
            'photo' => $response['picture'],
            //'originalResponse' => $response
        );

        if (isset($response['email'])) {
            $userInfo['email'] = $response['email'];
            $userInfo['i_agree'] = 1;
        }
        
        $state = isset($_GET['state']) ? json_decode(base64_decode($_GET['state']), true) : [];
        
        if (!empty($state['lang'])) {
            _cfg('language', $state['lang']);
        } else {
            _cfg('language', CookieStorage::getInstance()->get('oauth_lang') ?: _cfg('language'));
        }
        
        return $userInfo;
    }
}
