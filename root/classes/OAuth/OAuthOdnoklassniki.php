<?php

namespace eGamings\WLC\OAuth;


use eGamings\WLC\Storage\CookieStorage;

class OAuthOdnoklassniki extends AbstractOAuth
{
    public function getAuthUrl()
    {
        $url = 'http://www.odnoklassniki.ru/oauth/authorize'
            . '?client_id=' . $this->config['id']
            . '&redirect_uri=' . urlencode($this->config['redirect_uri'])
            . '&scope=GET_EMAIL'
            . '&response_type=code';

        CookieStorage::getInstance()->set('oauth_lang', _cfg('language'));

        return $url;
    }

    public function getCodeVerificationRequestParams($request, $query)
    {
        if (!isset($_GET['code']) || empty($_GET['code'])) {
            if (isset($_GET['error']) && !empty($_GET['error'])) {
                $err = $_GET['error'];
            } else {
                $err = 'Auth error';
            }

            throw new \Exception($err, 400);
        }

        $params = array(
            'url' => 'http://api.odnoklassniki.ru/oauth/token.do',
            'post' => array(),
            'get' => array(
                'code' => $_GET['code'],
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

        $accessToken = json_decode($accessToken, true);

        $params = array(
            'url' => 'http://api.odnoklassniki.ru/fb.do',
            'get' => array(
                'method' => 'users.getCurrentUser',
                'application_key' => $this->config['public'],
            	'fields' => 'UID,EMAIL,FIRST_NAME,LAST_NAME'
            ),
        );

        $sig_content = '';
        ksort($params['get']);
        foreach($params['get'] as $k => $v) {
        	$sig_content .= $k.'='.$v;
        }
        $sig_content .= md5($accessToken['access_token'] . $this->config['private']);

        $params['get']['access_token'] = $accessToken['access_token'];
        $params['get']['sig'] = md5($sig_content);

        return $params;
    }

    public function parseUserInfoResponse($response)
    {
    	global $cfg;

        $response = json_decode($response, true);
        
        $userInfo = array(
            'social' => 'ok',
            //'originalResponse' => $response
        );

        $cfg['language'] = CookieStorage::getInstance()->get('oauth_lang') ?: $cfg['language'];

        $userInfo['firstName'] = $response['first_name'];
        $userInfo['lastName'] = $response['last_name'];
        $userInfo['social_uid'] = $response['uid'];
        
        if (isset($response['email'])) {
        	$userInfo['email'] = $response['email'];
        }

        return $userInfo;
    }
}
