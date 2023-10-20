<?php
namespace eGamings\WLC;

use League\ISO3166\ISO3166;
use GeoIp2\Database\Reader;

/**
 * Class wrapper for GeoIp2 database reader
 * @link https://github.com/maxmind/GeoIP2-php description for module
 *
 * Class GeoIp
 * @package eGamings\WLC
 */
class GeoIp
{

    /**
     *  Allowable types of model
     */
    const MODELS = [
        'country', 'city'
    ];

    /**
     * Array of lang-Country
     *
     * @var array
     */
    public static $locales = [
        'AE' => 'ar', 'AF' => 'ps', 'AL' => 'sq', 'AM' => 'hy', 'AR' => 'es', 'AT' => 'de', 'AU' => 'en',
        'BA' => 'hr', 'BD' => 'bn', 'BE' => 'nl', 'BG' => 'bg', 'BH' => 'ar', 'BN' => 'ms', 'BO' => 'es', 'BR' => 'pt', 'BY' => 'ru', 'BZ' => 'en',
        'CA' => 'fr', 'CH' => 'it', 'CL' => 'es', 'CN' => 'zh', 'CO' => 'es', 'CR' => 'es', 'CZ' => 'cs',
        'DE' => 'de', 'DK' => 'da', 'DO' => 'es', 'DZ' => 'ar',  'EC' => 'es', 'EE' => 'et', 'EG' => 'ar', 'ES' => 'es', 'ET' => 'am',
        'FI' => 'fi', 'FO' => 'fo', 'FR' => 'fr', 'GB' => 'en', 'GE' => 'ka', 'GL' => 'kl', 'GR' => 'el', 'GT' => 'es',
        'HK' => 'zh', 'HN' => 'es', 'HR' => 'hr', 'HU' => 'hu', 'ID' => 'id', 'IE' => 'en', 'IL' => 'he', 'IN' => 'hi', 'IQ' => 'ar', 'IR' => 'fa', 'IS' => 'is', 'IT' => 'it',
        'JM' => 'en', 'JO' => 'ar', 'JP' => 'ja', 'KE' => 'sw', 'KG' => 'ky', 'KH' => 'km', 'KR' => 'ko', 'KW' => 'ar', 'KZ' => 'ru',
        'LA' => 'lo', 'LB' => 'ar', 'LI' => 'de', 'LK' => 'si', 'LT' => 'lt', 'LU' => 'lb', 'LV' => 'lv', 'LY' => 'ar',
        'MA' => 'ar', 'MC' => 'fr', 'MK' => 'mk', 'MN' => 'mn', 'MO' => 'zh', 'MT' => 'mt', 'MV' => 'dv', 'MX' => 'es', 'MY' => 'ms',
        'NG' => 'yo', 'NI' => 'es',  'NL' => 'nl', 'NO' => 'no', 'NP' => 'ne', 'NZ' => 'mi', 'OM' => 'ar',
        'PA' => 'es', 'PE' => 'es', 'PH' => 'fil', 'PK' => 'ur', 'PL' => 'pl', 'PR' => 'es', 'PT' => 'pt', 'PY' => 'es',
        'QA' => 'ar', 'RO' => 'ro', 'RU' => 'ru', 'RW' => 'rw', 'SA' => 'ar', 'SE' => 'sv', 'SG' => 'zh', 'SI' => 'sl', 'SK' => 'sk', 'SN' => 'wo', 'SV' => 'es', 'SY' => 'ar',
        'TH' => 'th', 'TM' => 'tk', 'TN' => 'ar', 'TR' => 'tr', 'TT' => 'en', 'TW' => 'zh',
        'UA' => 'ru', 'US' => 'es', 'UY' => 'es', 'VE' => 'es', 'VN' => 'vi', 'YE' => 'ar', 'ZA' => 'en', 'ZW' => 'en',
    ];

    /**
     * @var \GeoIp2\Database\Reader
     */
    private $reader;

    /**
     * Type of model wich get
     * Available variants {@see GeoIp::MODELS}
     *
     * @var string
     */
    private $type;

    /**
     * GeoIp constructor.
     *
     * @param $filename path to database
     * @param $type string type of model {@see GeoIp::MODELS}
     * @param array $locales
     */
    public function __construct($filename, $type, $locales = ['en'])
    {
        try {
            $this->reader = new Reader($filename, $locales);
            $this->type = $type;
        } catch (\Exception $ex) {
            $error = self::errorMsg(__LINE__, __METHOD__, $ex->getMessage());
        }
    }

    /**
     * Get country|city information
     *
     * @param string $ip IP Address
     * @return \GeoIp2\Model\Country|\GeoIp2\Model\City|false
     */
    public function get($ip)
    {
        if (!in_array($this->type, self::MODELS) || is_null($this->reader) || self::isLocalIp($ip)) {
            return false;
        }

        try {
            $model = $this->type;
            $record = $this->reader->$model($ip);
        } catch (\Exception $ex) {
            $error = self::errorMsg(__LINE__, __METHOD__, $ex->getMessage());
            return false;
        }

        return $record;
    }

    /**
     * Check that ip is local
     *
     * @param string $ip IP Address
     * @return boolean
     */
    static function isLocalIp($ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }
        return false;
    }

    /**
     * Get three-character country code (ISO 3166-3 alpha)
     *
     * @param $isoCode two-character country code (ISO 3166-1 alpha)
     *
     * @return string
     */
    static function countryIso3($isoCode)
    {
        static $iso = null;

        if (empty($isoCode)) {
            return '';
        }

        try {
            if ($iso === null) {
                $iso = new ISO3166();
            }

            $data = $iso->alpha2($isoCode);
        } catch (\Exception $ex) {
            $error = self::errorMsg(__LINE__, __METHOD__, $ex->getMessage());
            return '';
        }

        return strtolower($data['alpha3']);
    }

    /**
     * Get country information by code (ISO 3166-3 alpha)
     *
     * @param $isoCode three character country code
     *
     * @return array
     */
    static function countryIsoData($isoCode3)
    {
        static $iso = null;
        $data = [];

        try {
            if (!empty($isoCode3)) {
                if ($iso === null) {
                    $iso = new ISO3166();
                }

                $data = $iso->alpha3($isoCode3);
            }
        } catch (\Exception $ex) {
            $error = self::errorMsg(__LINE__, __METHOD__, $ex->getMessage());
        }

        return $data;
    }

    /**
     * Get language by country
     *
     * @param $country Country code 2 chars
     *
     * @return array
     */
    static function countryLanguage($country) {
        $country = strtoupper($country);
        $locale = null;

        if (!empty(self::$locales[$country])) {
            $locale = self::$locales[$country];
        }

        return $locale;
    }

    /**
     * Get error message
     *
     * @param $line
     * @param $method
     * @param $msg
     * @return string
     */
    private static function errorMsg($line, $method, $msg)
    {
        $error = '[' . $method . ':' . $line. ']: ' . $msg;
        error_log($error);
        return $error;
    }
}
