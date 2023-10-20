<?php
namespace eGamings\WLC;

use Symfony\Component\Yaml\Yaml;

class Config {
    static $_siteConfigFile =  'siteConfig.json';

    public static $lifeTime = 43200; // 12 hours

    /**
     * Get flattered name of configuration
     *
     * @param string|array $name Variable name
     * @return string flattered string value
     */
    static function getName($name) {
        $result = $name;

        if (is_array($name)) {
            $result = implode('.', $name);
        }

        return $result;
    }

    /**
     * Get configuration value
     *
     * @param string|array $name
     * @return NULL|unknown
     */
    static function get($name) {
        global $cfg;

        $cfgName = self::getName($name);

        if (!isset($cfg[$cfgName])) {
            return null;
        }

        return $cfg[$cfgName];
    }

    /**
     * Set configuration variable
     *
     * @param string|array $name Name of configuration key
     * @param unknown $value Value to set
     * @return unknown setted configuration value
     */
    static function set($name, $value) {
        global $cfg;

        $cfgName = self::getName($name);

        if (is_null($value)) {
            unset($cfg[$cfgName]);
        } else {
            $cfg[$cfgName] = $value;
        }
        return $value;
    }

    /**
     * Flattern config array
     *
     * @param array $conf
     * @param string $prefix
     * @return array
     */
    static function flatternArray($conf, $prefix = '') {
        $result = [];
        foreach($conf as $confItemKey => $confItemValue) {
            $iterResult = [];
            if (is_array($confItemValue)) {
                $iterResult = self::flatternArray($confItemValue, $prefix . $confItemKey . '.');
            } else {
                $iterResult[$prefix . $confItemKey] = $confItemValue;
                $oldConfItemKey = ucwords($prefix . $confItemKey, '.');
                $oldConfItemKey = str_replace('.', '', $oldConfItemKey);
                $oldConfItemKey = lcfirst($oldConfItemKey);
                $iterResult[$oldConfItemKey] = $confItemValue;
            }
            $result += $iterResult;
        }
        return $result;
    }
    /**
     * Load custom config from env folder
     *
     * @return boolean
     */
    static function load() {
        foreach([
            $_ENV,
            $_SERVER
        ] as $env) {
            $customConfigPath = $env['WLC_CONFIG_OVERRIDE'] ?? '';

            if ($customConfigPath !== '') {
                foreach(self::loadConfigFromFS($customConfigPath) as $configDataKey => $configDataValue) {
                    self::set($configDataKey, $configDataValue);
                }
            }
        }

        return true;
    }

    protected static function loadConfigFromFS(string $path): array
    {
        $files = [];
        if (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) == 'yaml') {
            $files[] = $path;
        } else if (is_dir($path)) {
            $files = glob($path . DIRECTORY_SEPARATOR . '*.config.yaml');
        }

        $configData = [];
        foreach($files as $file) {
            $fileData = Yaml::parseFile($file, Yaml::PARSE_OBJECT);
            if (is_array($fileData)) {
                $configData += self::flatternArray($fileData);
            }
        }

        return $configData;
    }

    /**
     * Fetch siteconfig
     *
     * @param string $force
     * @return boolean|unknown
     */
    static function fetchSiteConfig($force = false) {
        $file = _cfg('cache') . DIRECTORY_SEPARATOR . self::$_siteConfigFile;
        $system = System::getInstance();

        if (!$force && !isset($_GET['force']) && self::isConfigFileValidByTimeFrame()) {
            //12 hours timer, no need to update the file or if GET force not set
            return false;
        }

        $url = '/WLCClassifier/SiteConfig/';
        $transactionId = $system->getApiTID($url);

        $hash = md5($url.'/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
        $params = array(
            'TID' => $transactionId,
            'Hash' => $hash,
            'refresh' => $force || isset($_GET['force'])
        );

        $url .= '?&' . http_build_query($params);

        $response = $system->runFundistAPI($url);
        $results = explode(',', ''.$response, 2);

        if ($results[0] !== '1' || empty($results[1]))
        {
            return false;
        }

        $siteConfig = json_decode($results[1], true);
        if (!is_array($siteConfig) || empty($siteConfig)) {
            return false;
        }

        $exclude_currencies = _cfg('exclude_currencies');
        if (!empty($exclude_currencies) && is_array($exclude_currencies)) {
            $exclude_currencies = array_map('strtoupper', $exclude_currencies);
        } else {
            $exclude_currencies = [];
        }

        $siteConfig['currencies'] = !empty($siteConfig['currencies']) ? self::filterCurrencies($siteConfig['currencies'], $exclude_currencies) : [];

        self::setExcludeCountries($siteConfig);

        $whitelist_countries = _cfg('whitelist_countries') ?: [];
        if (!empty($whitelist_countries)) {
            $siteConfig['exclude_countries']= array_values(array_unique(array_filter($siteConfig['exclude_countries'], function($v) use ($whitelist_countries) {
                return !in_array($v, $whitelist_countries);
            })));
        }

        $data = json_encode($siteConfig, JSON_UNESCAPED_UNICODE);

        return Utils::atomicFileReplace($file, $data);
    }

    public static function filterCurrencies(array $rawCurrencies, array $filter): array
    {
        $ca = _cfg('currency_aliases'); // Currency aliases
        foreach ($rawCurrencies as $k => $v) {
            if (in_array($v['Name'], $filter)) {
                unset($rawCurrencies[$k]);
                continue;
            }
            $v['Alias'] = (!empty($v['Alias'])) ? $v['Alias'] : $v['Name'];
            $rawCurrencies[$k]['Alias'] = (!empty($ca[$v['Name']])) ? $ca[$v['Name']] : $v['Alias'];
        }

        return $rawCurrencies;
    }

    static function getSiteConfig() {
        static $siteConfig = null;
        if ($siteConfig !== null) {
            return $siteConfig;
        }

        $file = _cfg('cache') . DIRECTORY_SEPARATOR . self::$_siteConfigFile;
        if (!file_exists($file) && !self::fetchSiteConfig()) {
            return false;
        }

        self::checkFileTime($file);

        $siteConfig = json_decode(@file_get_contents($file), true);
        return $siteConfig;
    }

    static function checkFileTime($file = null, $lifeTime = 43200) {
        if (!empty($_SERVER['TEST_RUN'])) {
            return true;
        }

        $result = true;
        if (!self::isConfigFileValidByTimeFrame($file, $lifeTime)) {
            $result = self::fetchSiteConfig(true);
        }
        return $result;
    }

    public static function isConfigFileValidByTimeFrame(?string $file = null, int $customLifeTime = 0): bool
    {
        $file = $file ?? _cfg('cache') . DIRECTORY_SEPARATOR . self::$_siteConfigFile;
        $lifeTime = !$customLifeTime ? self::$lifeTime : $customLifeTime;

        return file_exists($file) && (time() - filemtime($file) < $lifeTime);
    }

    /**
     * @param array $siteConfig
     */
    public static function setExcludeCountries(array &$siteConfig): void
    {
        if (_cfg('ignoreAnyExcludeCountries')) {
            $siteConfig['exclude_countries'] = [];
        }

        $envGEC = isset($_ENV['GLOBAL_EXCLUDE_COUNTRIES']) ? explode(',', $_ENV['GLOBAL_EXCLUDE_COUNTRIES']) : [];
        $serverGEC = isset($_SERVER['GLOBAL_EXCLUDE_COUNTRIES']) ? explode(',', $_SERVER['GLOBAL_EXCLUDE_COUNTRIES']) : [];
        $globalExcludeCountries = !empty($envGEC) ? $envGEC : (!empty($serverGEC) ? $serverGEC : []);

        $excludeCountries = (null !== _cfg('exclude_countries') && is_array(_cfg('exclude_countries')))
            ? _cfg('exclude_countries')
            : [];

        $siteConfig['exclude_countries'] = (isset($siteConfig['exclude_countries']) && is_array($siteConfig['exclude_countries']))
            ? $siteConfig['exclude_countries']
            : [];

        if (!isset($siteConfig['force_exclude_countries']) || !$siteConfig['force_exclude_countries']) {
            $siteConfig['exclude_countries'] = array_values(array_unique(array_merge($siteConfig['exclude_countries'], $excludeCountries)));
            if (isset($siteConfig['Type']) && $siteConfig['Type'] === 'wlc') {
                $siteConfig['exclude_countries'] = array_values(array_unique(array_merge($siteConfig['exclude_countries'], $globalExcludeCountries)));
            }
        }
    }
}
