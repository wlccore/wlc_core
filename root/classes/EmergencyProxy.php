<?php
namespace eGamings\WLC;

use eGamings\WLC\RestApi\BootstrapResource;
use eGamings\WLC\RestApi\CountryResource;
use eGamings\WLC\RestApi\GamesResource;
use eGamings\WLC\RestApi\JackpotResource;

/**
 * Class EmergencyProxy
 * @codeCoverageIgnore
 * @package eGamings\WLC
 */
class EmergencyProxy
{
    protected static $handlersConfig = [
        'bootstrap' => [
            'handler' => [EmergencyProxy::class, 'handleBootstrap'],
            'args'    => []
        ],
        'games' => [
            'handler' => [EmergencyProxy::class, 'handleGames'],
            'args'    => []
        ],
        'countries' => [
            'handler' => [EmergencyProxy::class, 'handleCountries'],
            'args'    => []
        ],
        'jackpots' => [
            'handler' => [EmergencyProxy::class, 'handleJackpots'],
            'args'    => []
        ]
    ];

    // Relative from wlc directory
    protected static $saveDir = 'static/dist/api/v1';

    protected static $env = [];

    public static function rebuildCache(): bool
    {
        self::beforeRebuildCache();

        foreach(self::$handlersConfig as $name => &$handler) {
            [$class, $method] = $handler['handler'];

            if (class_exists($class) && method_exists($class, $method)) {
                $data = call_user_func_array([$class, $method], $handler['args'] ?: []);

                if (trim($data) === '') {
                    self::reportError(sprintf("Handler '%s' returned an empty string, do not replace it", $name));
                    continue;
                }

                if (!self::save($name, $data)) {
                    self::reportError(sprintf('Save failed for "%s"', $name));
                }
            } else {
                self::reportError(sprintf('Cannot find class/method for handler "%s" (%s)', $name, json_encode($handler['handler'])));
            }
        }

        self::afterRebuildCache();

        return true;
    }

    protected static function save(string $name, string $data): bool
    {
        try {
            $directoryForSave = self::getDirectoryForSave();

            if ($directoryForSave === null) {
                return false;
            }

            $filePath = $directoryForSave . DIRECTORY_SEPARATOR . $name . '.json';

            Utils::$atomicReplace = true;

            $errors = [];
            $try = 5;
            $errorMessage = '';

            do {
                $result = Utils::atomicFileReplace($filePath, $data, $errorMessage);

                if ($result === false) {
                    $errors[] = $errorMessage;
                } else {
                    // The file has been successfully wrote, setting up the permissions on files, required -rw-r-----
                    chmod($filePath, 0640);

                    $gzVersionFile = $filePath . '.gz';

                    if (file_exists($gzVersionFile)) {
                        @unlink($gzVersionFile);
                    }

                    break;
                }

                $try--;
            } while ($try > 0);

            if (!$result) {
                foreach (array_unique($errors) as $error) {
                    self::reportError($error);
                }

                return false;
            }
        } catch (\Exception $e) {
            self::reportError($e->getMessage());

            return false;
        }

        return true;
    }

    protected static function createDirectory(string $directory): bool
    {
        try {
            if (mkdir($directory, 0777, true) === false) {
                throw new \Exception(sprintf("Unable to create directory '%s' for backup.", $directory));
            }
        } catch (\Exception $e) {
            // @TODO: fix me later
            self::reportError($e->getMessage());

            return false;
        }

        return true;
    }

    protected static function getDirectoryForSave(): ?string
    {
        $directory = _cfg('root') . DIRECTORY_SEPARATOR . self::$saveDir;

        if (file_exists($directory)) {
            return $directory;
        }

        return self::createDirectory($directory) ? realpath($directory) : null;
    }

    protected static function beforeRebuildCache(): void
    {
        self::$env['atomicReplace'] = Utils::$atomicReplace;
    }

    protected static function afterRebuildCache(): void
    {
        Utils::$atomicReplace = self::$env['atomicReplace'];
    }

    protected static function reportError(string $message): void
    {
        error_log($message);
    }

    // HANDLERS

    public static function handleBootstrap(): string
    {
        $bootstrapResource = new BootstrapResource();

        return $bootstrapResource->buildBootstrap([]) ?: '';
    }

    public static function handleGames(): string
    {
        return GamesResource::buildGamesList();
    }

    public static function handleCountries(): string
    {
        return CountryResource::buildCountryList();
    }

    public static function handleJackpots(): string
    {
        return JackpotResource::buildJackpots([]);
    }
}