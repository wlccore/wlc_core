<?php

namespace eGamings\WLC\Cache;


class ApcCache extends AbstractCache
{
    private $prefix;
    private $methodPrefix = 'apc';

    public function __construct(array $config = [])
    {
        if (array_key_exists('prefix', $config)) {
            $this->prefix = $config['prefix'];
        }

        if (extension_loaded('apcu')) {
            $this->methodPrefix = 'apcu';
        }
    }

    public static function isAvailable()
    {
        // @codeCoverageIgnoreStart
        if (PHP_SAPI === 'cli' && empty($_SERVER['TEST_RUN'])) {
            return false;
        }
        // @codeCoverageIgnoreEnd
        $extLoaded = extension_loaded('apc') === true || extension_loaded('apcu') === true;
        return $extLoaded && ini_get('apc.enabled');
    }

    protected function key($key, $cacheArgs = [])
    {
        $fullKey = parent::key($key, $cacheArgs);

        if (!empty($this->prefix)) {
            $fullKey = $this->prefix . ':' . $fullKey;
        }

        return $fullKey;
    }

    public function get($key, $cacheArgs = [])
    {
        $fullKey = $this->key($key, $cacheArgs);
        $method = $this->methodPrefix . '_fetch';
        $success = false;
        $value = $method($fullKey, $success);

        return $success ? $value : null;
    }

    public function set($key, $value, $ttl = 60, $cacheArgs = [])
    {
        $fullKey = $this->key($key, $cacheArgs);
        $method = $this->methodPrefix . '_store';
        return $method($fullKey, $value, $ttl);
    }

    public function delete($key, $cacheArgs = [])
    {
        $fullKey = $this->key($key, $cacheArgs);
        $method = $this->methodPrefix . '_delete';
        return $method($fullKey);
    }

    public function exists($key, $cacheArgs = [])
    {
        $method = $this->methodPrefix . '_exists';
        return $method($this->key($key, $cacheArgs));
    }

    public function incr($key, $ttl = 60, $cacheArgs = [])
    {
        $fullKey = $this->key($key, $cacheArgs);
        $method = $this->methodPrefix . '_inc';
        $success = false;
        $value = $method($fullKey, 1, $success);

        if (!$success) {
            $value = 1;
            $this->set($key, $value, $ttl, $cacheArgs);
        }

        return $value;
    }

    public function dropCacheKeys($keyword)
    {
        $apc_pattern = '/' . $keyword . '/';
        if ($this->methodPrefix == 'apcu' && class_exists('\APCUIterator')) {
            $iterator = new \APCUIterator($apc_pattern);
        } else {
            $iterator = new \APCIterator('user', $apc_pattern);
        }
        $method = $this->methodPrefix . '_delete';
        return $method($iterator);
    }

    public function ttl()
    {

        return false;
    }
}
