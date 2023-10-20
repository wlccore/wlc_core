<?php

declare(strict_types=1);

namespace eGamings\WLC\Service;

use eGamings\WLC\Cache;

final class CookieProtection
{
    public const KEY_PREFIX = 'cookie_protection';
    public const KEY_PREFIX_EMAIL = 'cookie_protection_email_send';
    private const DEFAULT_TTL = 86400; // 24h

    /**
     * @param string $key
     * @param string $ip
     * @param string $userAgent
     * @return void
     */
    public function set(string $key, string $ip, string $userAgent): void
    {
        $ttl = _cfg('cookieProtectionTtl') ?: self::DEFAULT_TTL;

        Cache::set(
            self::KEY_PREFIX . $key,
            "{$ip}_{$userAgent}",
            $ttl
        );
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function get(string $key): ?string
    {
        return Cache::get(self::KEY_PREFIX . $key);
    }

    /**
     * @param string $key
     * @param string $route
     * @return bool
     */
    public function check(string $key, string $route): bool
    {
        $excludedRoutes = [
            'api/v1/bootstrap',
            'api/v1/games',
            'api/v1/metrics',
            'api/v1/docs',
            'api/v1/auth',
        ];

        if (in_array($route, $excludedRoutes, true)) {
            return true;
        }

        $storedToken = '';
        if (!empty($_SESSION['user'])) {
            $storedToken = $this->get($key);
        }

        return $storedToken === "{$_SERVER['REMOTE_ADDR']}_{$_SERVER['HTTP_USER_AGENT']}";
    }
}
