<?php

namespace eGamings\WLC;

use Raven_Client;
use Raven_ErrorHandler;
use Monolog\Handler\RavenHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger as MonologLogger;

class Logger
{
    static private $logger = null;
    static private $levels = [];

    public static function init()
    {
        $dsn = _cfg('SENTRY_DSN');

        if ($dsn) {
            self::$levels = MonologLogger::getLevels();

            $ravenClient = new \Raven_Client($dsn, [
                'tags' => [
                    'php_version' => phpversion()
                ],
                'release' => PROJECT_VERSION,
                'environment' => _cfg('env'),
                'prefixes' => [getcwd()],
                'auto_log_stacks' => true
            ]);

            $error_handler = new Raven_ErrorHandler($ravenClient);
            $error_handler->registerExceptionHandler();
            $error_handler->registerErrorHandler();
            $error_handler->registerShutdownFunction();

            $handler = new RavenHandler($ravenClient);
            $handler->setFormatter(new LineFormatter("[%datetime%] %message%\n"));

            self::$logger = new MonologLogger('WLC Core Logger');
            self::$logger->pushHandler($handler);

            self::$logger->pushProcessor(function ($record) {
                if (!empty($_SESSION['user']))
                    $record['context']['user'] = $_SESSION['user'];

                return $record;
            });
        }
    }

    public static function getLevelByName($level) {
        $level = strtoupper($level);

        if (array_key_exists($level, self::$levels)) {
            return self::$levels[$level];
        }

        return null;
    }

    public static function log($message, $level = 'error', $context = [])
    {
        $instanceName = self::getInstanceName();

        $contextInfo = implode(', ', $context);
        error_log('INSTANCE_' . $instanceName . ' ' . $message . (!empty($contextInfo) ? ' - ' . $contextInfo : '') );

        if (self::$logger !== null) {
            $loggerLevel = self::getLevelByName($level) ?: self::$levels['ERROR'];
            self::$logger->addRecord($loggerLevel, $message, $context);
        }
    }

    public static function getInstanceName(): string
    {
        if (!empty($_ENV['INSTANCE_NAME'])) {
            return $_ENV['INSTANCE_NAME'];
        } elseif (!empty($_SERVER['INSTANCE_NAME'])) {
            return $_SERVER['INSTANCE_NAME'];
        }

        return get_current_user();
    }
}
