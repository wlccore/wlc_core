<?php
namespace eGamings\WLC;

use eGamings\WLC\Cache\AbstractCache;

class RateLimiter
{
    protected static $_instance = null;

    private $middleware = null;
    private $rateLimitKey = 'RATE_LIMIT';
    private $rateLimitBlockKey = 'RATE_LIMIT_BLOCK';
    private $whiteList = [];

    private function __construct() {
        $this->setMiddleware(Core::getInstance()->redisCache());
        $this->whiteList = _cfg('rateLimiterIPsWhiteList') ?? [];
    }

    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function setMiddleware(AbstractCache $middleware) {
        $this->middleware = $middleware;
    }

    private function getKey($name, $id, $block = false) {
    	return implode(':', [($block ? $this->rateLimitBlockKey  : $this->rateLimitKey), $name, $id]);
    }

    public function isBlocked($name, $ip) {
        if (in_array($ip, $this->whiteList)) {
            return false;
        }

        $blockedKey = $this->getKey($name, $ip, true);
        return $this->middleware->exists($blockedKey, ['language' => '']);
    }

    public function limit($name, $ip, $limit = 3, $period = 60, $block = 300) {
        if ($this->isBlocked($name, $ip)) {
            return false;
        }

        $limitKey = $this->getKey($name, $ip);
        $blockedKey = $this->getKey($name, $ip, true);

        $currentRate = $this->middleware->incr($limitKey, $period, ['language' => '']);

        if ($currentRate >= $limit) {
            $this->middleware->set($blockedKey, 1, $block, ['language' => '']);
            $this->middleware->delete($limitKey, ['language' => '']);
            return false;
        }

        return true;
    }

    /**
     * @return void
     */
    public static function resetDepositsLimiter(): void
    {
        if (empty($_POST['ip'])) {
            return;
        }

        $name = 'postapi/v1/deposits';
        $limiter = self::getInstance();
        $limitKey = $limiter->getKey($name, $_POST['ip']);
        $blockedKey = $limiter->getKey($name, $_POST['ip'], true);

        $limiter->middleware->delete($limitKey, ['language' => '']);
        $limiter->middleware->delete($blockedKey, ['language' => '']);

        echo 1;
    }
}
