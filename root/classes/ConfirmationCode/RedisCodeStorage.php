<?php

declare(strict_types=1);

namespace eGamings\WLC\ConfirmationCode;

use eGamings\WLC\Cache\RedisCache;
use eGamings\WLC\Logger;
use eGamings\WLC\ConfirmationCode\Contracts\CodeStorage;
use eGamings\WLC\System;
use RuntimeException;

final class RedisCodeStorage implements CodeStorage
{
    private const DEFAULT_TTL = 600;

    /**
     * @var RedisCache
     */
    private $redis;

    /**
     * @var string
     */
    private $keyPrefix;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @param string $keyPrefix
     * @param int|null $ttl
     */
    public function __construct(string $keyPrefix, ?int $ttl = null)
    {
        $this->redis = System::redis();
        $this->keyPrefix = $keyPrefix;
        $this->ttl = $ttl ?? self::DEFAULT_TTL;
    }

    /**
     * @param object $user
     * @param string $code
     *
     * @return array
     */
    public function pop(object $user, string $code): array
    {
        $redisKey = "{$this->keyPrefix}_{$user->id}";

        if (!($dataJson = $this->redis->get($redisKey))
            || !($data = json_decode($dataJson, true))
            || empty($data['code'])
            || $data['code'] !== $code
        ) {
            throw new RuntimeException('Code not found.');
        }

        if (empty($data['userId'])
            || empty($data['data'])
            || (int)$user->id !== (int)$data['userId']
        ) {
            throw new RuntimeException('Empty code data.');
        }

        $this->redis->delete($redisKey);

        return $data['data'];
    }

    /**
     * @param object $user
     * @param string $code
     * @param array $data
     *
     * @return void
     */
    public function push(object $user, string $code, array $data): void
    {
        $redisKey = "{$this->keyPrefix}_{$user->id}";

        $codeData = [
            'userId' => $user->id,
            'data' => $data,
            'code' => $code,
            'time' => time(),
        ];

        if (!$this->redis->set($redisKey, json_encode($codeData), $this->ttl)) {
            Logger::log(__CLASS__ . 'Redis error. Failed set key - ' . $redisKey);

            throw new RuntimeException('Error saving code.');
        }
    }
}
