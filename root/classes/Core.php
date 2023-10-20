<?php
namespace eGamings\WLC;

use eGamings\WLC\Cache\RedisCache;
use eGamings\WLC\Cache\ApcCache;
use eGamings\WLC\Config;
use \Firebase\JWT\JWT;

/**
 * Class Core
 *
 * Bootstrap class of Wlc Core
 *
 * @package eGamings\WLC
 */

class Core
{
    /**
     * @var Core|null
     */
    protected static $_instance = null;
    private static $_sessionStarted = false;
    private $cache = null;
    public static $redis = null;
    private static $di_container = null;

    /**
     * Core constructor.
     */
    private function __construct() {}

    /**
     * @return Core
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function initCore(): void {
        Config::load();
        if (!empty($_SERVER['TEST_RUN'])) {
            return;
        }

        $this->initCors();
        $this->checkRedirect();
        $this->sessionStart();
        $this->seo();
        $this->checkAffilates();
        $this->isMailingLink();
        $this->initCache();
        $this->initMetamask();
    }

    /**
     * @return bool
     */
    public function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * @return boolean
     */
    public function initCors() {
        if (!_cfg('enableCors')) {
            return false;
        }

        if (!empty($_SERVER['HTTP_ORIGIN'])) {
            $originInfo = parse_url($_SERVER['HTTP_ORIGIN']);
            if (!empty($originInfo['host'])) {
                $_SERVER['HTTP_HOST'] = $originInfo['host'];
            }

            if (!empty($originInfo['scheme'])) {
                if ($originInfo['scheme'] == 'https') {
                    $_SERVER['HTTPS'] = 'on';
                } else {
                    unset($_SERVER['HTTPS']);
                }
            }
        }
        return true;
    }

    /**
     * @return boolean
     */
    public function initMetamask()
    {
        if (!_cfg('useMetamask')) {
            return false;
        }

        Config::set('registerGeneratePassword', true);
        Config::set('registerSkipLogin', false);
        Config::set('fastRegistration', true);
        Config::set('allowPartialUpdate', 1);

        return true;
    }

    /**
     * @codeCoverageIgnore
     * @return void
     */
    public function sessionStart($force = false, $forceJWT = false)
    {
        if (self::$_sessionStarted == true) {
            return true;
        }

        $request = json_decode(file_get_contents('php://input'), true);

        $sessionName = ini_get('session.name');
        $sessionUseCookie = ini_get('session.use_cookies');
        $sessionUseJWT = (isset($_SERVER['HTTP_AUTHORIZATION']) && strstr($_SERVER['HTTP_AUTHORIZATION'], 'Bearer')) 
            || (isset($request['jwtToken']) && $request['jwtToken'])
            || !empty($_GET['jwtToken'])
            || (isset($request['useJwt']) && $request['useJwt']) || $forceJWT;
        $forceCookie = !empty($_GET['forceCookie']);

        if (_cfg('enableCors') && !empty($_SERVER['HTTP_HOST'])) {
            $hostScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
            $hostUrl = $hostScheme . '://' . $_SERVER['HTTP_HOST'];
            $hostInfo = parse_url($hostUrl);
            $hostArr = explode('.', $hostInfo['host']);
            $cookieDomain = '.' . implode('.', array_slice($hostArr, -2, 2, true));
            ini_set('session.cookie_domain', $cookieDomain);
            ini_set('session.cookie_samesite', 'None');
            ini_set('session.cookie_secure', true);

            if (!ini_get('session.cookie_lifetime')) {
                ini_set('session.cookie_lifetime', 3600);
            }
        }

        if ($sessionUseJWT) {
            $jwtToken = $request['jwtToken'] ?? $_GET['jwtToken'] ?? '';
            $jwt = self::getAccessJwtToken($jwtToken);
            if (isset($jwt['jti'])) {
                session_id($jwt['jti']);
            }

            @session_start();
            self::$_sessionStarted = true;
            PrometheusKeys::getInstance()->BEARER_AUTHENTICATION->store();
            if (!$forceCookie) {
                $cookies = [];
                foreach (headers_list() as $header) {
                    if (stripos($header, 'Set-Cookie:') === 0) {
                        [, $cookieValue] = explode(":", $header, 2);
                        if (strpos($cookieValue, $sessionName) === false) {
                            $cookies[] = $cookieValue;
                        }
                    }
                }
                header_remove('Set-Cookie');
                foreach ($cookies as $cookie) {
                    header("Set-Cookie: {$cookie}");
                }
                unset($_COOKIE[$sessionName]);
                setcookie($sessionName, null, -1, '/');
            }
        } else if ($sessionUseCookie) {
            if (!empty($_COOKIE[$sessionName])) {
                session_id($_COOKIE[$sessionName]);
                @session_start(); //@ running a few times because of _construct, but if it is not there, bug happens
                PrometheusKeys::getInstance()->BASIC_AUTHENTICATION->store();
                self::$_sessionStarted = true;

                //Check for empty user session
                if (empty($_SESSION['user'])) {
                    self::sessionDestroy();
                }
            } else if ($force) {
                @session_start(); //@ running a few times because of _construct, but if it is not there, bug happens
                self::$_sessionStarted = true;
                PrometheusKeys::getInstance()->BASIC_AUTHENTICATION->store();
            }
        } else {
            @session_start(); //@ running a few times because of _construct, but if it is not there, bug happens
            self::$_sessionStarted = true;
            PrometheusKeys::getInstance()->BASIC_AUTHENTICATION->store();
        }

        if (empty($_SESSION)) {
            $_SESSION = [];
        }
    }

    /**
     * @codeCoverageIgnore
     * get jwt token
     *
     * @param null|string $token
     * @return array
     */
    public static function getAccessJwtToken(?string $token = null): array
    {
        $key = 'Jwt_auth_key_' . _cfg('websiteName');

        $jwt = null;
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? $token ?? '';
        $token = str_replace('Bearer ', '', $token);

        if (!empty($token)) {
            try {
                $jwt = (array)JWT::decode($token, $key, ['HS256']);
                if (!empty($jwt['jti'])) {
                    return $jwt;
                }
            } catch (\Exception $e) {
            }
        }

        return [];
    }

    /**
     * @return boolean
     */
    public function sessionStarted() {
        return self::$_sessionStarted;
    }

    /**
     * @return boolean
     */
    public function sessionDestroy() {
        if (!self::$_sessionStarted && empty(session_id())) {
            return false;
        }

        unset($_SESSION['user']);
        unset($_SESSION['social']);
        unset($_SESSION['FundistIDUser']);
        session_destroy();
        setcookie(session_name(), 'session-ended', 1, ini_get('session.cookie_path'), ini_get('session.cookie_domain'));
        self::$_sessionStarted = false;
        return true;
    }

    /**
     * @return void
     */
    private function seo()
    {
        if ($this->isAjax()) {
            return false;
        }

        Seo::checkRedirects();
    }

    /**
     * @return void
     */
    private function checkRedirect()
    {
        if (_cfg('disableRedirect') === true || empty($_GET['RedirectId'])) {
            return;
        }

        $query = 'SELECT * FROM redirects
              WHERE id="' . Db::escape($_GET['RedirectId']) .'"
              AND add_date > NOW() - INTERVAL 1 HOUR';

        $queryResult = Db::fetchRow($query);
        if($queryResult !== false) {
            header('Location: ' . $queryResult->domain . str_replace('RedirectId', 'RedirectedId', $_SERVER['REQUEST_URI']));
            exit();
        }
    }

    /**
     * @return void
     */
    public function checkAffilates()
    {
        if(!empty($_SESSION['user'])) {
            return false;
        }

        $method = !empty($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

        Affiliate::identifyAffiliate($method == 'POST' ? $_POST : $_GET);
    }

    /**
     * @return void
     */
    private function isMailingLink()
    {
        $messagesService = new Messages();
        $dataRequest = (!empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;

        if ($messagesService->isMailingLink($dataRequest) && $messagesService->isCorrectMailParams($dataRequest)) {
            $messagesService->updateMessageOpenLink($dataRequest);
        }
    }

    private function initCache() {
        $middleware = [];

        if (ApcCache::isAvailable()) {
            $middleware[] = new ApcCache([
                'prefix' => defined('APC_PREFIX') ? APC_PREFIX : ''
            ]);
        }

        if (RedisCache::isAvailable()) {
            $middleware[] = $this->redisCache();
        }

        Cache::addMiddleware(...$middleware);
    }

    /**
     * @codeCoverageIgnore
     */
    public function redisCache() {
        if (self::$redis === null) {
            self::$redis = new RedisCache([
                'host' => !empty(_cfg('REDIS_HOST')) ? _cfg('REDIS_HOST') : REDIS_HOST,
                'port' => !empty(_cfg('REDIS_PORT')) ? _cfg('REDIS_PORT') : REDIS_PORT,
                'timeout' => 5,
                'prefix' => !empty(_cfg('REDIS_PREFIX')) ? _cfg('REDIS_PREFIX') : REDIS_PREFIX
            ]);
        }

        return self::$redis;
    }

    /**
     * @codeCoverageIgnore
     */
    public static function DI(): \DI\Container
    {
        if (self::$di_container === null) {
            self::$di_container = (new \DI\ContainerBuilder())
                ->addDefinitions(_cfg('core') . '/di_config.php')
                ->build();
        }

        return self::$di_container;
    }
}
