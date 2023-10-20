<?php

namespace eGamings\WLC;

use eGamings\WLC\RestApi\GamesResource;
use eGamings\WLC\RestApi\ApiEndpoints;
use eGamings\WLC\Logger;

abstract class GZ
{
    public const WATCHED_VALUES_TTL = 12*60*60; // 12h
    public const FRESH_LIFE_TIME = 15*60; // 15m

    public static $fileName;
    public static $cacheDir = null;
    public static $fileNames = [];


    public static $watchedConfgVars = ['websiteCDN'];
    public static $watchRedisKeyPostfix = '_GZWatchedValues';
    public static $forceUpdate = false;
    public static $filters = [];
    public static $lockKey = '_gz_games';

    public static function fileNames($type): string 
    {
        if (!empty(self::$fileNames)) {
            return self::$fileNames[$type];
        }

        $domain = str_replace(['https://','http://'],'',_cfg('site'));
        self::$fileNames = [
            'nexus' => 'gamesList_'.$domain,
            'filled' => 'filledGamesList_'.$domain,
            'slim' => 'gamesListSlim_'.$domain
        ];

        return self::$fileNames[$type];
    }

    public static function canUse(array $filters, array $query): bool
    {
        self::$filters = $filters;

        if (
            Utils::either(
                $filters['merchant'],
                $filters['category'],
                $filters['order_by']
            )
            || !self::checkConfigVars()
            || !self::canClientHandleGZ()
            || !self::makeMinFiles()
        ) {
            return false;
        }

        self::$fileName = (bool) $query['slim'] ? self::fileNames('slim') : self::fileNames('filled');
        return true;
    }

    public static function checkConfigVars(): bool
    {
        $redis = System::redis(true);
        $key = Logger::getInstanceName() . self::$watchRedisKeyPostfix;
        $previousValues = $redis->get($key);

        if (!is_array($previousValues)) {
            self::setForceUpdate();
            return true;
        }

        foreach (self::$watchedConfgVars as $var) {
            if (isset($previousValues[$var]) && _cfg($var) !== $previousValues[$var]) {
                self::setForceUpdate();
                break;
            }
        }

        return true;
    }

    public static function setForceUpdate(): void
    {
        // Games::DropCache(false);
        self::$forceUpdate = true;
    }

    public static function rebuildWatchCache(): bool
    {
        $redis = System::redis(true);
        $key = Logger::getInstanceName() . self::$watchRedisKeyPostfix;

        $values = [];
        foreach (self::$watchedConfgVars as $var) {
            $values[$var] = _cfg($var);
        }

        return $redis->set($key, $values, self::WATCHED_VALUES_TTL);
    }

    public static function getCacheDir(): string
    {
        if (self::$cacheDir === null) { // TODO: Implement a mock value
            self::$cacheDir = _cfg('cache') . DIRECTORY_SEPARATOR;
        }

        return self::$cacheDir;
    }

    public static function canClientHandleGZ(): bool
    {
        return stripos($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip') !== false;
    }

    public static function getGZ(): string
    {
        $filePath = Utils::joinPaths(
            self::getCacheDir(),
            sprintf(
                "%s%s.json.gz",
                self::$fileName,
                Utils::isMobile() ? '.mobile' : ''
            )
        );

        return file_exists($filePath) ? file_get_contents($filePath) : "";
    }

    /**
     * @codeCoverageIgnore
     */
    public static function getJsonSlim(): string
    {
        $filePath = Utils::joinPaths(
            self::getCacheDir(),
            self::fileNames('slim') . '.json'
        );

        return file_exists($filePath) ? file_get_contents($filePath) : "";
    }

    public static function dropCache(): void
    {
        $redis = System::redis(true);
        $key = Logger::getInstanceName() . self::$watchRedisKeyPostfix;

        if ($redis->exists($key)) {
            $redis->delete($key);
        }
    }

    public static function makeMinFiles(): bool
    {
        $dir = self::getCacheDir();

        $nexusPath = Utils::joinPaths($dir, sprintf("%s.json", self::fileNames('nexus')));
        $filledPath = Utils::joinPaths($dir, sprintf("%s.json", self::fileNames('filled')));
        $slimPath = Utils::joinPaths($dir, sprintf("%s.json", self::fileNames('slim')));

        $gzFreshLifeTime = empty(_cfg('gzFreshLifeTime')) ? self::FRESH_LIFE_TIME : _cfg('gzFreshLifeTime');

        if (
            !self::$forceUpdate
            && self::isFreshCacheFile($slimPath, $gzFreshLifeTime)
            && file_exists($filledPath)
            && file_exists($slimPath)
        ) {
            return true;
        }

        // @codeCoverageIgnoreStart
        if (self::isLocked()) {
            return true;
        }
        // @codeCoverageIgnoreEnd
        self::lock();

        Utils::$isMobileOverride = self::$filters['is_mobile'] = false;

        $filledData = GamesResource::getGamesCatalog(self::$filters);
        self::saveCache($filledPath, $filledData);

        Utils::$isMobileOverride = self::$filters['is_mobile'] = true;

        $filledData['games'] = self::truncateUnwantedFields($filledData['games']);
        self::saveCache($slimPath, $filledData);
        unset($filledData);

        $filledDataMobile = GamesResource::getGamesCatalog(self::$filters);
        self::saveCache(Utils::joinPaths($dir, sprintf("%s.mobile.json", self::fileNames('filled'))), $filledDataMobile);
        self::saveCache(Utils::joinPaths($dir, sprintf("%s.mobile.json", self::fileNames('slim'))), $filledDataMobile);

        Utils::$isMobileOverride = null;

        self::rebuildWatchCache();
        self::unlock();

        return true;
    }

    public static function truncateUnwantedFields(array $games): array
    {
        $requiredFields =  [
            'ID', 'Name', 'Image', 'Url', 'hasDemo', 'CategoryID', 'MerchantID',
            'AR', 'IDCountryRestriction', 'Sort', 'LaunchCode', 'CategoryTitle',
            'SortPerCategory', 'SubMerchantID', 'CustomSort', 'Background',
            'IsVirtual', 'Freeround', 'TableID',
        ];
        $_games = [];
        foreach ($games as $key => $game) {
            $_games[$key] = [];
            foreach ($requiredFields as $field) {
                if (array_key_exists($field, $game)) {
                    $_games[$key][$field] = $games[$key][$field];
                }
            }
        }

        return $_games;
    }

    /**
     * Saves data at the specified path in json format
     *
     * @param string $path
     * @param mixed  $data
     *
     * @return bool
     */
    public static function saveCache(string $path, $data): bool
    {
        $data = ApiEndpoints::buildResponse(200, 'success', $data);
        return Utils::atomicFileReplace($path, json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public static function isFreshCacheFile(string $filePath, int $lifeTime = 43200): bool
    {
        return Config::isConfigFileValidByTimeFrame($filePath, $lifeTime);
    }

    /**
     * @codeCoverageIgnore
     */
    public static function isLocked(): bool
    {
        $redis = System::redis(true);
        $key = Logger::getInstanceName() . self::$lockKey;

        return $redis->exists($key);
    }

    /**
     * @codeCoverageIgnore
     */
    public static function lock(): bool
    {
        $redis = System::redis(true);
        $key = Logger::getInstanceName() . self::$lockKey;
        if (!$redis->exists($key)) {
            if (!$redis->set($key, time(), 60)) {
                Logger::log("Redis error. Failed set key " . $$key);
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public static function unlock(): bool
    {
        $redis = System::redis(true);
        $key = Logger::getInstanceName() . self::$lockKey;
        if ($redis->exists($key)) {
            $redis->delete($key);
            return true;
        }
        return false;
    }
}
