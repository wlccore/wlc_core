<?php
namespace eGamings\WLC;

use eGamings\WLC\Cache\AbstractCache;

class Cache {
    public static $middleware = [];

    public static function addMiddleware(AbstractCache ...$middleware) {
        foreach ($middleware as $m) {
            self::$middleware[] = $m;
        }
    }

    public static function clearMiddleware() {
        self::$middleware = [];
    }

    public static function result($key, callable $func = null, $ttl = 60, array $cacheArgs = []) {
        $value = self::get($key, $cacheArgs);

        if ($value === null && is_callable($func)) {
            try {
                $value = call_user_func($func);
                if ($value !== null) {
                    self::set($key, $value, $ttl, $cacheArgs);
                }
            } catch (\Exception $ex) {}
        }

        return $value;
    }

    public static function get($key, array $cacheArgs = []) {
        foreach (self::$middleware as $middleware) {
            $value = $middleware->get($key, $cacheArgs);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    public static function set($key, $value, $ttl = 60, array $cacheArgs = []) {
        $result = true;

        foreach (self::$middleware as $middleware) {
            $result = $middleware->set($key, $value, $ttl, $cacheArgs) && $result;
        }

        return $result;
    }

    public static function delete($key, array $cacheArgs = []) {
        $result = true;

        foreach (self::$middleware as $middleware) {
            $result = $middleware->delete($key, $cacheArgs) && $result;
        }

        return $result;
    }

    public static function dropCacheKeys($keyword) {
        foreach (self::$middleware as $middleware) {
            $middleware->dropCacheKeys($keyword);
        }
    }

    public static function del($key, array $cacheArgs = []) {
        return self::delete($key, $cacheArgs);
    }
}
