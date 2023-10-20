<?php

namespace eGamings\WLC;


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

class Social extends System
{
    private $config;
    private static $available_networks = [
        'ok' => 'OK.RU',
        'fb' => 'Facebook',
        'ya' => 'Yandex',
        'ml' => 'Mail.Ru',
        'tw' => 'Twitter',
        'gp' => 'Google+',
        'vk' => 'Vkontakte',
        'in' => 'Instagram'
    ];

    function __construct()
    {
        global $cfg;

        if (!empty($_GET['language'])) {
            $cfg['language'] = $_GET['language'];
        }

        $this->config = _cfg('social');
    }
    
    public static function getNetworks() {
    	return self::$available_networks;
    }

    public function Verify($provider = '')
    {
        if ($provider == '') {
            $provider = explode('/', Router::getRoute());
            $provider = array_pop($provider);
        }

        if (!isset($this->config[$provider])) {
            die('no social config: ' . $provider);
        }

        if (isset($_GET['state'])) {
            $tracking = json_decode(self::base64UrlDecode($_GET['state']), $assoc = true);
            Affiliate::identifyAffiliate($tracking);
            Affiliate::saveTracking($tracking);
        }

        $this->config = $this->config[$provider];

        if (isset($_SESSION['social'][$provider])) {
            return $this->{$provider . 'Complete'}();
        } else {
            if (method_exists($this, $provider . 'Verify')) {
                return $this->{$provider . 'Verify'}();
            }
        }
    }

    //TODO: remove this, left for compatibility with old WLCs.
    /**
     * @deprecated
     * @param $provider
     * @return mixed
     * @throws \Exception
     */
    public function getToken($provider)
    {
        if (!method_exists($this, $provider)) {
            throw new \Exception('Unsupported provider: ' . $provider);
        }

        if (!isset($this->config[$provider])) {
            throw new \Exception('No social config: ' . $provider);
        }

        $config = $this->config[$provider];

        if (empty($config['redirect_uri'])) {
            $config['redirect_uri'] = _cfg('site') . '/' . _cfg('language') . '/social/login/' . $provider;
        }

        return $this->$provider($config);
    }

    /**
     * @param $provider
     * @return mixed
     * @throws \Exception
     */
    public function getAuthUrl($provider)
    {
        if (!method_exists($this, $provider)) {
            throw new \Exception('Unsupported provider: ' . $provider);
        }

        if (!isset($this->config[$provider])) {
            throw new \Exception('No social config: ' . $provider);
        }

        $config = $this->config[$provider];

        if (empty($config['redirect_uri'])) {
            $config['redirect_uri'] = _cfg('site') . '/api/v1/auth/social/oauth_cb/' . $provider;
        }

        return $this->$provider($config);
    }

    private function oAuthRequest($cfg)
    {
        $ch = curl_init();

        if (_cfg('env') != 'dev') {
            $cfg['url'] = preg_replace('/^http(s)?:/', KEEPALIVE_PROXY, $cfg['url']);
        }

        if (isset($cfg['get'])) {
            foreach ($cfg['get'] as $k => $v) {
                $cfg['get'][$k] = $k . '=' . urlencode($v);
            }

            $cfg['url'] = $cfg['url'] . '?' . implode('&', $cfg['get']);
        }

        $curlOptions = array(
            CURLOPT_URL => $cfg['url'],
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => isset($cfg['headers']) ? $cfg['headers'] : array(),
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => true,
        );

        if (isset($cfg['post'])) {
            $curlOptions[CURLOPT_POST] = 1;
            $curlOptions[CURLOPT_POSTFIELDS] = $cfg['post'];
        }

        curl_setopt_array($ch, $curlOptions);
        $response = curl_exec($ch); // run the whole process
        $status = curl_getinfo($ch);
        curl_close($ch);


        Db::query(
            'INSERT INTO `social_requests` SET ' .
            '`url` = "' . Db::escape($cfg['url']) . '", ' .
            '`post` = "' . (!empty($cfg['post']) ? Db::escape(json_encode($cfg['post'])) : null) .
            '", ' .
            '`response` = "' . Db::escape($response) . '" '
        );


        if ($status['http_code'] != 200) {
            if (_cfg('env') == 'dev') {
                print_r($cfg);
                echo $response;
                print_r($status);
            }

            return false;
        }

        return $response;
    }

    function twVerify()
    {
        $params = array(
            'url' => 'https://api.twitter.com/oauth/access_token',
            'callback' => urlencode(_cfg('site') . '/' . _cfg('language') . '/social/login/twitter'),
            'id' => $this->config['id'],
            'secret' => $this->config['private'],
            'token' => $_GET['oauth_token'],
            'verifier' => $_GET['oauth_verifier'],
            'nonce' => md5(uniqid(rand(), true)),
            'time' => time(),
        );

        // oauth_token_secret получаем из сессии, которую зарегистрировали
        // во время запроса request_token
        $oauth_token_secret = $_SESSION['social']['twitter']['oauth_token_secret'];

        $oauth_base_text = "GET&"
            . urlencode($params['url']) . "&"
            . urlencode("oauth_consumer_key=" . $params['id'] . "&")
            . urlencode("oauth_nonce=" . $params['nonce'] . "&")
            . urlencode("oauth_signature_method=HMAC-SHA1&")
            . urlencode("oauth_token=" . $params['token'] . "&")
            . urlencode("oauth_timestamp=" . $params['time'] . "&")
            . urlencode("oauth_verifier=" . $params['verifier'] . "&")
            . urlencode("oauth_version=1.0");

        $key = $params['secret'] . "&" . $_SESSION['social']['twitter']['oauth_token_secret'];
        $oauth_signature = base64_encode(hash_hmac("sha1", $oauth_base_text, $key, true));

        $cfg = array(
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

        $f = $this->oAuthRequest($cfg);
        parse_str($f, $f);

        $oauth_nonce = md5(uniqid(rand(), true));

        // время когда будет выполняться запрос (в секундых)
        $oauth_timestamp = time();

        $oauth_token = $f['oauth_token'];
        $oauth_token_secret = $f['oauth_token_secret'];
        $screen_name = $f['screen_name'];

        $oauth_base_text = "GET&"
            . urlencode('https://api.twitter.com/1.1/users/show.json') . '&'
            . urlencode('oauth_consumer_key=' . $params['id'] . '&')
            . urlencode('oauth_nonce=' . $oauth_nonce . '&')
            . urlencode('oauth_signature_method=HMAC-SHA1&')
            . urlencode('oauth_timestamp=' . $oauth_timestamp . "&")
            . urlencode('oauth_token=' . $oauth_token . "&")
            . urlencode('oauth_version=1.0&')
            . urlencode('screen_name=' . $screen_name);

        $key = $params['secret'] . '&' . $oauth_token_secret;
        $signature = base64_encode(hash_hmac("sha1", $oauth_base_text, $key, true));

        // Формируем GET-запрос
        $cfg = array(
            'url' => 'https://api.twitter.com/1.1/users/show.json',
            'get' => array(
                'oauth_consumer_key' => $params['id'],
                'oauth_nonce' => $oauth_nonce,
                'oauth_signature' => $signature,
                'oauth_signature_method' => 'HMAC-SHA1',
                'oauth_timestamp' => $oauth_timestamp,
                'oauth_token' => $oauth_token,
                'oauth_version' => '1.0',
                'screen_name' => $screen_name
            )
        );
        $f = $this->oAuthRequest($cfg);
        $f = json_decode($f, 1);

        if (!isset($f['id'])) {
            return array('error' => 'auth error (' . __LINE__ . ')');
        }

        $_SESSION['social']['tw'] = $f;

        return $this->twComplete($f);
    }

    private function twComplete($data = array())
    {
    	$u = new User();
        $user = $_POST;
        $user['password'] = $u->generatePassword();
        $user['social'] = 'tw';

        if (empty($data)) {
            if (!isset($_SESSION['social']) || !isset($_SESSION['social']['tw'])) {
                return array('error' => 'auth error (' . __LINE__ . ')');
            }

            $data = $_SESSION['social']['tw'];
        }

        $name_parts = explode(' ', $data['name'], 1);

        $user['firstName'] = $name_parts[0];
        $user['lastName'] = !empty($name_parts[1]) ? $name_parts[1] : '';
        $user['photo'] = $data['profile_image_url'];
        $user['social_uid'] = $data['id'];

        $user = User::socialLogin($user);
        if ($user && isset($user['error'])) {
            return $user;
        }

        header('Location: ' . _cfg('site') . '/' . _cfg('language'));
        die();
    }

    private function tw($config)
    {
        if (isset($_SESSION['social']['tw'])) {
            unset($_SESSION['social']['tw']);
        }

        $params = array(
            'url' => 'https://api.twitter.com/oauth/request_token',
            'ka_url' => (defined('KEEPALIVE_PROXY') ? KEEPALIVE_PROXY : 'https:/') . '/api.twitter.com/oauth/request_token',
            'callback' => urlencode($config['redirect_uri']),
            'id' => $config['id'],
            'secret' => $config['private'],
            'nonce' => md5(uniqid(rand(), true)),
            'time' => time(),
        );

        // ПОРЯДОК ПАРАМЕТРОВ ДОЛЖЕН БЫТЬ ИМЕННО ТАКОЙ!
        // Т.е. сперва oauth_callback -> oauth_consumer_key -> ... -> oauth_version.
        $oauth_base_text = "GET&"
            . urlencode($params['url']) . "&"
            . urlencode("oauth_callback=" . $params['callback'] . "&")
            . urlencode("oauth_consumer_key=" . $params['id'] . "&")
            . urlencode("oauth_nonce=" . $params['nonce'] . "&")
            . urlencode("oauth_signature_method=HMAC-SHA1&")
            . urlencode("oauth_timestamp=" . $params['time'] . "&")
            . urlencode("oauth_version=1.0");

        // Формируем ключ
        // На конце строки-ключа должен быть амперсанд & !!!
        $key = $params['secret'] . "&";

        // Формируем oauth_signature
        $signature = base64_encode(hash_hmac("sha1", $oauth_base_text, $key, true));

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
        $response = file_get_contents($url);
        parse_str($response, $response);

        $_SESSION['social']['twitter']['oauth_token_secret'] = $response['oauth_token_secret'];

        return 'https://api.twitter.com/oauth/authorize?oauth_token=' . $response['oauth_token'];
    }

    private function vkVerify()
    {
        if (!isset($_GET['code']) || empty($_GET['code'])) {
            if (isset($_GET['error']) && !empty($_GET['error'])) {
                return array('error' => $_GET['error']);
            }

            return array('error' => 'VK Authentication error (' . __LINE__ . ')');
        }

        $cfg = array(
            'url' => 'https://oauth.vk.com/access_token',
            'get' => array(
                'client_id' => $this->config['id'],
                'client_secret' => $this->config['private'],
                'code' => $_GET['code'],
                'redirect_uri' => _cfg('site') . '/' . _cfg('language') . '/social/login/vk',
            ),
        );

        $f = $this->oAuthRequest($cfg);

        if ($f === false) {
            return array('error' => 'VK Authentication error, no answer (' . __LINE__ . ')');
        }

        $f = json_decode($f, 1);

        if (!isset($f['user_id']) || !isset($f['access_token'])) {
            return array('error' => 'VK Authentication error, user not found (' . __LINE__ . ')');
        }

        $email = '';

        if ($f['email']) {
            $email = $f['email'];
        }

        $cfg = array(
            'url' => 'https://api.vk.com/method/users.get',
            'get' => array(
                'uid' => $f['user_id'],
                'access_token' => $f['access_token'],
                'fields' => 'email,photo_200',
            ),
        );

        $f = $this->oAuthRequest($cfg);

        if ($f === false) {
            return array('error' => 'VK Authentication error, user not found (' . __LINE__ . ')');
        }

        $f = json_decode($f, 1);

        if (!isset($f['response']) || !isset($f['response'][0]) || !isset($f['response'][0]['uid'])) {
            return array('error' => 'VK Authentication error, no response (' . __LINE__ . ')');
        }

        $f = $f['response'][0];

        if ($email) {
            $f['email'] = $email;
        }

        $_SESSION['social']['vk'] = $f;

        return $this->vkComplete($f);
    }

    private function vkComplete($data = array())
    {
    	$u = new User();
        $user = $_POST;
        $user['password'] = $u->generatePassword();
        $user['social'] = 'vk';

        if (empty($data)) {
            if (!isset($_SESSION['social']) || !isset($_SESSION['social']['vk'])) {
                return array('error' => 'VK Authentication error, session died, please try again (' . __LINE__ . ')');
            }

            $data = $_SESSION['social']['vk'];
        }

        $user['firstName'] = $data['first_name'];
        $user['lastName'] = $data['last_name'];

        if (!empty($data['email'])) {
            $user['email'] = $data['email'];
        }

        $user['social_uid'] = $data['uid'];
        $user['photo'] = $data['photo_200'];
        $user = User::socialLogin($user);

        if ($user && !empty($user['error'])) {
            return $user;
        }

        header('Location: ' . _cfg('site') . '/' . _cfg('language'));
        die();
    }

    private function vk($config)
    {
        if (isset($_SESSION['social']['vk'])) {
            unset($_SESSION['social']['vk']);
        }

        $url = 'http://oauth.vk.com/authorize'
            . '?client_id=' . $config['id']
            . '&redirect_uri=' . $config['redirect_uri']
            . '&scope=email'
            . '&response_type=code';

        return $url;
    }

    private function okVerify()
    {

        if (!isset($_GET['code']) || empty($_GET['code'])) {
            if (isset($_GET['error']) && !empty($_GET['error'])) {
                $err = $_GET['error'];
            } else {
                $err = 'Auth error';
            }

            return array('error' => $err . ' (' . __LINE__ . ')');
        }

        $cfg = array(
            'url' => 'http://api.odnoklassniki.ru/oauth/token.do',
            'post' => array(),
            'get' => array(
                'code' => $_GET['code'],
                'redirect_uri' => _cfg('site') . '/' . _cfg('language') . '/social/login/ok',
                'grant_type' => 'authorization_code',
                'client_id' => $this->config['id'],
                'client_secret' => $this->config['private'],
            ),
        );

        $f = $this->oAuthRequest($cfg);

        if ($f === false) {
            return array('error' => 'auth error (' . __LINE__ . ')');
        }

        $f = json_decode($f, 1);

        if (!isset($f['access_token'])) {
            return array('error' => 'auth error (' . __LINE__ . ')');
        }

        $sign = md5('application_key=' . $this->config['public'] . 'method=users.getCurrentUser' . md5($f['access_token'] . $this->config['private']));
        $cfg = array(
            //?&access_token={access_token}&application_key={public_key}&sig={sign}
            'url' => 'http://api.odnoklassniki.ru/fb.do',
            'post' => array(),
            'get' => array(
                'method' => 'users.getCurrentUser',
                'application_key' => $this->config['public'],
                'access_token' => $f['access_token'],
                'sig' => $sign
            ),
        );

        $f = $this->oAuthRequest($cfg);

        if ($f === false) {
            return array('error' => 'auth error (' . __LINE__ . ')');
        }

        $f = json_decode($f, 1);

        if (!isset($f['uid'])) {
            return array('error' => 'auth error (' . __LINE__ . ')');
        }

        $_SESSION['social']['ok'] = $f;

        return $this->okComplete($f);
    }

    private function okComplete($data = array())
    {
    	$u = new User();
        $user = $_POST;
        $user['password'] = $u->generatePassword();
        $user['social'] = 'ok';

        if (empty($data)) {
            if (!isset($_SESSION['social']) || !isset($_SESSION['social']['ok'])) {
                return array('error' => 'auth error (' . __LINE__ . ')');
            }
            $data = $_SESSION['social']['ok'];
        }

        $user['firstName'] = $data['first_name'];
        $user['lastName'] = $data['last_name'];
        $user['social_uid'] = $data['uid'];
        $user = User::socialLogin($user);
        if ($user && isset($user['error'])) {
            return $user;
        }

        header('Location: ' . _cfg('site') . '/' . _cfg('language'));
        die();
    }

    private function ok($config)
    {
        if (isset($_SESSION['social']['ok'])) {
            unset($_SESSION['social']['ok']);
        }

        $url = 'http://www.odnoklassniki.ru/oauth/authorize'
            . '?client_id=' . $config['id']
            . '&redirect_uri=' . $config['redirect_uri']
            . '&response_type=code';

        return $url;
    }

    private function fbVerify()
    {
        if (!isset($_GET['code']) || empty($_GET['code'])) {
            if (isset($_GET['error']) && !empty($_GET['error'])) {
                return array('error' => $_GET['error']);
            }

            return array('error' => 'Facebook Authentication error (' . __LINE__ . ')');
        }

        $cfg = array(
            'url' => 'https://graph.facebook.com/oauth/access_token',
            'get' => array(
                'code' => $_GET['code'],
                'redirect_uri' => _cfg('site') . '/' . _cfg('language') . '/social/login/fb',
                'client_id' => $this->config['id'],
                'client_secret' => $this->config['private'],
                //'grant_type'=>'client_credentials'
            ),
        );

        $f = $this->oAuthRequest($cfg);

        if ($f === false) {
            return array('error' => 'Facebook Authentication error, no answer (' . __LINE__ . ')');
        }

        parse_str($f, $f);

        if (!isset($f['access_token'])) {
            return array('error' => 'Facebook Authentication error, user not found (' . __LINE__ . ')');
        }

        $cfg = array(
            'url' => 'https://graph.facebook.com/me',
            'get' => array(
                'access_token' => $f['access_token'],
            ),
        );

        $f = $this->oAuthRequest($cfg);

        if ($f === false) {
            return array('error' => 'Facebook Authentication error, no answer (' . __LINE__ . ')');
        }

        $f = json_decode($f, 1);

        if (!isset($f['id'])) {
            return array('error' => 'Facebook Authentication error, no ID of user found (' . __LINE__ . ')');
        }

        $_SESSION['social']['fb'] = $f;

        return $this->fbComplete($f);
    }

    private function fbComplete($data = array())
    {
    	$u = new User();
        $user = $_POST;
        $user['password'] = $u->generatePassword();
        $user['social'] = 'fb';

        if (empty($data)) {
            if (!isset($_SESSION['social']) || !isset($_SESSION['social']['fb'])) {
                return array('error' => 'Facebook authentication error (' . __LINE__ . ')');
            }

            $data = $_SESSION['social']['fb'];
        }

        $user['firstName'] = $data['first_name'];
        $user['lastName'] = $data['last_name'];

        if (isset($data['email'])) {
            $user['email'] = $data['email'];
        }

        $user['social_uid'] = $data['id'];
        $user['photo'] = 'https://graph.facebook.com/' . $user['social_uid'] . '/picture?type=large';

        $user = User::socialLogin($user);
        if ($user && isset($user['error'])) {
            return $user;
        }

        header('Location: ' . _cfg('site') . '/' . _cfg('language'));
        die();
    }

    private function fb($config)
    {
        if (isset($_SESSION['social']['fb'])) {
            unset($_SESSION['social']['fb']);
        }

        $url = 'https://www.facebook.com/dialog/oauth'
            . '?client_id=' . $config['id']
            . '&redirect_uri=' . $config['redirect_uri']
            . '&scope=email,public_profile'
            . '&response_type=code';

        return $url;
    }

    private function yaVerify()
    {
        if(!empty($_GET['state'])){
            header('Location: ' . $_GET['state'] . str_replace('state','redirected',$_SERVER['REQUEST_URI']));
            die();
        }

        if (!isset($_GET['code']) || empty($_GET['code'])) {
            if (isset($_GET['error']) && !empty($_GET['error'])) {
                $err = $_GET['error'];
            } else {
                $err = 'Auth error';
            }

            return $err;
        }

        $cfg = array(
            'url' => 'https://oauth.yandex.ru/token',
            'post' => array(
                'code' => $_GET['code'],
                'redirect_uri' => _cfg('site') . '/' . _cfg('language') . '/social/login/ya',
                'grant_type' => 'authorization_code',
                'client_id' => $this->config['id'],
                'client_secret' => $this->config['private'],
            ),
        );

        $f = $this->oAuthRequest($cfg);

        if ($f === false) {
            return array('error' => 'auth error (' . __LINE__ . ')');
        }

        $f = json_decode($f, 1);

        if (!isset($f['access_token'])) {
            return array('error' => 'auth error (' . __LINE__ . ')');
        }

        $cfg = array(
            'url' => 'https://login.yandex.ru/info',
            'headers' => array('Authorization: OAuth ' . $f['access_token']),
            'get' => array(
                'format' => 'json',
                'access_token' => $f['access_token'],
            ),
        );

        $f = $this->oAuthRequest($cfg);

        if ($f === false) {
            return array('error' => 'auth error (' . __LINE__ . ')');
        }

        $f = json_decode($f, 1);

        if (!isset($f['id'])) {
            return array('error' => 'auth error (' . __LINE__ . ')');
        }

        $_SESSION['social']['ya'] = $f;

        return $this->yaComplete($f);
    }

    private function yaComplete($data = array())
    {
    	$u = new User();
        $user = $_POST;
        $user['password'] = $u->generatePassword();
        $user['social'] = 'ya';

        if (empty($data)) {
            if (!isset($_SESSION['social']) || !isset($_SESSION['social']['ya'])) {
                return array('error' => 'auth error (' . __LINE__ . ')');
            }
            $data = $_SESSION['social']['ya'];
        }

        $user['firstName'] = $data['first_name'];
        $user['lastName'] = $data['last_name'];
        $user['social_uid'] = $data['id'];

        if (isset($data['default_email'])) {
            $user['email'] = $data['default_email'];
            $user['i_agree'] = 1;
        }

        $user = User::socialLogin($user);
        if ($user && isset($user['error'])) {
            return $user;
        }

        header('Location: ' . _cfg('site') . '/' . _cfg('language'));
        die();
    }

    private function ya($config)
    {
        if (isset($_SESSION['social']['ya'])) {
            unset($_SESSION['social']['ya']);
        }

        $url = 'https://oauth.yandex.ru/authorize'
            . '?client_id=' . $config['id']
            . '&state=' . urlencode(_cfg('site'))
            . '&response_type=code';

        return $url;
    }

    private function mlVerify()
    {

        if (!isset($_GET['code']) || empty($_GET['code'])) {
            if (isset($_GET['error']) && !empty($_GET['error'])) {
                $err = $_GET['error'];
            } else {
                $err = 'Auth error';
            }

            return $err;
        }

        $cfg = array(
            'url' => 'https://connect.mail.ru/oauth/token',
            'post' => array(
                'code' => $_GET['code'],
                'redirect_uri' => _cfg('site') . '/' . _cfg('language') . '/social/login/ml',
                'grant_type' => 'authorization_code',
                'client_id' => $this->config['id'],
                'client_secret' => $this->config['private'],
            ),
        );

        $f = $this->oAuthRequest($cfg);
        if ($f === false) {
            return array('error' => 'auth error (' . __LINE__ . ')');
        }

        $f = json_decode($f, 1);

        if (!isset($f['access_token'])) {
            return array('error' => 'auth error (' . __LINE__ . ')');
        }
        /*method=users.getInfo&
        app_id=423004&session_key=be6ef89965d58e56dec21acb9b62bdaa&
        sig=f82efdd230e45e58e4fa327fdf92135d&
        uids=15410773191172635989,11425330190814458227*/

        $sign = md5('app_id=' . $this->config['id'] . 'method=users.getInfosecure=1session_key=' . $f['access_token'] . $this->config['private']);
        $cfg = array(
            'url' => 'http://www.appsmail.ru/platform/api',
            //'headers'=>array('Authorization: OAuth '.$f['access_token']),
            'get' => array(
                'app_id' => $this->config['id'],
                'session_key' => $f['access_token'],
                'secure' => '1',
                'method' => 'users.getInfo',
                'sig' => $sign
            ),
        );

        $f = $this->oAuthRequest($cfg);
        if ($f === false) {
            return array('error' => 'auth error (' . __LINE__ . ')');
        }

        $f = json_decode($f, 1);
        if (!isset($f[0]) || !isset($f[0]['uid'])) {
            return array('error' => 'auth error (' . __LINE__ . ')');
        }

        $_SESSION['social']['ml'] = $f[0];

        return $this->mlComplete($f[0]);
    }

    private function mlComplete($data = array())
    {
    	$u = new User();
        $user = $_POST;
        $user['password'] = $u->generatePassword();
        $user['social'] = 'ml';

        if (empty($data)) {
            if (!isset($_SESSION['social']) || !isset($_SESSION['social']['ml'])) {
                return array('error' => 'auth error (' . __LINE__ . ')');
            }
            $data = $_SESSION['social']['ml'];
        }

        $user['firstName'] = $data['first_name'];
        $user['lastName'] = $data['last_name'];
        $user['social_uid'] = $data['uid'];

        if (isset($data['email'])) {
            $user['email'] = $data['email'];
            $user['i_agree'] = 1;
        }

        $user = User::socialLogin($user);
        if ($user && isset($user['error'])) {
            return $user;
        }

        header('Location: ' . _cfg('site') . '/' . _cfg('language'));
        die();
    }

    private function ml($config)
    {
        if (isset($_SESSION['social']['ml'])) {
            unset($_SESSION['social']['ml']);
        }

        $url = 'https://connect.mail.ru/oauth/authorize'
            . '?client_id=' . $config['id']
            . '&response_type=code'
            . '&redirect_uri=' . $config['redirect_uri'];

        return $url;
    }

    private function gpVerify()
    {
        if (!isset($_GET['code']) || empty($_GET['code'])) {
            if (isset($_GET['error']) && !empty($_GET['error'])) {
                $err = $_GET['error'];
            } else {
                $err = 'Auth error';
            }

            return $err;
        }

        $cfg = array(
            'url' => 'https://accounts.google.com/o/oauth2/token',
            'post' => array(
                'code' => $_GET['code'],
                'redirect_uri' => _cfg('site') . '/' . _cfg('language') . '/social/login/gp',
                'grant_type' => 'authorization_code',
                'client_id' => $this->config['id'],
                'client_secret' => $this->config['private'],
            ),
        );

        $f = $this->oAuthRequest($cfg);

        if ($f === false) {
            return array('error' => 'auth error (' . __LINE__ . ')');
        }

        $f = json_decode($f, 1);

        if (!isset($f['access_token'])) {
            return array('error' => 'auth error (' . __LINE__ . ')');
        }

        $cfg = array(
            'url' => 'https://www.googleapis.com/oauth2/v1/userinfo',
            //'headers'=>array('Authorization: OAuth '.$f['access_token']),
            'get' => array(
                'access_token' => $f['access_token'],
            ),
        );

        $f = $this->oAuthRequest($cfg);

        if ($f === false) {
            return array('error' => 'auth error (' . __LINE__ . ')');
        }

        $f = json_decode($f, 1);

        if (!isset($f['id'])) {
            return array('error' => 'auth error (' . __LINE__ . ')');
        }

        $_SESSION['social']['gp'] = $f;

        return $this->gpComplete($f);
    }

    private function gpComplete($data = array())
    {
    	$u = new User();
        $user = $_POST;
        $user['password'] = $u->generatePassword();
        $user['social'] = 'gp';

        if (empty($data)) {
            if (!isset($_SESSION['social']) || !isset($_SESSION['social']['gp'])) {
                return array('error' => 'auth error (' . __LINE__ . ')');
            }
            $data = $_SESSION['social']['gp'];
        }

        $user['firstName'] = $data['given_name'];
        $user['lastName'] = $data['family_name'];
        $user['social_uid'] = $data['id'];
        $user['photo'] = $data['picture'];

        if (isset($data['email'])) {
            $user['email'] = $data['email'];
            $user['i_agree'] = 1;
        }

        $user = User::socialLogin($user);
        if ($user && isset($user['error'])) {
            return $user;
        }

        header('Location: ' . _cfg('site') . '/' . _cfg('language'));
        die();
    }

    private function gp($config)
    {
        if (isset($_SESSION['social']['gp'])) {
            unset($_SESSION['social']['gp']);
        }

        $url = 'https://accounts.google.com/o/oauth2/auth'
            . '?redirect_uri=' . $config['redirect_uri']
            . '&client_id=' . $config['id']
            . '&scope=https://www.googleapis.com/auth/userinfo.email'
            . '&response_type=code';

        return $url;
    }

    public static function getName($network_code)
    {
        $network_name = '';

        if (isset(self::$available_networks[$network_code])) {
            $network_name = self::$available_networks[$network_code];
        }

        return $network_name;
    }

    public static function base64UrlEncode($inputStr)
    {
        return strtr(base64_encode($inputStr), '+/=', '-_,');
    }

    public static function base64UrlDecode($inputStr)
    {
        return base64_decode(strtr($inputStr, '-_,', '+/='));
    }

    public static function jump()
    {
        $route = $_GET['route'];
        $parts = explode('/', $route);

        $provider = end($parts);

        $social = new self();
        $url = $social->getToken($provider);

        header('Location: ' . $url);
        die();
    }

}
