<?php

namespace eGamings\WLC\Cache;

use eGamings\WLC\Service\CookieProtection;

abstract class AbstractCache
{
    /**
     * Get cache value
     * @param string $key
     * @param array $cacheArgs
     * @return mixed
     */
    abstract public function get($key, $cacheArgs = []);

    /**
     * Set cache value
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @param array $cacheArgs
     * @return boolean
     */
    abstract public function set($key, $value, $ttl = 60, $cacheArgs = []);

    /**
     * Delete cache by key
     * @param string $key
     * @param array $cacheArgs
     * @return boolean
     */
    abstract public function delete($key, $cacheArgs = []);

    /**
     * Delete cache by pattern
     * @param string $keyword
     * @return void
     */
    abstract public function dropCacheKeys($keyword);

    /**
     * Verify if the specified key exists
     * @param string $key
     * @param array $cacheArgs
     * @return boolean
     */
    abstract public function exists($key, $cacheArgs = []);

    /**
     * Increment the number stored at key by one
     * @param string $key
     * @param int $ttl
     * @param array $cacheArgs
     * @return mixed
     */
    abstract public function incr($key, $ttl = 60, $cacheArgs = []);

    /**
     * Return status of the cache
     * @return boolean
     */
    static function isAvailable() {
        return false;
    }

    /**
     * Return cache key by params
     * @param string $key
     * @param array $cacheArgs
     * @return string Full key
     */
    protected function key($key, $cacheArgs = [])
    {
        $language = _cfg('language');
        $fullKeyArr = [];

        $languageExcludedKeys = [
            'Jwt_auth_key_' . _cfg('websiteName'),
            'Jwt_refresh_key_' . _cfg('websiteName'),
        ];

        $withoutLanguage = (isset($cacheArgs['language']) && empty($cacheArgs['language']))
            || in_array($key, $languageExcludedKeys, true)
            || strpos($key, CookieProtection::KEY_PREFIX) !== false;

        if ($withoutLanguage) {
            unset($cacheArgs['language']);
        } else {
            $fullKeyArr[] = $language;
        }

        if (_cfg('mobile')) {
            $fullKeyArr[] = 'mobile';
        }

        $fullKeyArr[] = $key;
        if (!empty($cacheArgs)) {
            $fullKeyArr[] = md5(serialize($cacheArgs));
        }

        return implode(':', $fullKeyArr);
    }

    /**
     * Return and store the value of the cache function
     * @param $key
     * @param callable|null $func
     * @param int $ttl
     * @param array $cacheArgs
     * @return mixed
     */
    public function result($key, callable $func = null, $ttl = 60, $cacheArgs = []) {
        $value = $this->get($key, $cacheArgs);

        if ($value === null && is_callable($func)) {
            $value = call_user_func($func);

            $this->set($key, $value, $ttl, $cacheArgs);
        }

        return $value;
    }
}
