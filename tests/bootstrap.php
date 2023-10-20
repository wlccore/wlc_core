<?php
use eGamings\WLC\Config;
use eGamings\WLC\Classifier;
use eGamings\WLC\Games;
use eGamings\WLC\Utils;

Config::$_siteConfigFile        = '../tests/data/siteConfig.json';
Classifier::$countryListFile    = '../tests/data/countryList.json';
Games::$gamesListFile           = '../tests/data/gamesList.json';
Utils::$atomicReplace           = false;

$_SERVER['DOCUMENT_ROOT'] = __DIR__ . DIRECTORY_SEPARATOR . 'public_html';
$_SERVER['HTTP_USER_AGENT'] = 'WlcCoreTests/1.0';
$_SERVER['TEST_RUN'] = true;

$wlcCoreRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'root';

$cfg = [
    'language' => 'en',
    'allowedLanguages' => [
        'en' => 'en_US',
        'ru' => 'ru_RU',
        'lv' => 'lv_LV',
        'de' => 'de_DE'
    ],
    'root' => $_SERVER['DOCUMENT_ROOT'],
    'core' => $wlcCoreRoot,
    'inc'  => realpath($wlcCoreRoot. DIRECTORY_SEPARATOR . 'inc'),
    'defaultTimeZone' => 0,
    'ecommpayAsPopup' => 0,
    'maxWithdrawalQueries' => 3,
    'mobile' => 0,
    'mobileEnabled' => 0,
    'mobileDomainUsed' => false,
    'mobileDetected' => false,
    'forceDesktop' => false,
    'env' => 'dev',
    'classes' => $wlcCoreRoot. '/classes',
    'cache' => $wlcCoreRoot. '/../cache',
    'testData' => realpath(__DIR__ . DIRECTORY_SEPARATOR. '/data'),
    'lib' => $wlcCoreRoot. '/lib',
    'template' => $wlcCoreRoot . '/template',
    'template_c' => $wlcCoreRoot. '/../cache'
];

$cfg['site'] = "https://wlc_core.test";
$cfg['dbHost'] = "localhost";
$cfg['dbBase'] = "wlc_core";
$cfg['dbUser'] = "root";
$cfg['dbPass'] = "password";
$cfg['dbPort'] = 3306;

$cfg['fundistApiBaseUrl'] = "http://fundist/";
$cfg['fundistApiKey'] = "wlc-test-key";
$cfg['fundistApiPass'] = "wlc-test-pass";

$cfg['mailQueueLimit'] = 2;

define('REDIS_HOST', '');
define('REDIS_PORT', '');
define('REDIS_PREFIX', '');

require_once dirname(__DIR__) . '/vendor/autoload.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/init.php';
require_once $wlcCoreRoot . '/inc/functions.php';
