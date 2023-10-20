<?php
namespace eGamings\WLC\Storage;

use \Firebase\JWT\JWT;


/**
 * Class CookieStorage
 *
 * Storage data in the cookie
 *
 * @package eGamings\WLC\Storage
 */
class CookieStorage implements IStorage
{
    /**
     * @var CookieStorage|null
     */
    protected static $_instance = null;
    /**
     * @var string
     */
    private $key = '';
    /**
     * @var string
     */
    private $alg = 'HS256';
    /**
     * @var int
     */
    private $defaultExpired = 60 * 60  * 24;
    /**
     * @var null|mixed
     */
    private $currentJwt = null;
    /**
     * @var string
     */
    private $cookieKey = 'jwtstorage';

    /**
     * CookieStorage constructor.
     */
    private function __construct()
    {
        $this->key = _cfg('wlc_secret');
        $this->cookieKey = _cfg('cs_cookie_name') ?: $this->cookieKey;
    }

    /**
     * @return CookieStorage
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * @param string|null $key storage key
     * @return mixed|null
     */
    public function get($key = '')
    {
        $jwt = $this->getCurrentJwt();

        if (!empty($jwt)) {
            $storage = json_decode(json_encode($jwt['storage']), true);

            if ($key) {
                if (array_key_exists($key, $storage) && $storage[$key]['expired'] > time()) {
                    return $storage[$key]['value'];
                } else {
                    $this->remove($key);
                }
            } else {
                return $storage;
            }
        }

        return null;
    }

    /**
     * @param string $key storage key
     * @param mixed $value
     * @param int $expired cookie/jwt expired
     * @return bool
     */
    public function set($key, $value, $expired = 0)
    {
        $iat = $now = time();
        $exp = $expired = $now + ($expired > 0 ? $expired : $this->defaultExpired);
        $storage = $this->get() ?: [];

        if ($value === null) {
            unset($storage[$key]);

            if (count($storage) == 0) {
                $this->removeCookie($this->cookieKey);
                $this->currentJwt = null;

                return true;
            }
        }
        else {
            $storage[$key] = [
                'value' => $value,
                'expired' => $expired
            ];
        }

        if (!empty($storage)) {
            foreach ($storage as $item) {
                if ($item['expired'] > $exp) {
                    $exp = $item['expired'];
                }
            }
        }

        $token = [
            'iat' => $iat,
            'exp' => $exp,
            'storage' => $storage
        ];

        $jwt = JWT::encode($token, $this->key, $this->alg);

        $this->setCookie($this->cookieKey, $jwt, $exp);

        $this->currentJwt = $token;

        return true;
    }

    /**
     * @param string $key cookie key
     * @return bool
     */
    public function has($key)
    {
        $jwt = $this->get($key);

        if ($jwt === null) {
            return false;
        }

        return true;
    }

    /**
     * @param string $key cookie key
     * @return void
     */
    public function remove($key)
    {
        $this->set($key, null);
    }

    /**
     * @return array|null
     */
    private function getCurrentJwt() {
        if ($this->currentJwt !== null) {
            return $this->currentJwt;
        }

        $jwt = null;
        $cookie = $this->getCookie($this->cookieKey);

        if (!empty($cookie)) {
            try {
                $jwt = (array)JWT::decode($cookie, $this->key, [$this->alg]);
            } catch (\Exception $e) {
            }
        }

        $this->currentJwt = $jwt;

        return $jwt;
    }

    /**
     * @param string $key string cookie key
     * @param mixed $value cookie value
     * @param integer $expired cookie expire
     */
    private function setCookie($key, $value, $expired) {
        setcookie($key, $value, $expired, '/');
        $_COOKIE[$key] = $value;
    }

    /**
     * @param string $key cookie key
     * @return mixed
     */
    private function getCookie($key) {
    	return !empty($_COOKIE[$key]) ? $_COOKIE[$key] : null;
    }

    /**
     * @param string $key cookie key
     */
    private function removeCookie($key) {
        setcookie($key, "", time() - 3600, '/');
        unset($_COOKIE[$key]);
    }
}