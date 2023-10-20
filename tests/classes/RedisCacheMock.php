<?php

namespace eGamings\WLC\Tests;

use eGamings\WLC\Cache\RedisCache;

class RedisCacheMock extends RedisCache
{
    public function __construct(array $config = [])
    {
        if (empty($config)) {
            $config = [
                'host' => !empty(_cfg('REDIS_HOST')) ? _cfg('REDIS_HOST') : REDIS_HOST,
                'port' => !empty(_cfg('REDIS_PORT')) ? _cfg('REDIS_PORT') : REDIS_PORT,
                'timeout' => 5,
                'prefix' => "RedisPrefix"
            ];
        }

        parent::__construct($config);
    }

    public function redis()
    {
        return new RedisMock();
    }
}
