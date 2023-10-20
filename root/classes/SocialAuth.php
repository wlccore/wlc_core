<?php

namespace eGamings\WLC;

use eGamings\WLC\RestApi\ApiException;
use eGamings\WLC\OAuth\AbstractOAuth;


/* auth data
Yandex: devegames@yandex.ru : devxa_pass
yandex for test: devegamessoc@yandex.ru : devxa_pass
https://oauth.yandex.ru/client/my
---
Facebook: devegames@yandex.ru : devxa_pass
Phone: +371 22111700
---
google: devegamings@gmail.com : devxa_pass
---
odnoklassniki: devegames@yandex.ru : devxa_pass
---
vk: +37122111700 (devegames@yandex.ru) : devxa_pass
---
twitter: devegames : devxa_pass
email: (devegames@yandex.ru)
---
mail.ru: devegames@mail.ru : devxa_pass
mail:ru for test: devegamessoc@mail.ru : devxa_pass
http://api.mail.ru/sites/my
*/

class SocialAuth extends System
{
    /**
     * Returns social part of system config.
     *
     * @return string
     */
    public function getSocialConfig()
    {
        return _cfg('social');
    }

    /**
     * Returns config params for particular provider.
     *
     * @param $providerName
     * @return array
     * @throws \Exception
     */
    public function getProviderConfig($providerName)
    {
        $config = $this->getSocialConfig();

        try {
            $providerConfig = $config[$providerName];
        } catch (\Exception $e) {
            throw new \Exception('No social config: ' . $providerName);
        }

        if (empty($providerConfig['redirect_uri'])) {
            $providerConfig['redirect_uri'] = _cfg('site') . '/api/v1/auth/social/oauth_cb/' . $providerName;
        }

        return $providerConfig;
    }

    /**
     * Returns social provider class name by provider name (id).
     *
     * @param $providerName
     * @return string
     * @throws \Exception
     *
     * @codeCoverageIgnore
     */
    protected function getProviderClassByName($providerName)
    {
        $providerClassMapping = array(
            'gp' => 'OAuthGoogle',
            'fb' => 'OAuthFacebook',
            'tw' => 'OAuthTwitter',
            'vk' => 'OAuthVkontakte',
            'ok' => 'OAuthOdnoklassniki',
            'ml' => 'OAuthMailRu',
            'in' => 'OAuthInstagram'
        );

        if (empty($providerClassMapping[$providerName])) {
            throw new \Exception(_('Unsupported authentication provider') . ': ' . $providerName);
        }

        $providerClass = $providerClassMapping[$providerName];

        return 'eGamings\\WLC\\OAuth\\' . $providerClass;
    }

    /**
     * Returns provider`s object initialized with its config params.
     *
     * @param $providerName
     * @return AbstractOAuth
     * @throws \Exception
     */
    public function getProvider($providerName)
    {
        $providerClass = $this->getProviderClassByName($providerName);
        $providerConfig = $this->getProviderConfig($providerName);

        /** @var AbstractOAuth $provider */
        $provider = new $providerClass($providerConfig);

        return $provider;
    }

    /**
     * @param $params
     * @param $response
     */
    protected function logResponse($params, $response)
    {
        Db::query(
            'INSERT INTO social_requests SET ' .
            'url = "' . Db::escape($params['url']) . '", ' .
            'post = "' . (!empty($params['post']) ? Db::escape(json_encode($params['post'])) : null) .
            '", ' .
            'response = "' . Db::escape($response) . '" '
        );
    }

    /**
     * @param $params
     * @return array|mixed
     * @throws \Exception
     */
    protected function makeRequest($params)
    {
        $ch = curl_init();

        if (!empty($params['get'])) {
            foreach ($params['get'] as $k => $v) {
                $params['get'][$k] = $k . '=' . urlencode($v);
            }

            $params['url'] = $params['url'] . '?' . implode('&', $params['get']);
        }

        $social_ka_proxy = _cfg('social_ka_proxy');

        if ($social_ka_proxy) {
            $params['url'] = preg_replace('/^https?:\\//', $social_ka_proxy, $params['url']);
        }

        $curlOptions = array(
            CURLOPT_URL => $params['url'],
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => empty($params['headers']) ? [] : $params['headers'],
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => true,
        );

        if (isset($params['post'])) {
            if (!isset($params['disable_post'])) $curlOptions[CURLOPT_POST] = 1;
            $curlOptions[CURLOPT_POSTFIELDS] = $params['post'];
        }

        curl_setopt_array($ch, $curlOptions);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch);
        curl_close($ch);

        $this->logResponse($params, $response);

        if ($status['http_code'] != 200) {
            throw new \Exception('Request failed with HTTP/' . $status['http_code'],
                $status['http_code']);
        }

        if ($response === false) {
            throw new \Exception('Empty response from provider', 500);
        }

        return $response;
    }

    /**
     * Returns authentication url for particular provider.
     *
     * @param $providerName
     * @return string
     */
    public function getAuthUrl($providerName)
    {
        $provider = $this->getProvider($providerName);

        return $provider->getAuthUrl();
    }

    /**
     * @param $providerName
     * @param $request
     * @param $query
     * @return array|mixed
     * @throws \Exception
     */
    public function verifyOAuthCodeFromProvider($providerName, $request, $query)
    {
        $provider = $this->getProvider($providerName);
        $params = $provider->getCodeVerificationRequestParams($request, $query);
        $accessToken = $this->makeRequest($params);
        return $accessToken;
    }

    public function getBaseUrl($providerName, $request, $query)
    {
        $provider = $this->getProvider($providerName);
        return $provider->getBaseUrl($request, $query);
    }

    /**
     * @param $providerName
     * @param $accessToken
     * @return array|mixed
     * @throws \Exception
     */
    public function getUserInfo($providerName, $accessToken)
    {
        $provider = $this->getProvider($providerName);
        $params = $provider->getUserInfoRequestParams($accessToken);
        $response = $this->makeRequest($params);
        $userInfo = $provider->parseUserInfoResponse($response, $accessToken);

        return $userInfo;
    }

    /**
     * @param $userInfo
     * @return bool
     * @throws \Exception
     */
    public function doSocialLoginForUser($userInfo)
    {
        $result = User::loginWithSocialAccount($userInfo);

        if (!empty($result['error'])) {
            throw new \Exception($result['error']);
        }

        return $result;
    }

    public function completeRegistration($userInfo)
    {
        $user = new User();

        return $user->completeSocialRegistration($userInfo);
    }

    public function linkSocialAccount($provider, $email)
    {
        if (!$_SESSION['social']) {
            throw new ApiException(_('social_account_expired'), 400);
        }

        $social = $_SESSION['social'];

        if ($social['social'] === $provider) {
            $socialUid = $social['social_uid'];

            $code = sha1($email . '/' . $provider . '/' . $socialUid . '/' . microtime());

            Db::query(
                'INSERT INTO `social_connect` SET ' .
                '`email` = "' . Db::escape($email) . '", ' .
                '`code` = "' . $code . '", ' .
                '`social` = "' . $provider . '", ' .
                '`social_uid` = ' . $socialUid . ' '
            );

            $templateName = 'connect-social';
            $templateContext = [
                'code' => $code,
                'url' => _cfg('site') . '/run/social-code',
                'site_url' => _cfg('site')
            ];
            
            $template = new Template();
            $msg = $template->getMailTemplate($templateName, $templateContext);

            $msgReplaceKeys = [];
            $msgReplaceVals = [];
            foreach($templateContext as $k => $v) {
                $msgReplaceKeys[] = '%'.$k.'%';
                $msgReplaceVals[] = $v;
            }

            $msg = str_replace( $msgReplaceKeys, $msgReplaceVals, $msg );
            if (!$msg || !($msgResult = Email::send($email, _('connect_social_account_email_theme'), $msg)) ) {
                throw new ApiException('Can\'t send email', 400);
            }

            return true;
        }

        throw new ApiException('Social provider data not found', 400);
    }

    public function disconnectSocialAccount($provider)
    {
        $user = new User();
        $user = $user->checkUser();

        if (!$user->id) {
            return _('not_logged_in');
        }

        $row = Db::fetchRow('SELECT COUNT(`id`) AS `count` FROM `social` WHERE `user_id` = ' . (int)$user->id);

        if ($row->count <= 1) {
            $err = 0;
            //User trying to delete last socials, checking if mail/pass is set
            $msg = _('trying_delete_last_social');

            $row = Db::fetchRow('SELECT `password`, `email` FROM `users` WHERE `id` = ' . (int)$user->id);
            if (!trim($row->email)) { //if email is not set, it must, because it's a login itself
                $err = 1;
                $msg .= '<br />' . _('please_set_email');
            }

            if (substr($row->password, 0,
                    6) == 'social'
            ) { //if password is not set, it must, because it's required to login
                $err = 1;
                $msg .= '<br />' . _('please_set_password');
            }

            if ($err == 1) {
                return $msg;
            }
        }

        Db::query(
            'DELETE FROM `social` WHERE ' .
            '`social` = "' . Db::escape($provider) . '" AND ' .
            '`user_id` = ' . (int)$user->id
        );
        Db::query(
            'UPDATE `users_data` SET ' .
            '`social_' . Db::escape($provider) . '` = 0 WHERE' .
            '`user_id` = ' . (int)$user->id
        );

        return true;
    }

    /**
     * @param int $userId
     * @return array
     */
    public function getConnectedProviderList($userId)
    {
        $connected_provider_list = [];
        $result = Db::fetchRows('SELECT social FROM social WHERE user_id = ' . $userId);

        if (!empty($result)) {
            foreach ($result as $row) {
                $connected_provider_list[] = $row->social;
            }
        }

        return $connected_provider_list;
    }
    
    /**
     * 
     * @param array|mixed $info
     * @return boolean status
     */
    public function setUserSocialInfo($user, $data)
    {
   	   	$status = false;

   	   	if (!is_object($user)) {
   	   	   	return false;
   	   	}

   	   	//Registering in social table
   	   	$status = Db::query(
   	   	   	'INSERT INTO `social` SET ' .
   	   	   	   	'`social` = "' . Db::escape($data['social']) . '", ' .
   	   	   	   	'`social_uid` = ' . Db::escape($data['social_uid']) . ', ' .
   	   	   	   	'`user_id` = ' . (int)$user->id . ' '
   	   	);

   	   	//Adding to user that this social is now available
   	   	$status = $status && Db::query(
   	   	   	'UPDATE `users_data` SET ' .
   	   	   	   	'`social_' . Db::escape($data['social']) . '` = 1 ' .
   	   	   	   	'WHERE `user_id` = ' . (int)$user->id
   	   	);

   	   	return $status;
   	}
}
