<?php
namespace eGamings\WLC;

class Classifier {
    static $countryListFile = 'countryList.json';
    static $cloudflareIPList = 'cloudflareIPList.json';

    /**
     * Sort country title by DESC
     *
     * @private
     * @method sortByTitleDesc
     * @param {array} $a
     * @param {array} $b
     * @return {int}
     */
    static function sortByTitleDesc($a, $b) {
        return strcmp($b->Name, $a->Name);
    }

    /**
     * Sort country title by ASC
     *
     * @private
     * @method sortByTitleAsc
     * @param {array} $a
     * @param {array} $b
     * @return {int}
     */
    static function sortByTitleAsc($a, $b) {
        return strcmp($a->Name, $b->Name);
    }

    static function fetchCountryList($force = false) {
        $file = _cfg('cache') . DIRECTORY_SEPARATOR . self::$countryListFile;
        $system = System::getInstance();

        if (!$force && (file_exists($file) && filesize($file) != 0) && filemtime($file) + 43200 > time() && !isset($_GET['force'])) { //12 hours timer, no need to update the file
            return false;
        }

        $url = '/WLCClassifier/Countries/';

        $transactionId = $system->getApiTID($url);

        $hash = md5('WLCClassifier/Countries/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'TID' => $transactionId,
            'Hash' => $hash,
        );

        $url .= '?&' . http_build_query($params);

        $response = $system->runFundistAPI($url);

        $result = explode(',', $response, 2);
        if ($result[0] !== '1') {
            return false;
        }

        $data = json_encode(json_decode($result[1], true), JSON_UNESCAPED_UNICODE);

        return Utils::atomicFileReplace($file, $data);
    }

    static function getCountryList($orderBy = 'asc', $lang = '') {

        if(!empty($_SERVER['TEST_RUN'])) set_time_limit(0);

        $file = _cfg('cache') . DIRECTORY_SEPARATOR . self::$countryListFile;
        if (!file_exists($file) && !self::fetchCountryList()) {
            return false;
        }

        $lang = ($lang) ? $lang : _cfg('language');
        $countryList = Cache::result('countryList', function() use ($file, $orderBy, $lang) {
            $result = [];
            $siteConfig = Config::getSiteConfig();
            $excludeCountries = [];

            if (!empty($siteConfig['exclude_countries'])) {
                $excludeCountries = $siteConfig['exclude_countries'];
            }

            $countryList = json_decode(@file_get_contents($file));

            //Getting only countries with specific language
            if (is_array($countryList)) {
                if ($orderBy == 'desc') {
                    usort($countryList, 'static::sortByTitleDesc');
                } else {
                    usort($countryList, 'static::sortByTitleAsc');
                }

                $countryMap = [];
                foreach ($countryList as $k => $v) {
                    if ($v->Lang != 'en' ||  in_array($v->Iso2, $excludeCountries) || in_array($v->Iso3, $excludeCountries)) {
                        continue;
                    }

                    $countryMap[$v->Iso3] = array(
                        'value' => $v->Iso3,
                        'iso3' => $v->Iso3,
                        'iso2' => $v->Iso2,
                        'title' => $v->Name,
                        'phoneCode' => $v->PhoneCode
                    );
                }

                foreach ($countryList as $k => $v) {
                    if ($v->Lang == $lang && !in_array($v->Iso2, $excludeCountries)
                            && !in_array($v->Iso3, $excludeCountries) ) {
                                $countryMap[$v->Iso3]= array(
                                    'value' => $v->Iso3,
                                    'iso3' => $v->Iso3,
                                    'iso2' => $v->Iso2,
                                    'title' => $v->Name,
                                    'phoneCode' => $v->PhoneCode
                                );
                            }
                }
                $result = array_values($countryMap);
            }

            return $result;
        }, 60, [$orderBy, $lang]);

        return $countryList;
    }

    static function getCountryCodes($orderBy = 'asc', $lang = '') {
        $countryList = self::getCountryList($orderBy, $lang);
        return array_map(function($c) {
            return $c['value'];
        }, is_array($countryList) ? $countryList : []);
    }

    static function fetchCloudflareIPList() {
        $file = _cfg('cache') . DIRECTORY_SEPARATOR . self::$cloudflareIPList;
        if ((file_exists($file) && filesize($file) != 0) && filemtime($file) + 86400 > time() && !isset($_GET['force'])) {
            //24 hours timer, no need to update the file
            return false;
        }

        $response = file_get_contents('https://www.cloudflare.com/ips-v4');
        if($response === false)
            return false;
        $response = explode("\n",strip_tags($response));
        $ranges = [];
        foreach($response as $cidr){
            if(!empty($cidr)){
                $cidr = explode('/', $cidr);
                if(count($cidr)==2){
                    $from = (ip2long($cidr[0])) & ((-1 << (32 - (int)$cidr[1])));
                    $to = (ip2long($cidr[0])) + pow(2, (32 - (int)$cidr[1])) - 1;
                    $ranges[] = ['from' => $from, 'to' => $to];
                }
            }
        }
        if(empty($ranges))
            return false;
        $data = json_encode($ranges);

        return Utils::atomicFileReplace($file, $data);
    }

    static function getCloudflareIPList() {
        $file = _cfg('cache') . DIRECTORY_SEPARATOR . self::$cloudflareIPList;
        if (!file_exists($file) && !self::fetchCloudflareIPList()) {
            return false;
        }
        return json_decode(@file_get_contents($file),true);
    }
}
