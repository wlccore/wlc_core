<?php

namespace eGamings\WLC\Cache;

class RedisCache extends AbstractCache
{
    public static $redis = null;
    private $host;
    private $port;
    private $timeout;
    private $prefix;

    public function __construct(array $config = [])
    {
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->timeout = $config['timeout'];
        $this->prefix = array_key_exists('prefix', $config) ? $config['prefix'] : '';
    }

    public static function isAvailable() {
        return class_exists('\Redis');
    }

    public function redis() {
        if (self::$redis === null) {
            self::$redis = new \Redis();

            self::$redis->connect(
                $this->host,
                $this->port,
                $this->timeout
            );

            self::$redis->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);

            !empty($this->prefix) && self::$redis->setOption(\Redis::OPT_PREFIX, $this->prefix);
        }

        return self::$redis;
    }

    public function get($key, $cacheArgs = []) {
        $fullKey = $this->key($key, $cacheArgs);

        if ($value = $this->redis()->get($fullKey)) {
            $value = unserialize($value);

            return $value;
        }

        return null;
    }

    public function set($key, $value, $ttl = 60, $cacheArgs = []) {
        $fullKey = $this->key($key, $cacheArgs);

        return $this->redis()->set($fullKey, serialize($value), $ttl);
    }

    public function delete($key, $cacheArgs = []) {
        $fullKey = $this->key($key, $cacheArgs);

        return (bool) $this->redis()->del($fullKey);
    }

    public function exists($key, $cacheArgs = [])
    {
        return $this->redis()->exists($this->key($key, $cacheArgs));
    }

    public function incr($key, $ttl = 60, $cacheArgs = [])
    {
        $fullKey = $this->key($key, $cacheArgs);
        $value   = $this->redis()->incr($fullKey);

        if ($value == 1) {
            $this->redis()->expire($fullKey, $ttl);
        }

        return $value;
    }

    public function dropCacheKeys($keyword)
    {
        $result = true;

        $redis = $this->redis();
        $redisPattern = '*' . $keyword . '*';
        $it = null;
        while ($keys = $redis->scan($it, $redisPattern, 100)) {
            foreach ($keys as $key) {
                $relatedKey = !empty($this->prefix) ? str_replace($this->prefix, '', $key) : $key;
                $result = $redis->del($relatedKey) && $result;
            }
        }

        return $result;
    }

    public function ttl($key, $cacheArgs = []) {
        $fullKey = $this->key($key, $cacheArgs);

        return $this->redis()->ttl($fullKey);
    }
}
