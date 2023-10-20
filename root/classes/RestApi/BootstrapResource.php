<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\Api;
use eGamings\WLC\Banners;
use eGamings\WLC\Cache;
use eGamings\WLC\Config;
use eGamings\WLC\Front;
use eGamings\WLC\Games;
use eGamings\WLC\Social;
use eGamings\WLC\System;
use eGamings\WLC\Seo;
use eGamings\WLC\GeoIp;

/**
 * @SWG\Tag(
 *     name="bootstrap",
 *     description="Bootstrap"
 * )
 */

/**
 * @SWG\Definition(
 *     definition="Bootstrap",
 *     description="Bootstrap",
 *     type="object",
 *     @SWG\Property(
 *         property="banners",
 *         type="array",
 *         @SWG\Items(ref="#/definitions/bannerList")
 *     ),
 *     @SWG\Property(
 *         property="env",
 *         type="string",
 *         description="Environment"
 *     ),
 *     @SWG\Property(
 *         property="footerText",
 *         type="object",
 *         description="Footer text",
 *         example={"en": "Text", "ru": "Text"}
 *     ),
 *     @SWG\Property(
 *         property="language",
 *         type="string",
 *         description="Current language",
 *         example="ru"
 *     ),
 *     @SWG\Property(
 *         property="country",
 *         type="string",
 *         description="User alpha3 country code",
 *         example="rus"
 *     ),
 *     @SWG\Property(
 *         property="country2",
 *         type="string",
 *         description="User alpha2 country code",
 *         example="ru"
 *     ),
 *     @SWG\Property(
 *         property="languages",
 *         type="array",
 *         description="Available languages",
 *         example={{"code": "en", "label": "English"}},
 *         @SWG\Items(
 *             type="object"
 *         )
 *     ),
 *     @SWG\Property(
 *         property="loggedIn",
 *         type="array",
 *         description="User is authorized",
 *         enum={"0", "1"},
 *         @SWG\Items(
 *             type="integer"
 *         )
 *     ),
 *     @SWG\Property(
 *         property="menu",
 *         type="array",
 *         description="Menu",
 *         example={{"menuId": "popular", "menuName": {"en": "Popular"}}, {"menuId": "slots", "menuName": {"en": "Slots"}}},
 *         @SWG\Items(
 *             type="object"
 *         )
 *     ),
 *     @SWG\Property(
 *         property="site",
 *         type="string",
 *         description="Site URL"
 *     ),
 *     @SWG\Property(
 *         property="siteconfig",
 *         type="object",
 *         description="Site config"
 *     ),
 *     @SWG\Property(
 *         property="socialNetworks",
 *         type="array",
 *         @SWG\Items(ref="#/definitions/socialNetwork")
 *     ),
 *     @SWG\Property(
 *         property="user",
 *         type="object",
 *         description="User fields"
 *     ),
 *     @SWG\Property(
 *         property="userCountry",
 *         type="string",
 *         description="User country",
 *         example="rus"
 *     ),
 *     @SWG\Property(
 *         property="userCountryForbidden",
 *         type="boolean",
 *         description="User country forbidden",
 *         example=false
 *     ),
 *     @SWG\Property(
 *         property="seo",
 *         ref="#/definitions/seo"
 *     ),
 * )
 */

/**
 * @class BootstrapResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Cache
 * @uses eGamings\WLC\Social
 * @uses eGamings\WLC\Games
 * @uses eGamings\WLC\Front
 * @uses eGamings\WLC\Config
 * @uses eGamings\WLC\Cache
 */
class BootstrapResource extends AbstractResource {

    protected $countriesLangs = [
        "aus" => ["en"],
        "aut" => ["de"],
        "aze" => ["az"],
        "alb" => ["sq"],
        "dza" => ["ar"],
        "ago" => ["pt"],
        "and" => ["ca"],
        "atg" => ["en"],
        "arg" => ["es"],
        "arm" => ["hy"],
        "afg" => ["ps"],
        "bhs" => ["en"],
        "bgd" => ["en", "bn"],
        "brb" => ["en"],
        "bhr" => ["ar"],
        "blz" => ["en"],
        "blr" => ["ru", "be"],
        "bel" => ["de", "fr", "nl"],
        "ben" => ["fr"],
        "bgr" => ["bg"],
        "bol" => ["es", "ay", "qu"],
        "bih" => ["bs", "sr", "hr"],
        "bwa" => ["en"],
        "bra" => ["pt-br"],
        "brn" => ["en", "ms"],
        "bfa" => ["fr"],
        "bdi" => ["fr"],
        "btn" => ["dz"],
        "vut" => ["en", "fr", "bi"],
        "vat" => ["la", "it"],
        "gbr" => ["en"],
        "hun" => ["hu"],
        "ven" => ["es"],
        "tls" => ["pt"],
        "vnm" => ["vi"],
        "gab" => ["fr"],
        "hti" => ["fr"],
        "guy" => ["en"],
        "gmb" => ["en"],
        "gha" => ["en"],
        "gtm" => ["es"],
        "gin" => ["fr"],
        "gnb" => ["pt"],
        "deu" => ["de"],
        "hnd" => ["es"],
        "grd" => ["en"],
        "grc" => ["el"],
        "geo" => ["ka"],
        "dnk" => ["da"],
        "dji" => ["ar", "fr"],
        "dma" => ["en"],
        "dom" => ["es"],
        "cod" => ["fr"],
        "egy" => ["ar"],
        "zmb" => ["en"],
        "zwe" => ["en"],
        "isr" => ["ar", "he"],
        "ind" => ["en", "hi"],
        "idn" => ["id"],
        "jor" => ["ar"],
        "irq" => ["ar", "ku"],
        "irn" => ["fa"],
        "irl" => ["en", "ga"],
        "isl" => ["is"],
        "esp" => ["es"],
        "ita" => ["it"],
        "yem" => ["ar"],
        "cpv" => ["pt"],
        "kaz" => ["kk"],
        "khm" => ["km"],
        "cmr" => ["en", "fr"],
        "can" => ["en", "fr"],
        "qat" => ["ar"],
        "ken" => ["en", "sw"],
        "cyp" => ["el", "tr"],
        "kgz" => ["ky"],
        "kir" => ["en"],
        "prk" => ["ko"],
        "chn" => ["zh"],
        "col" => ["es"],
        "com" => ["ar", "fr"],
        "cri" => ["es"],
        "cub" => ["es"],
        "kwt" => ["ar"],
        "lao" => ["lo"],
        "lva" => ["lv"],
        "lso" => ["en"],
        "lbr" => ["en"],
        "lbn" => ["ar"],
        "lby" => ["ar"],
        "ltu" => ["lt"],
        "lie" => ["de"],
        "lux" => ["de", "fr"],
        "mus" => ["en"],
        "mrt" => ["ar"],
        "mdg" => ["fr", "mg"],
        "mkd" => ["mk"],
        "mwi" => ["en"],
        "mli" => ["fr"],
        "mdv" => ["dv"],
        "mlt" => ["en", "mt"],
        "mar" => ["ar"],
        "mhl" => ["en"],
        "mex" => ["es"],
        "fsm" => ["en"],
        "moz" => ["pt"],
        "mco" => ["fr"],
        "mng" => ["mn"],
        "nam" => ["en"],
        "nru" => ["en"],
        "ner" => ["fr"],
        "nga" => ["en", "fr"],
        "nld" => ["nl"],
        "nic" => ["es"],
        "nzl" => ["en"],
        "nor" => ["no"],
        "are" => ["ar"],
        "omn" => ["ar"],
        "pak" => ["en"],
        "plw" => ["en"],
        "pan" => ["es"],
        "png" => ["en"],
        "pry" => ["es", "gn"],
        "per" => ["es", "ay", "qu"],
        "pol" => ["pl"],
        "prt" => ["pt"],
        "cog" => ["fr"],
        "kor" => ["ko"],
        "rus" => ["ru"],
        "rwa" => ["en", "fr"],
        "rou" => ["ro"],
        "slv" => ["es"],
        "wsm" => ["en", "sm"],
        "smr" => ["it"],
        "stp" => ["pt"],
        "sau" => ["ar"],
        "swz" => ["en"],
        "syc" => ["en", "fr"],
        "sen" => ["fr"],
        "vct" => ["en"],
        "kna" => ["en"],
        "lca" => ["en"],
        "srb" => ["sr"],
        "sgp" => ["en", "ms", "zh"],
        "syr" => ["ar"],
        "svk" => ["sk"],
        "svn" => ["sl"],
        "slb" => ["en"],
        "som" => ["ar"],
        "sdn" => ["ar"],
        "sur" => ["nl"],
        "usa" => ["en"],
        "sle" => ["en"],
        "tjk" => ["tg"],
        "tha" => ["th"],
        "tza" => ["en", "sw"],
        "tgo" => ["fr"],
        "ton" => ["en"],
        "tto" => ["en"],
        "tuv" => ["en"],
        "tun" => ["ar"],
        "tkm" => ["tk"],
        "tur" => ["tr"],
        "uga" => ["en"],
        "uzb" => ["uz"],
        "ukr" => ["uk"],
        "ury" => ["es"],
        "fji" => ["en"],
        "phl" => ["tl"],
        "fin" => ["fi", "sv"],
        "fra" => ["fr"],
        "hrv" => ["hr"],
        "caf" => ["fr"],
        "tcd" => ["fr"],
        "mne" => ["sq", "sr"],
        "cze" => ["cs"],
        "chl" => ["es"],
        "che" => ["de", "fr", "it"],
        "swe" => ["sv"],
        "lka" => ["en"],
        "ecu" => ["es"],
        "gnq" => ["es", "fr"],
        "eri" => ["ar"],
        "est" => ["et"],
        "zaf" => ["en", "af"],
        "jam" => ["en"],
        "jpn" => ["ja"]
    ];

    /**
     * @SWG\Get(
     *     path="/bootstrap",
     *     description="Site bootstrap",
     *     tags={"bootstrap"},
     *     @SWG\Parameter(
     *         name="refresh",
     *         in="query",
     *         type="boolean",
     *         description="Remove from the cache games top menu",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Successful operation",
     *         @SWG\Property(
     *             ref="#/definitions/Bootstrap"
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * Bootstrap
     *
     * @public
     * @method get
     * @param {array} $request
     * @param {array} $query
     * @param {array} $params
     * @return {array}
     */
    public function get($request, $query = [], $params = []): void
    {
        $bootstrapData = $this->buildBootstrap($request, $query, $params);
        $this->flushBootstrap($bootstrapData);
    }

    function buildBootstrap($request, $query = [], $params = []): string
    {
        $allowedLanguages = _cfg('allowedLanguages');
        $languagesInfo = _cfg('languagesInfo');
        $languages = [];
        $slim = (bool) ($query['slim'] ?? false);

        if (!is_array($languagesInfo)) $languagesInfo = [];

        foreach($languagesInfo as $languageId => $languageInfo) {
            if (isset($allowedLanguages[$languageId])) {
                $languages[] = $languageInfo;
            }
        }

        $siteConfig = Cache::result('siteConfig', function() {
            return Config::getSiteConfig();
        }, 60);

        $siteConfig['registerGeneratePassword'] = _cfg('registerGeneratePassword');
        $siteConfig['fastRegistration'] = _cfg('fastRegistration');
        $siteConfig['skipPassCheckOnFirstSession'] = _cfg('skipPassCheckOnFirstSession');
        $siteConfig['termsOfService'] = _cfg('termsOfService');
        $siteConfig['useMetamask'] = _cfg('useMetamask');
        $siteConfig['allowEmailChange'] = _cfg('allowEmailChange');

        if (_cfg('requiredFieldsList')) {
            $user = new \eGamings\WLC\User();
            $siteConfig['systemsGamePlayInfo'] = $user->addRequiredFieldsResources($siteConfig['systemsGamePlayInfo'], 'Fields');
        }

        if (_cfg('maxFileSize')) {
            $siteConfig = array_merge($siteConfig,["verification" => [
                "maxFileSize" => _cfg('maxFileSize')
            ]]);
        }

        $siteConfigLanguages = Cache::result('siteConfigLanguages', function() use ($siteConfig) {
            $languages = [];
            if (!empty($siteConfig) && !empty($siteConfig['languages'])) {
                foreach($siteConfig['languages'] as $language) {
                    $languages[] = [
                        'code' => $language['Code'],
                        'label' => $language['Name']
                    ];
                }
            }
            return $languages;
        }, 60);

        if (!empty($siteConfigLanguages)) {
            $languages = $siteConfigLanguages;
        }

        if (!$slim) {
            $gamesMenuKey = 'gamesTopMenu';
            if (!empty($query['refresh'])) {
                Cache::del($gamesMenuKey);
            }

            $gamesMenu = Cache::result($gamesMenuKey, function() {
                $games = new Games();
                $menu = [];

                $gamesData = $games->getGamesData();

                if (!is_object($gamesData) || empty($gamesData->categories)) {
                    return $menu;
                }
                $gamesCategories = $gamesData->categories;

                foreach($gamesCategories as $gamesCategory) {
                    if (empty($gamesCategory['Tags']) || !is_array($gamesCategory['Tags']) || !in_array('menu', $gamesCategory['Tags'])) {
                        continue;
                    }

                    $menuId = preg_replace('/[^a-z0-9]/i', '', strtolower($gamesCategory['Name']['en']));
                    if ($menuId != '') {
                        $menu[] = [
                            'menuId' => $menuId,
                            'menuName' => $gamesCategory['Name']
                        ];
                    }
                }

                return $menu;
            }, 60);

            $user = Front::User();
            if (is_object($user)) {
                $user = (array) $user;
                unset($user['api_password'], $user['password']);
            }

            if (_cfg('requiredRegisterCheckbox')) {
                foreach (_cfg('requiredRegisterCheckbox') as $checkbox) {
                    unset($user[$checkbox], $user[$checkbox . 'Date']);
                }
            }
        } else {
            unset($siteConfig['languages']);

            foreach ($siteConfig['systemsGamePlayInfo'] ?? [] as $systemId => $systemData) {
                if (empty($systemData['Fields'])) {
                    unset($siteConfig['systemsGamePlayInfo'][$systemId]);
                }
            }
        }

        $footerText = [];
        if (!empty($siteConfig['footerText'])) {
            $footerText = $siteConfig['footerText'];
            unset($siteConfig['footerText']);
        }

        $exclude = empty($siteConfig['exclude_countries']) ? [] : $siteConfig['exclude_countries'];
        $country = System::getGeoData();
        $countryData = GeoIp::countryIsoData($country);
        $region = System::getGeoCityData();

        $staticVersion = Cache::result('staticVersion', function() {
            $url = defined('KEEPALIVE_PROXY') ? str_replace('https://', KEEPALIVE_PROXY . '/', _cfg('staticVersionURL')) : _cfg('staticVersionURL');
            return trim(file_get_contents($url));
        }, 60);

        $data = (object) [
            'version' => WLCCORE_VERSION,
            'staticVersion' => $staticVersion,
            'loggedIn' => (intval(Front::User('id')) > 0) ? '1' : '0',
            'site' => _cfg('site'),
            'language' => _cfg('language'),
            'locale' => $allowedLanguages[_cfg('language')] ?? '',
            'languages' => $languages,
            'siteconfig' => $siteConfig,
            'country' => $country,
            'country2' => (!empty($countryData['alpha2'])) ? strtolower($countryData['alpha2']) : substr($country, 0, 2),
            'regionIsoCode' => $region,
            'countryLangs' => $this->countriesLangs[$country],
            'countryRestricted' => in_array($country,$exclude),
            'env' => _cfg('env'),
            'mobile' => (_cfg('mobileDetected')) ? true : false,
            'footerText' => $footerText,
            'sessionName' => ini_get('session.name'),
            'hideEmailExistence' => (!empty(_cfg('hideEmailExistence'))) ? _cfg('hideEmailExistence') : false,
            'useRecaptcha' => (!empty(_cfg('recaptcha'))) ? _cfg('recaptcha') : false,
            'smsEnabled' => (bool)(_cfg('smsConfig') ?? false),
            'gameFileTime' => Games::getGameFilesTime(),
            'countryAgeBan' => _cfg('countryAgeBan'),
        ];

        if (!$slim) {
            $data->menu = $gamesMenu;
            $data->user = $user;
            $data->banners = BannersResource::getBanners();
            $data->socialNetworks = SocialNetworksResource::getSocialNetworks();
            $data->seo = SeoResource::getSeo();
        }

        // Run bootstrap hook after data is fully prepared
        $hookData = System::hook('api:bootstrap', $data);
        if (!empty($hookData)) {
            if (is_object($hookData)) {
                $data = (object)array_merge((array)$data, (array)$hookData);
            }
            elseif (is_array($hookData)) {
                foreach ($hookData as $hd) {
                    $data = (object)array_merge((array)$data, (array)$hd);
                }
            }
        }

        return json_encode((array) $data, JSON_UNESCAPED_UNICODE) ?: '';
    }

    public function flushBootstrap(string $bootstrapData): void
    {
        
        header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate, private');
        header('Content-Type: application/json; encoding=utf-8');
        $output = $bootstrapData;
        if (stripos($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip') !== false) {
            header('Content-Encoding: gzip');
            $output = gzencode($bootstrapData);
        }

        header('Content-Length: '. strlen($output));
        print $output;

        exit;
    }
}
