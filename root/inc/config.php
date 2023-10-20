<?php
use eGamings\WLC\PlatformDetect;
use eGamings\WLC\ConfigProcessor;

if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('UTC');
}

if (empty($GLOBALS['cfg'])) {
    $GLOBALS['cfg'] = [];
}
$cfg = &$GLOBALS['cfg'];
$cfg['hooks'] = [];

// @TODO: filter_var_array?
$configDefinition = [
    'rateLimiterIPsWhiteList' => [
        'type' => 'array',
        'errors' => [
            'errorMessage' => 'There must be an array of IPs'
        ],
        'validator' => static function (array $vars): int {
            foreach($vars as $var) {
                if (filter_var($var, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) === false) {
                    return ConfigProcessor::$VALIDATOR_ERROR;
                }
            }

            return ConfigProcessor::$VALIDATOR_OK;
        },
        'validatorError' => 'There is NOT an array of IPs'
    ],
    'recaptchaIPsWhiteList' => [
        'type' => 'array',
        'errors' => [
            'errorMessage' => 'There must be an array of IPs'
        ],
        'validator' => static function (array $vars): int {
            foreach($vars as $var) {
                if (filter_var($var, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) === false) {
                    return ConfigProcessor::$VALIDATOR_ERROR;
                }
            }

            return ConfigProcessor::$VALIDATOR_OK;
        },
        'validatorError' => 'There is NOT an array of IPs'
    ],
    'isFuncoreLogoutRequired' => [
        'type' => 'boolean',
        'errors' => [
            'errorMessage' => 'There must be an bool value'
        ],
        'validatorError' => 'There is NOT an bool'
    ],
    'globalAffCookieSecPeriod' => [
        'type' => 'integer',
        'errors' => [
            'errorMessage' => 'There must be an integer value'
        ],
        'validatorError' => 'There is NOT an integer'
    ],
    'enableCaptcha' => [
        'type' => 'boolean',
        'errors' => [
            'errorMessage' => 'There must be an bool value'
        ],
        'validatorError' => 'There is NOT an bool'
    ],
    'captchaConfig' => [
        'type' => 'array',
        'errors' => [
            'errorMessage' => 'There must be an array value'
        ],
        'validator' => static function (array $config): int {
            foreach([
                'hour',
                'day'
            ] as $requiredKey) {
                if (empty($config[$requiredKey]) || gettype($config[$requiredKey]) !== 'integer') {
                    return ConfigProcessor::$VALIDATOR_TYPES_MISMATCH;
                }
            }

            return ConfigProcessor::$VALIDATOR_OK;
        },
        'validatorError' => 'Required "hour" [int] and "day" [int] values'
    ],
];
$configProcessor = new ConfigProcessor($cfg, $configDefinition);

/**
 * Configuration file
 */
set_time_limit(32); //Required few more seconds than time limit on fundist, or will return php error

if ((!isset($_SERVER['DOCUMENT_ROOT']) || $_SERVER['DOCUMENT_ROOT'] == '') && isset($cfg['root']) && $cfg['root'] != '') $_SERVER['DOCUMENT_ROOT'] = $cfg['root'];
if (empty($_SERVER['HTTP_HOST'])) {
    if (!empty($_ENV['HTTP_HOST'])) {
        $_SERVER['HTTP_HOST'] = $_ENV['HTTP_HOST'];
    }
}

define('SYNC_MAX_HITS_ROWS', 3000);

//=====================================================

/**
 * Main site preference - in document root folder
 */

if(!isset($cfg['root'])) {
    die('NO ROOT');
}

$cfg['core'] = dirname(__DIR__);
$cfg['inc'] = __DIR__;
$cfg['content'] = $cfg['root'].'/content/index.php'; //can be redaclarated in client siteconfig

$cfg['defaultTimeZone'] = 0; // in minutes, example: -60 = GMT-1 or 180 = GMT+3
$cfg['ecommpayAsPopup'] = 0; // open ecommpay payment as pop
$cfg['maxWithdrawalQueries'] = 3; //Maximum withdrawal money requests allowed
$cfg['mobile'] = 0; //Defining config for mobile version
$cfg['mobileEnabled'] = 0; //Default value, should be always 0, must be modified in client siteconfig.php
$cfg['mobileDomainUsed'] = false;
$cfg['mobileDetected'] = false;
$cfg['forceDesktop'] = false;
$cfg['registerGeneratePassword'] = false;

$cfg['useMetamask'] = false;

$zone = explode('.', $_SERVER['HTTP_HOST']);

$cfg['pcEmulation'] = !empty($_COOKIE['PC_EMULATION']);
$cfg['mobileDomainUsed'] = (sizeof($zone) >= 3 && substr($zone[0], 0, 1) == 'm') ? true : false;
$cfg['mobileDetected'] = PlatformDetect::isMobile() || $cfg['pcEmulation'];
$cfg['forceDesktop'] = (!empty($_COOKIE['FORCE_DESKTOP']) || isset($_GET['forcedesktop']));
$cfg['forceMobile'] = isset($_GET['forcemobile']);

if  (($cfg['mobileDomainUsed'] || $cfg['mobileDetected'] || $cfg['forceMobile']) && !$cfg['forceDesktop'])
{
    $cfg['mobile'] = 1;
}

$cfg['allowPartialUpdate'] = true;

/**
 * Available values: 'agreedWithTermsAndConditions','ageConfirmed','agreeWithSelfExcluded' and etc.
 */
$cfg['requiredRegisterCheckbox'] = [];

$cfg['enableAffiliates'] = true;
$cfg['setAffiliateCookieByPromoCode'] = true;
$cfg['unsetAffKeys'] = true;

$cfg['recaptchaLog'] = false;

$cfg['ignoreAnyExcludeCountries'] = false;

/**
 * GeoIP2 Default Parameters
 */
define('GEOIP2_DATABASE_PATH', '/usr/share/GeoIP/GeoLite2-Country.mmdb');
define('GEOIP2_DATABASE_TYPE', 'country');
if (file_exists(GEOIP2_DATABASE_PATH)) {
    $cfg['geoipDatabasePath'] = GEOIP2_DATABASE_PATH;
    $cfg['geoipDatabaseType'] = GEOIP2_DATABASE_TYPE;
}

define('GEOIP2_CITY_DATABASE_PATH', '/usr/share/GeoIP/GeoLite2-City.mmdb');
define('GEOIP2_CITY_DATABASE_TYPE', 'city');
if (file_exists(GEOIP2_CITY_DATABASE_PATH)) {
    $cfg['geoipCityDatabasePath'] = GEOIP2_CITY_DATABASE_PATH;
    $cfg['geoipCityDatabaseType'] = GEOIP2_CITY_DATABASE_TYPE;
}

/*
Most WLC uses Fundist balance, as merchants are integrated via single wallet,
Binarium uses SpotOption, that is integrated via separate wallet, so it needs to show balance under SpotOption merchant

$cfg['wallet']['mode'] = {'single'|'separate'}
$cfg['wallet']['default_merchant'] = NNN - ID of default merchant (separate wallet), that balance should be shown

*/
$cfg['wallet'] = [
    'mode' => 'single',
    'default_merchant' => '',
];

// Cookie storage
$cfg['cs_cookie_name'] = 'jwtstorage';

$siteconfig_inc = $cfg['inc'].'/siteconfig.php';

if (file_exists($siteconfig_inc)) {
    require_once $siteconfig_inc;
} else {
    $envs = array(
        'dev' => 'dev',
        'mdev' => 'dev',
    	'development' => 'dev',
        'psklo' => 'dev',
        'qa' => 'qa',
        'mqa' => 'qa',
        'test' => 'test',
        'mtest' => 'test',
        'testing' => 'test',
        '*' => 'prod'
    );

    $server_vars = ['SITE_ENV', 'SITE_ENVIRONMENT', 'HTTP_HOST'];

    $host_id = 'production';
    foreach($server_vars as $server_var) {
        if (!empty($_SERVER[$server_var])) {
            $url_info = parse_url($_SERVER[$server_var]);
            if (isset($url_info['host'])) {
                $host_arr = explode('.', $url_info['host']);
                if (sizeof($host_arr) > 2) {
                    $host_id = $host_arr[sizeof($host_arr) - 3];
                }
            } else {
                $host_id = $url_info['path'];
            }
            break;
        }
    }

    // If the $host_id is the "path" component
    if(strpos($host_id, '.') !== false) {
        $tld = explode('.', $host_id);
        $tld = array_pop($tld);

        $devTLDs = [
          'pskovoffice', // This TLD is the prefix, i. e. pskovoffice55
          'localhost'
        ];

        foreach ($devTLDs as $devTLD) {
            if (stripos($tld, $devTLD) !== false) {
                $cfg['env'] = 'dev';
                break;
            }
        }
    } else {
        $envs = $envs ?? [];
        foreach ($envs as $envs_prefix => $envs_name) {
            if (strpos($host_id, $envs_prefix) === 0) {
                $cfg['env'] = $envs_name;
                break;
            }
        }
    }

    if ($envs_prefix == "*") {
        $cfg['env'] = $envs_name;
    }


    $siteconfig_inc = $cfg['inc'].'/siteconfig-'.$cfg['env'].'.php';
    if (file_exists($siteconfig_inc)) {
        require_once($siteconfig_inc);
    }
}

//----------------
$project_version_file = dirname($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR . 'project_version';

if ( !file_exists($project_version_file) || !($project_version = file_get_contents($project_version_file)) )
{
    if ($cfg['env'] !== 'dev') {
        // Fail with configuration error
        die('Configuration Error 0');
    } else {
        // YYYYMMDD.HHMMSS
        $project_version = date('Ymd.His');
    }
}

$project_version = trim($project_version);
$project_version = str_replace(Array(CHR(10),CHR(13)), '', $project_version);

define('PROJECT_VERSION', $project_version);
$cfg['projectVersion'] = $project_version;
$cfg['static'] = 'static'.PROJECT_VERSION.'/';
//----------------

require_once $cfg['root'].'/siteconfig.php'; //client siteconfig

// Sportsbook Api Url
switch ($cfg['env']) {
    case 'prod':
        $cfg['sportsbookApiURL'] = (defined('KEEPALIVE_PROXY') ? KEEPALIVE_PROXY : 'https:/') . '/api-sportsbet.esportings.com';
        break;

    case 'test':
        $cfg['sportsbookApiURL'] = (defined('KEEPALIVE_PROXY') ? KEEPALIVE_PROXY : 'https:/') . '/test-brserver.esportings.com';
        break;

    default:
        $cfg['sportsbookApiURL'] = (defined('KEEPALIVE_PROXY') ? KEEPALIVE_PROXY : 'https:/') . '/qa-brserver.egamings.com';
        break;
}



$cfg['mobile'] = $cfg['mobile'] && $cfg['mobileEnabled'];

if ( empty($cfg['env']) ||
     empty($cfg['dbHost']) ||
     empty($cfg['dbBase']) ||
     empty($cfg['dbUser']) ||
     empty($cfg['dbPass']) ||
     empty($cfg['dbPort']) )
{
    die('Configuration Error 1');
}

/**
 * Core parameters
 */
$cfg['classes'] = $cfg['core'].'/classes';
$cfg['cache'] = $cfg['root'].'/../cache';
$cfg['lib'] = $cfg['core'].'/lib';

$cfg['template'] = $cfg['root'].'/template';
$cfg['template_c'] = $cfg['root'].'/../tmp';

$cfg['plugins'] = $cfg['root'].'/plugins';

// Last checks for config
if ( !$cfg['fundistApiBaseUrl'] ||
     !$cfg['fundistApiKey'] ||
     !$cfg['fundistApiPass'] )
{
    die('Configuration Error 2');
}

$rateLimiterIPsWhiteListDefault = [
    '78.28.223.26',
    '217.28.63.154',
    '217.28.63.155',
];

$recaptchaIPsWhiteListDefault = [
    '217.28.63.154',
    '217.28.63.155',
];

$configProcessor->setConfig($cfg);
$configProcessor->validateConfig([
    'rateLimiterIPsWhiteList' => $rateLimiterIPsWhiteListDefault,
    'recaptchaIPsWhiteList' => $recaptchaIPsWhiteListDefault,
    'isFuncoreLogoutRequired' => false,
    'globalAffCookieSecPeriod' => 60 * 60 * 24 * 30,
    'enableCaptcha' => false,
    'captchaConfig' => [
        'hour' => 2,
        'day'  => 10
    ]
]);

if (!ConfigProcessor::isValidateOk()) {
    die(join("<br />\n", $configProcessor->getErrors()));
}

$validatedConfig = $configProcessor->getValidatedValues();

$cfg['rateLimiterIPsWhiteList'] = array_unique(array_merge(
    $rateLimiterIPsWhiteListDefault,
    $validatedConfig['rateLimiterIPsWhiteList']
));

$cfg['recaptchaIPsWhiteList'] = array_unique(array_merge(
    $recaptchaIPsWhiteListDefault,
    $validatedConfig['recaptchaIPsWhiteList']
));

$cfg['isFuncoreLogoutRequired'] = $validatedConfig['isFuncoreLogoutRequired'];
$cfg['globalAffCookieSecPeriod'] = $validatedConfig['globalAffCookieSecPeriod'];
$cfg['enableCaptcha'] = $validatedConfig['enableCaptcha'];
$cfg['captchaConfig'] = $validatedConfig['captchaConfig'];

$cfg['staticVersionURL'] = 'https://agstatic.com/project_version';

$cfg['numberOfAttempts2FA'] = 5;
$cfg['lockTime2FA'] = 5; // in minutes
$cfg['smsLimitAttempts'] = 5; // in minutes

$defaultCountryAgeBan = [
    'est' => 21
];

$cfg['countryAgeBan'] = !empty($cfg['countryAgeBan']) ? $cfg['countryAgeBan'] : $defaultCountryAgeBan;

$cfg['qtranslateMode'] = $cfg['qtranslateMode'] ?? 'query';

$cfg['newTempUsersEndpoint'] = true;

$cfg['fundistTidUUID'] = 1;

/*
 * REDIS
 */
if (!defined('REDIS_PREFIX'))
{
    $redisPrefix = '';
    if (!empty($_SERVER['REDIS_PREFIX']))
    {
        $redisPrefix = $_SERVER['REDIS_PREFIX'];
    }
    elseif (!empty($cfg['websiteName']))
    {
        $redisPrefix = preg_replace('/[^a-z0-9_]/i', '', $cfg['websiteName']);
    }

    if (empty($redisPrefix)) {
        $redisPrefix = 'WLC'.strtoupper($cfg['env']);
    }

    define('REDIS_PREFIX', $redisPrefix.':');
    unset($redisPrefix);
}

if (!defined('APC_PREFIX')) {
    define('APC_PREFIX', REDIS_PREFIX);
}

if ( !defined( 'REDIS_HOST' ) )
{
    define( 'REDIS_HOST', '127.0.0.1' );
}

if ( !defined( 'REDIS_PORT' ) )
{
    define( 'REDIS_PORT', 6379 );
}

require $cfg['lib'] . '/Raven_Stacktrace.php';
