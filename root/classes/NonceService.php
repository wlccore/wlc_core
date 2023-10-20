<?php

declare(strict_types=1);

namespace eGamings\WLC;

use eGamings\WLC\Cache;

final class NonceService
{
    private const KEY_PREFIX = 'nonce_token_';
    private const DEFAULT_TTL = 86400;

    /**
     * @param string $key
     * @param string $token
     *
     * @return void
     */
    public function set(string $key, string $token): void
    {
        $ttl = _cfg('nonceTtl') ?: self::DEFAULT_TTL;

        Cache::set(self::KEY_PREFIX . $key, $token, $ttl);
    }

    /**
     * @param string $key
     *
     * @return string|null
     */
    public function get(string $key): ?string
    {
        return Cache::get(self::KEY_PREFIX . $key);
    }
}
