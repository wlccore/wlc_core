<?php

namespace eGamings\WLC;

use eGamings\WLC\Classifier;

/**
 * Class States
 * @package eGamings\WLC
 */
class States {

    static $stateListFile = 'stateList.json';

    /**
     * @codeCoverageIgnore
     *
     * Sort state title by DESC
     * @private
     * @method sortByTitleDesc
     * @param {array} $a
     * @param {array} $b
     * @return {int}
     */
    private static function sortByTitleDesc($a, $b): int
    {
        return strcmp($b['Name'], $a['Name']);
    }

    /**
     * @codeCoverageIgnore
     *
     * Sort state title by ASC
     * @private
     * @method sortByTitleAsc
     * @param {array} $a
     * @param {array} $b
     * @return {int}
     */
    private static function sortByTitleAsc($a, $b): int
    {
        return strcmp($a['Name'], $b['Name']);
    }

    /**
     * Fetch countries states list
     * @param false $force
     * @return bool
     * @throws \Exception
     */
    public static function fetchStateList($force = false): bool
    {
        $file = _cfg('cache') . DIRECTORY_SEPARATOR . self::$stateListFile;
        $system = System::getInstance();

        // @codeCoverageIgnoreStart
        if (!$force && (file_exists($file) && filesize($file) != 0) && filemtime($file) + 43200 > time() && !isset($_GET['force'])) { //12 hours timer, no need to update the file
            return false;
        }
        // @codeCoverageIgnoreEnd

        $url = '/WLCClassifier/States/';
        $transactionId = $system->getApiTID($url);

        $hash = md5('WLCClassifier/States/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
        $params = [
            'TID' => $transactionId,
            'Hash' => $hash,
        ];

        $url .= '?&' . http_build_query($params);
        $response = $system->runFundistAPI($url);

        $result = explode(',', $response, 2);
        if ($result[0] !== '1') {
            return false;
        }

        // @codeCoverageIgnoreStart
        $data = json_encode(json_decode($result[1], true), JSON_UNESCAPED_UNICODE);
        return Utils::atomicFileReplace($file, $data);
        // @codeCoverageIgnoreEnd
    }

    /**
     * Get states list sorted by countries
     * @param string $orderBy
     * @param string $lang
     * @return array|false
     * @throws \Exception
     */
    public static function getStatesList($orderBy = 'asc', $lang = '')
    {
        if (!empty($_SERVER['TEST_RUN'])) {
            set_time_limit(0);
        }

        // @codeCoverageIgnoreStart
        $file = _cfg('cache') . DIRECTORY_SEPARATOR . self::$stateListFile;
        if (!file_exists($file) && !self::fetchStateList()) {
            return false;
        }

        $lang = $lang ?: _cfg('language');
        $statesList = Cache::result('statesList', function() use ($file, $orderBy, $lang) {
            $result = [];
            $countriesList = [];
            $excludeCountries = [];

            $statesList = json_decode(@file_get_contents($file), 1);

            $siteConfig = Config::getSiteConfig();

            if (!empty($siteConfig['exclude_countries'])) {
                $excludeCountries = $siteConfig['exclude_countries'];
            }

            $excludeStates = array_filter($excludeCountries, function($v) {
                return strpos($v, '-');
            });

            foreach ($excludeStates as $k => $v) {
                $v = explode('-', $v);

                if (isset($excludeStates[$v[0]]) && count($excludeStates[$v[0]]) > 0 ) {
                    array_push($excludeStates[$v[0]], $v[1]);
                } else {
                    $excludeStates[$v[0]] = [];
                    array_push($excludeStates[$v[0]], $v[1]);
                }
                unset($excludeStates[$k]);
            }

            $countriesList = Classifier::getCountryList();
            foreach ($countriesList as $v) {
                if (array_key_exists(strtoupper($v['iso2']), $excludeStates)) {
                    $excludeStates[$v['iso3']] = $excludeStates[strtoupper($v['iso2'])];
                    unset($excludeStates[strtoupper($v['iso2'])]);
                }
            }


            if (is_array($statesList)) {
                uasort($statesList, $orderBy == 'desc' ? 'static::sortByTitleDesc' : 'static::sortByTitleAsc');
                $statesMap = [];

                foreach ($statesList as $k => $v) {
                    foreach ($v as $state) {
                        if ($state['Lang'] == 'en') {
                            $statesMap[$k][$state['IsoCode']] = [
                                'value' => $state['IsoCode'],
                                'title' => $state['Name'],
                            ];
                        }
                    }
                }

                foreach ($statesList as $k => $v) {
                    foreach ($v as $state) {
                        if ($state['Lang'] == $lang) {
                            $statesMap[$k][$state['IsoCode']] = [
                                'value' => $state['IsoCode'],
                                'title' => $state['Name'],
                            ];
                        }
                    }
                }

                if (count($excludeStates) > 0) {
                    foreach ($statesMap as $k => $v) {
                        if (array_key_exists($k, $excludeStates)) {
                            foreach ($v as $name => $state) {
                                if (in_array($state['value'], $excludeStates[$k])) {
                                    unset($statesMap[$k][$name]);
                                }
                            }
                        }
                    }
                }

                foreach ($statesMap as $k => $v) {
                    foreach ($v as $state) {
                        $result[$k][] = $state;
                    }

                    if (empty($result[$k])) {
                        $result[$k][] = ['value' => 'FRBDN', 'title' => _('No states allowed')];
                    }
                }
            }

            return $result;
        }, 60, [$orderBy, $lang]);

        return $statesList;
        // @codeCoverageIgnoreEnd
    }
}
