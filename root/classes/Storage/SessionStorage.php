<?php
namespace eGamings\WLC\Storage;


/**
 * Class SessionStorage
 * @package eGamings\WLC
 */
class SessionStorage implements IStorage
{
    /**
     * @var SessionStorage|null
     */
    protected static $_instance;
    /**
     * @var bool
     */
    private $useCookie = false;
    /**
     * @var string
     */
    private $sessionName = '';

    /**
     * SessionStorage constructor.
     */
    private function __construct()
    {
        $this->sessionName = ini_get('session.name');
        $this->useCookie = ini_get('session.use_cookies');
    }

    /**
     * @return SessionStorage
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * @return bool
     */
    public function startSession()
    {
        switch (session_status()) {
            case PHP_SESSION_DISABLED:
                return false;

            case PHP_SESSION_NONE:
                if ($this->useCookie && !empty($_COOKIE[$this->sessionName])) {
                    session_id($_COOKIE[$this->sessionName]);
                    @session_start();
                } else {
                    @session_start();
                }

                if (empty($_SESSION)) {
                    $_SESSION = [];
                }
        }

        return true;
    }

    public function destroySession() {
        session_destroy();
    }

    /**
     * @param string $key
     * @param string|array $value
     * @return bool
     */
    public function set($key, $value)
    {
        if (!$this->startSession()) {
            return false;
        }

        $key = explode('.', $key);

        $cnt = count($key);

        if ($cnt > 1) {
            $_SESSION_ = &$_SESSION;

            for ($i = 0; $i <= $cnt - 2; $i++) {
                $k = $key[$i];

                if (!array_key_exists($k, $_SESSION_) || !is_array($_SESSION_[$k])) {
                    $_SESSION_[$k] = [];
                }

                $_SESSION_ = &$_SESSION_[$k];
            }

            $_SESSION_[$k][$cnt - 1] = $value;
        }
        else {
            $_SESSION[$key[0]] = $value;
        }

        return true;
    }

    /**
     * @param string $key
     * @return string|array|null
     */
    public function get($key)
    {
        if (!$this->startSession()) {
            return null;
        }

        return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : null;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function remove($key)
    {
        if (!$this->startSession()) {
            return false;
        }

        unset($_SESSION[$key]);

        return true;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        if (!$this->startSession()) {
            return false;
        }

        return array_key_exists($key, $_SESSION);
    }
}
