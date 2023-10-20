<?php
namespace eGamings\WLC;

use Dompdf\Dompdf;
use eGamings\WLC\Loyalty;
use eGamings\WLC\Banners;
use eGamings\WLC\GeoIp;
use eGamings\WLC\RestApi\ApiEndpoints;
use eGamings\WLC\RestApi\TempUsersResource;
use phpDocumentor\Reflection\Types\Boolean;
use Ramsey\Uuid\Uuid;

class System {

    public $data;
    public $allowedPlatforms;
    public $allowedLanguages;
    public $db;
    public $cfg;
    public $user;
    public $apiReqStartTime;
    public $apiTID = '';
    public $apiLogID = 0;
    public $logged_in = 0;

    private static $ravenClient = null;
    private static $Redis = null;

    public static $instance = null;
    public static $coreInstance = null;

    const LOGMSG_LIMIT = 1024;

    public function __construct()
    {
        if (self::$instance === null) {
            self::$instance = $this;
            self::$coreInstance = Core::getInstance();
        }
    }

    static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    static function hook($hook)
    {
        $hooks = _cfg('hooks');

        if (!is_array($hooks) || !isset($hooks[$hook]) || !is_array($hooks[$hook])) return;
        $func_args = func_get_args();
        $hook_args = array_splice($func_args, 1);
        $res = [];
        foreach ($hooks[$hook] as $hook_func) {
            if (!is_callable($hook_func)) {
                $res[] = null;
                continue;
            }
            $res[] = call_user_func_array($hook_func, $hook_args);
        }
        return (count($res) > 1) ? $res : $res[0];
    }

    public function trading()
    {
        $this->data->page = _cfg('page');
        $this->startGetText();
        $template = new Template();
        //@TODO Check THIS! - Classifier::getCountryList()
        $this->countryList = $template->getCountryList($this->user->country);
        return $this;
    }

    static function getGeoData()
    {
        static $geoCache = null;
        if (!empty($geoCache)) {
            return $geoCache;
        }

        $countryCode3 = _cfg('userCountry');
        if (_cfg('env') != 'dev') {
            $ip = System::getUserIP();
        	if (!empty($_SERVER['X_GEO_COUNTRY'])) {
                $countryCode3 = strtolower($_SERVER['X_GEO_COUNTRY']);
                header('X-Geo-Country: ' . $_SERVER['X_GEO_COUNTRY']);
            } else if (_cfg('geoipDatabasePath') && _cfg('geoipDatabaseType')) {
                $geoip = new GeoIp(_cfg('geoipDatabasePath'), _cfg('geoipDatabaseType'));
                $record = $geoip->get($ip);
                $countryCode3 = !$record ?: $record->country->isoCode;
            } elseif (function_exists('geoip_database_info')) {
                $countryCode3 = geoip_country_code3_by_name($ip);
                $countryCode3 = strtolower($countryCode3);
            }
        }
        $countryCode3 = strlen($countryCode3) < 3 ? GeoIp::countryIso3($countryCode3) : $countryCode3;
        self::setHeader('X-Geo-Detected: ' . $countryCode3);

        $geoCache = $countryCode3;

        return $geoCache;
    }

    static function getGeoCityData()
    {
        $geoip = new GeoIp(_cfg('geoipCityDatabasePath'), _cfg('geoipCityDatabaseType'));
        $ip = System::getUserIP();
        $record = $geoip->get($ip);
        return !empty($record->subdivisions[0]->isoCode) ? $record->country->isoCode."-".$record->subdivisions[0]->isoCode : NULL;
    }

    public function run()
    {
        global $cfg, $argv, $argc;

        // Check for cli cron
        if (php_sapi_name() == 'cli') {
            if (!empty($argv[1])) switch($argv[1]) {
                case 'runcron':
                    $this->runCron();
                    return;
            }
        }

        $this->checkGetData();
        $this->startGetText();

        $countryCode3 = self::getGeoData();
        //Updating in global config
        if ($countryCode3) {
            $cfg['userCountry'] = $countryCode3;
        }

        System::hook('system:run:before', $this);

        if (Router::getRoute() == 'logout') {
        	Core::getInstance()->sessionDestroy();
            header('Location: ' . $cfg['site'] . '/' . _cfg('language'));
            die();
        }

        $tpl = Router::getPage();

        $template = new Template();
        $template->parse($tpl);

        System::hook('system:run:after', $this);

        //Db::close();
    }

    public function ajax($data)
    {
        Core::getInstance();

        $this->checkGetData();
        $this->startGetText();

        $ajax = new Ajax();
        return trim($ajax->ajaxRun($data));
    }

    /**
     * fix Norwight locale change
     *
     * @param  string $locale
     * @return string
     */
    public function fixOldLocales(string $locale): string
    {
        switch ($locale) {
            case 'no_NO':  
                $locale = 'nb_NO';
                break;
            case 'hi_HI':  
                $locale = 'hi_IN';
                break;
            default:
        }

        return $locale;
    }

    protected function startGetText()
    {
        static $textInitialized = false;

        if ($textInitialized == true) {
            return;
        }

        $textInitialized = true;
        $locales = _cfg('allowedLanguages');
        $locale = (is_array($locales) && isset($locales[_cfg('language')])) ? $locales[_cfg('language')] : 'en_US';
        $locale = $this->fixOldLocales($locale);
        $locale_codeset = 'UTF-8';

        putenv('LC_ALL=' . $locale . '.' . $locale_codeset);
        putenv('LANG=' . $locale . '.' . $locale_codeset);
        putenv('LANGUAGE=' . $locale . '.' . $locale_codeset);
        setlocale(LC_ALL, $locale . '.' . $locale_codeset); // Linux
        setlocale(LC_CTYPE, 'C');
        setlocale(LC_NUMERIC, 'POSIX');


        foreach (['messages','loyalty'] as $file) {
            $fileMessagesPath = _cfg('core') . '/locale';
            $fileRootFile = _cfg('root') . '/' . implode(DIRECTORY_SEPARATOR, ['locale', $locale, 'LC_MESSAGES', $file.'.mo']);
            if (file_exists($fileRootFile)) {
                $fileMessagesPath =  _cfg('root') . '/locale';
            }

            bindtextdomain($file, $fileMessagesPath);
            bind_textdomain_codeset($file, $locale_codeset);
        }

        textdomain('messages');
        return true;
    }

    private function checkGetData()
    {
        $request = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/');

        $langs = _cfg('allowedLanguages');

        if (is_array($langs) && empty($_COOKIE['sitelang'])) {
            // Language detection by geo data
            if (_cfg('enableGeoLanguage')) {
                $country = $this->getGeoData();
                $countryInfo = GeoIp::countryIsoData($country);
                if (!empty($countryInfo['alpha2'])) {
                    $countryLocale = GeoIp::countryLanguage($countryInfo['alpha2']);
                    if (!empty($countryLocale) && !empty($langs[$countryLocale])) {
                        _cfg('language', $countryLocale);
                    }
                }
            }

            // Language detection by http header
            if (_cfg('enableBrowserLanguage') && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $browserLangs = array_reduce(explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']), function ($res, $el) {
                    list($l, $q) = array_merge(explode(';q=', $el), [1]);
                    $res[$l] = (float) $q;
                    return $res;
                }, []);
                arsort($browserLangs);

                if (is_array($browserLangs)) {
                    foreach($browserLangs as $browserLang => $browserLangWeight) {
                        if (!empty($langs[$browserLang])) {
                            _cfg('language', $browserLang);
                            break;
                        }
                    }
                }
            }
        }

        if (isset($_GET['language']) && !isset($langs[$_GET['language']])
            && isset($_COOKIE['sitelang']) && isset($langs[$_COOKIE['sitelang']])
            ) {
            _cfg('language', $_COOKIE['sitelang']);
        }

        if (empty($_GET['language'])) {
            $route = ltrim(empty($_GET['route']) ? $request['path'] : $_GET['route'], '/');

            $_GET['language'] = isset($_COOKIE['sitelang']) ? $_COOKIE['sitelang'] : _cfg('language');
            if (empty($route)) {
                $_GET['route'] = '';
            } else {
                $breakdown = explode('/', $route);

                if ($breakdown[0] === 'api') {
                    if (!empty($_GET['lang'])) {
                        $_GET['language'] = $_GET['lang'];
                    }

                    $_GET['route'] = $route;
                } else {
                    $_GET['language'] = array_shift($breakdown);
                    $_GET['route'] = implode('/', $breakdown);
                }
            }
        }

        if (!isset($_GET['route'])) $_GET['route'] = '';

        $breakdown = explode('/', $_GET['route']);
        if (isset($_GET['language']) && $_GET['language'] == 'run') {
            if ($_GET['route'] == 'cron' && $_GET['cronjob'] == 'a98sdu9a8d91829dpa') {
                $this->runCron();
            } else if ($_GET['route'] == 'gettext' && $_GET['cronjob'] == 'a98sdu9a8d91829dpa') {
                $this->runGetText();
            } else if (substr($_GET['route'], 0, 4) == 'code') {
                $breakdown = explode('/', $_GET['route']);

                if (!isset($breakdown[1]) || !$breakdown[1]) {
                    exit();
                }

                $user = new User();
                $userId = $user->getUserIdByCode($breakdown[1]);
                if ($userId === false) {
                    echo _('Code incorrect, user not found');
                    exit();
                }

                $answer = $user->finishRegistration($userId);
                if (!is_object($answer) && $answer !== true) {
                    exit($answer);
                }

                $answer = User::setEmailVerified($answer->id);
                if ($answer !== true) {
                    exit($answer);
                }

                $page = '/' . _cfg('language') . _cfg('regLoginRelPage');

                go(_cfg('site') . $page);
            } else if ($breakdown[0] == 'emailverify') {
                if (!isset($breakdown[1]) || !$breakdown[1]) {
                    exit();
                }

                $user_id = User::getIdByEmailCode($breakdown[1]);
                if (!$user_id) {
                    echo _('Code incorrect, user not found');
                    exit();
                }

                $answer = User::setEmailVerified($user_id);
                if ($answer !== true) {
                    exit($answer);
                }

                $_SESSION['flash_message'] = gettext('email_has_been_verified');

                $page = '/' . _cfg('language');

                go(_cfg('site') . $page);
            } else if (substr($_GET['route'], 0, 11) == 'social-code') {
                $breakdown = explode('/', $_GET['route']);

                if (!$breakdown[1]) {
                    exit();
                }

                $user = new User();
                $userId = $user->newSocialConnect($breakdown[1]);
                if ($userId === false) {
                    echo _('Code incorrect, user not found');
                    exit();
                }

                go(_cfg('site'));
            } else if (trim($_GET['route'], '/') == 'processEmailQueue' && $_GET['secret'] == 'keinooquohgeiQuah0eizaow4ohLohng') {
                EmailQueue::process();
            } else if (trim($_GET['route'], '/') == 'processUnactivatedAccounts' && $_GET['secret'] == 'aasahyaexoo0aehu0ienae4ohheJaeng') {
                $date = null;
                if (isset($_GET['date'])) {
                    $date = $_GET['date'];
                }
                Cron::processAccounts('Unactivated', $date);
            } else if (trim($_GET['route'], '/') == 'processUnverifiedEmails' && $_GET['secret'] == 'othahy6loog5suvoquuph3chui1moh0I') {
                $date = null;
                if (isset($_GET['date'])) {
                    $date = $_GET['date'];
                }
                Cron::processAccounts('UnverifiedEmails', $date);
            } else if (strpos($_GET['route'], 'api/user/register') === 0) {
                Api::userRegister();
            } else if (strpos($_GET['route'], 'api/user/update') === 0) {
                Api::userUpdate();
            } else if (strpos($_GET['route'], 'api/user/statusupdate') === 0) {
                Api::userStatusUpdate();
            } else if (strpos($_GET['route'], 'api/temporarylocksupdate') === 0) {
                User::updateTemporaryLocks();
            } else if (strpos($_GET['route'], 'api/sendemailoverproxy') === 0) {
                Api::userSendEmail();
            } else if (strpos($_GET['route'], 'api/resendbgcdata') === 0) {
                Api::resendBGCData();
            } else if(strpos($_GET['route'], 'api/createemailqueue') === 0) {
                Api::createEmailQueue();
            } else if (strpos($_GET['route'], 'api/createsmsqueue') === 0) {
                Api::createSmsQueue();
            } else if (strpos($_GET['route'], 'api/runcron') === 0) {
                Api::runCron();
            } else if (strpos($_GET['route'], 'api/banners') === 0) {
                Banners::fetchBanners();
            } else if (strpos($_GET['route'], 'api/games/clearcache') === 0) {
                Games::DropCache();
            } else if (strpos($_GET['route'], 'api/wins/clearcache') === 0) {
                Games::dropWinsCache();
            } else if (strpos($_GET['route'], 'api/games/sorting/clearcache') === 0) {
                Games::dropGamesSortingCache();
            } else if (strpos($_GET['route'], 'api/games/sorts/clearcache') === 0) {
                Games::dropGamesSortsCache();
            } else if (strpos($_GET['route'], 'api/siteconfig') === 0) {
                Config::fetchSiteConfig(true);
                Cache::dropCacheKeys('siteConfig');

                echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
                exit();
            } else if (strpos($_GET['route'], 'api/tempusers') === 0) {
                if (strpos($_GET['route'], 'api/tempusers/activation') === 0) {
                    $operation = 'Activation';
                } elseif (strpos($_GET['route'], 'api/tempusers/resendemail') === 0) {
                    $operation = 'ResendEmail';
                } else {
                    $operation = 'GetTempUsers';
                }

                Api::userTemp($operation);
            } else if (strpos($_GET['route'], 'api/checkifsafeendpoint') === 0) {
                exit(!empty(_cfg('newTempUsersEndpoint')));
            } else if (strpos($_GET['route'], 'api/resetdepositslimiter') === 0) {
                RateLimiter::resetDepositsLimiter();
            } else {
                exit('Run command error');
            }

            !isset($_SERVER['MY_TEST_RUN']) && exit();
        }

        System::hook('system:language:before', $this);

        //Setting - Languages
        if ((empty($_GET['language']) || !isset($langs[$_GET['language']])) && isset($_COOKIE['sitelang']) && isset($langs[$_COOKIE['sitelang']])) {
            _cfg('language', $_COOKIE['sitelang']);
        } else if (isset($_GET['language']) && isset($langs[$_GET['language']])) {
            _cfg('language', $_GET['language']);
        }

        if (!isset($_COOKIE['sitelang']) || $_COOKIE['sitelang'] !== _cfg('language')) {
            self::setCookie('sitelang', _cfg('language'), time() + 30 * 24 * 3600, '/');
        }

        $_GET['language'] = _cfg('language'); //overwriting just in case
        _cfg('href', _cfg('site') . '/' . _cfg('language'));

        System::hook('system:language:after', $this);

        return true;
    }

    /**
     * Set cookie for user
     *
     * @return bool set cookie result
     */
    public static function setCookie() {
        if (php_sapi_name() == "cli") {
            return false;
        }
        return call_user_func_array('setcookie', func_get_args());
    }

    /**
     * Set http header for user
     *
     * @return bool set cookie result
     */
    public static function setHeader() {
        if (php_sapi_name() == "cli") {
            return false;
        }
        return call_user_func_array('header', func_get_args());
    }

    public function runFundistAPI($path, $retry = 0, $baseUrl = '', $post = false)
    {
        $postFields = [];
        if ($post) {
            $tmp = explode('?&', $path, 2);

            if (count($tmp) == 2)
            {
                parse_str($tmp[1], $postFields);
            }

            $path = $tmp[0];
        }

        if (empty($baseUrl)) {
            $fundistApiUrl = _cfg('fundistApiUrl');

            if (empty($fundistApiUrl)) {
                self::initApiUrl();
                $fundistApiUrl = _cfg('fundistApiUrl');
            }

            $url = $fundistApiUrl . '/' . _cfg('fundistApiKey') . $path;
        } else {
            $url = $baseUrl . $path;
        }

        if (!$url) {
            exit('rerun');
        }

        $ch = empty($_SERVER['TEST_RUN']) ? curl_init() : true;

        $headers = [];

        $curlOptions = array(
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => FALSE,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => $post,
            CURLOPT_HTTPHEADER => [],
            CURLOPT_HEADERFUNCTION => function ($curl, $header) use (&$headers) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) {
                    // ignore invalid headers
                    return $len;
                }

                $headers[mb_strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
            }
        );

        if (!empty($_SERVER['HTTP_HOST'])) {
            $curlOptions[CURLOPT_HTTPHEADER][] = 'X-Domain: ' . (isset($_SERVER['HTTP_X_DOMAIN']) ? $_SERVER['HTTP_X_DOMAIN'] : $_SERVER['HTTP_HOST']);
        }

        if (!empty($_SERVER['HTTP_X_UNIQ_ID'])) {
            $curlOptions[CURLOPT_HTTPHEADER][] = 'X-Uniq-Id: ' . $_SERVER['HTTP_X_UNIQ_ID'];
        }

        if ($post) {
            $curlOptions[CURLOPT_POSTFIELDS] = $postFields;
        }

        empty($_SERVER['TEST_RUN']) ? curl_setopt_array($ch, $curlOptions) : true;

        $responseContentType = 'application/x-fundist-data';
        $response = empty($_SERVER['TEST_RUN']) ? curl_exec($ch) : '1,success'; // run the whole process

        $http_code = empty($_SERVER['TEST_RUN']) ?  curl_getinfo($ch,CURLINFO_HTTP_CODE) : 200;
        if (!$response) {
            $response = '999,API request failed: ['.$http_code.'], curl: '.curl_errno($ch).' - '.curl_error($ch);
        } elseif ($http_code != 200) {
            $response = '999,API request failed: ['.$http_code.'], response: ' . mb_substr($response, 0, 200);
        } else {
            $expectedHash = md5($this->apiTID . _cfg('fundistApiPass') . $response);
            if (empty($_SERVER['TEST_RUN']) && $headers['response-hash'][0] != $expectedHash) {
                Logger::log('Fundist API: API request failed: '. _('Response-hash does not match the request or is missing'), 'error', [
                    'url' => 'url: ' . $url,
                    'own_hash' => 'own_hash: ' . $expectedHash,
                    'response_hash' => 'response_hash: ' . $headers['response-hash'][0],
                    'response' => 'response: ' . print_r($response, true)
                ]);

                throw new \Exception(_('Response-hash does not match the request or is missing'));
            }

            $responseContentTypeFull = empty($_SERVER['TEST_RUN']) ? curl_getinfo($ch, CURLINFO_CONTENT_TYPE) : 'application/x-fundist';
            $responseContentType = trim(explode(';', $responseContentTypeFull, 2)[0]);
        }

        empty($_SERVER['TEST_RUN']) ? curl_close($ch) : true;

        //Adding this check in case apiTID will be 0
        if ($this->apiLogID != 0) {
            $duration = microtime(true) - $this->apiReqStartTime; //calculates total time taken

            if (strlen($response) > self::LOGMSG_LIMIT) {
                $rsplog = substr($response, 0, self::LOGMSG_LIMIT);
            } else {
                $rsplog = &$response;
            }

            Db::query(
                'UPDATE `api_logs2` SET ' .
                '`TID` = "' . Db::escape($this->apiTID) . '", ' .
                '`Request` = "' . Db::escape(Utils::obfuscateUrl($path)) . '", ' .
                '`Params` = "' . Db::escape(http_build_query($postFields)) . '", ' .
                '`Response` = "' . Db::escape($rsplog) . '", ' .
                '`CallTime` = ' . number_format($duration, 3, '.', '') . ' ' .
                'WHERE ID = ' . (int)$this->apiLogID
            );
        }

        $this->apiReqStartTime = 0;
        $this->apiLogID = 0;
        $this->apiTID = '';

        if ($responseContentType == 'application/json') {
            return $response;
        }

        //Check error 33
        $breakdown = explode(',', $response);

        if ($breakdown[0] == 33 && $breakdown[1] != 'self-exclusion' && $retry != 1) {
            //WLC on deployment, sleeping for 1 second and re-run
            sleep(1);
            $this->runFundistAPI($path, 1, $baseUrl);
        }

        if ($breakdown[0] != 1) {
            Logger::log('Fundist API: ' . $response, 'error', [
                'url' => $url
            ]);
        }

        return $response;
    }

    public function runLoyaltyAPI($path, $params)
    {
        return Loyalty::Request($path, $params, false);
    }

    /**
     * @param string $path
     * @param bool $post
     * @param array $params
     * @return string|null
     */
    public function runPaycryptosAPI(string $path, bool $post, array $params = []): ?string
    {
        return Paycryptos::getInstance()->send($path, $post, $params);
    }

    /*
    $url      @string = url to API query
    $userId     @int = userId for insert if required
    */
    public function getApiTID($url, $userId = 0)
    {
        $this->apiReqStartTime = microtime(true);
        $doDbLog = !_cfg('fundistTidUUID') || _cfg('forceApiDBLog');

        if (!isset($_SESSION['user']['id'])) {
            $uid = $userId;
        } else {
            $uid = $_SESSION['user']['id'];//Front::User('id');
        }

        $result = false;
        // Check if we need to use only uuid4 scheme
        if (_cfg('fundistTidUUID')) {
            $this->apiTID = _cfg('fundistTidPrefix') . Uuid::uuid4()->toString();
        } else $this->apiTID = '';

        if($doDbLog) {
            $result = Db::query(
                'INSERT INTO `api_logs2` SET ' .
                '`TID` = "' . Db::escape( $this->apiTID) . '", ' .
                '`UID` = ' . (int) $uid . ', ' .
                '`Request` = "' . Db::escape( Utils::obfuscateUrl($url) ) . '", ' .
                '`Params` = "", ' .
                '`ReqDate` = NOW(), `Date` = NOW()'
            );

            if (!$result) {
                throw new \Exception(_('Transaction value generation failed'));
            }

            $this->apiLogID = Db::lastId();
            if (!_cfg('fundistTidUUID')) {
                $this->apiTID = _cfg('fundistTidPrefix') . $this->apiLogID;
            }
        }

        if (_cfg('env') != 'prod') {
            $this->apiTID = _cfg('env') . '_' . $this->apiTID;
        }

        return $this->apiTID;
    }

    private function runGetText()
    {
        $directory = _cfg('root') . '/template';
        $saveFile = 'gettext.php';

        if (!file_exists($directory) && !is_dir($directory)) {
            exit('Directory does not exists');
        }

        $stringList = array();
        $gatheringStrings = array();
        $handler = opendir($directory);
        $ignoreFiles = array('mail', '.svn', 'm');
        while ($file = readdir($handler)) {
            //Checking if not hidden files
            if ($file != "." && $file != "..") {
                //Checking if file ignoring is required
                if (!in_array($file, $ignoreFiles)) {
                    $html = file_get_contents($directory . '/' . $file);
                    preg_match_all("{% trans '(.*?)' %}", $html, $gatheringStrings);
                    $stringList = array_merge($stringList, $gatheringStrings[1]);
                }
            }
        }
        closedir($handler);

        //cleaning
        $gathered = array();
        foreach ($stringList as $v) {
            if (!in_array($v, $gathered)) {
                $gathered[] = $v;
            }
        }

        //putting into normal php
        $html = '<?php' . "\n";
        foreach ($gathered as $v) {
            $html .= '_("' . $v . '");' . "\n";
        }
        $html .= '?' . '>';


        if (!file_put_contents(_cfg('core') . '/classes/' . $saveFile, $html)) {
            exit('This should be done on local machine and not production, command wasn\'t done successfully');
        }
    }

    public function runCron()
    {
        $cron = new Cron();
        // @codeCoverageIgnoreStart
        $debugCron = _cfg('debugCron');
        if ($debugCron) {
            $start = time();
            echo 'Start cron task ' . date('Y-m-d H:i:s', $start) . PHP_EOL;
        }
        System::hook('system:cron:before');
        $cron->cleanOldSqlData();
        if ($debugCron) {
            echo 'cleanOldSqlData => ' . (time() - $start) . 's ' . PHP_EOL;
        }
        $cron->processEmailSend();
        if ($debugCron) {
            echo 'processEmailSend => ' . (time() - $start) . 's ' . PHP_EOL;
        }
        $cron->repeatEmailSend();
        if ($debugCron) {
            echo 'repeatEmailSend => ' . (time() - $start) . 's ' . PHP_EOL;
        }
        $cron->cleanEmailQueue();
        if ($debugCron) {
            echo 'cleanEmailQueue => ' . (time() - $start) . 's ' . PHP_EOL;
        }
        $cron->finishRegistration();
        if ($debugCron) {
            echo 'finishRegistration => ' . (time() - $start) . 's ' . PHP_EOL;
        }
        $cron->fetchCountryList();
        if ($debugCron) {
            echo 'fetchCountryList => ' . (time() - $start) . 's ' . PHP_EOL;
        }
        $cron->fetchStateList();
        if ($debugCron) {
            echo 'fetchStateList => ' . (time() - $start) . 's ' . PHP_EOL;
        }
        $cron->fetchGamesList();
        if ($debugCron) {
            echo 'fetchGamesList => ' . (time() - $start) . 's ' . PHP_EOL;
        }
        $cron->fetchBanners();
        if ($debugCron) {
            echo 'fetchBanners => ' . (time() - $start) . 's ' . PHP_EOL;
        }
        $cron->fetchBannersV2();
        if ($debugCron) {
            echo 'fetchBanners => ' . (time() - $start) . 's ' . PHP_EOL;
        }
        $cron->fetchSiteConfig();
        if ($debugCron) {
            echo 'fetchSiteConfig => ' . (time() - $start) . 's ' . PHP_EOL;
        }
        $cron->syncLiveChat();
        if ($debugCron) {
            echo 'syncLiveChat => ' . (time() - $start) . 's ' . PHP_EOL;
        }
        $cron->fetchSeo();
        if ($debugCron) {
            echo 'fetchSeo => ' . (time() - $start) . 's ' . PHP_EOL;
        }
        $cron->clearSortingCache();
        if ($debugCron) {
            echo 'clearSortingCache => ' . (time() - $start) . 's ' . PHP_EOL;
        }
        $cron->clearSortsCache();
        if ($debugCron) {
            echo 'clearSortsCache => ' . (time() - $start) . 's ' . PHP_EOL;
        }
        $cron->processSmsSend();
        if ($debugCron) {
            echo 'processSmsSend => ' . (time() - $start) . 's ' . PHP_EOL;
        }
        $cron->repeatSmsSend();
        if ($debugCron) {
            echo 'repeatSmsSend => ' . (time() - $start) . 's ' . PHP_EOL;
        }
        $cron->cleanSmsQueue();
        if ($debugCron) {
            echo 'cleanSmsQueue => ' . (time() - $start) . 's ' . PHP_EOL;
        }
        $cron->updateEmergencyData();
        if ($debugCron) {
            echo 'updateEmergencyData => ' . (time() - $start) . 's ' . PHP_EOL;
        }
        System::hook('system:cron:after');
        if ($debugCron) {
            echo 'Cron task finished '. date('Y-m-d H:i:s') . PHP_EOL;
        }
        // @codeCoverageIgnoreEnd

        exit();
    }

    public function getFundistValue($field, $value = '')
    {
        $fundist_value = '';

        switch ($field) {
            case 'Gender':
                switch ($value) {
                    case 'm':
                        $fundist_value = 'male';
                        break;
                    case 'f':
                        $fundist_value = 'female';
                        break;
                }
                break;
        }

        return $fundist_value;
    }

    protected static function initApiUrl()
    {
        if (!empty($_GET['lang']) && preg_match('/^([a-z]{2}|[a-z]{2}-[a-z]{2})$/', $_GET['lang'])) {
            _cfg('language', $_GET['lang']);
        }
        _cfg('fundistApiUrl', implode('/', [trim(_cfg('fundistApiBaseUrl'), '/'), _cfg('language'), 'Api']) );
    }

    public static function redis(bool $isWrapper = false)
    {
        return $isWrapper ? Core::getInstance()->redisCache() : Core::getInstance()->redisCache()->redis();
    }

    public static function completeResponse()
    {
        ignore_user_abort(true);

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    public static function getFlashMessage()
    {
        $message = '';

        if (isset($_SESSION['flash_message'])) {
            $message = gettext($_SESSION['flash_message']);
            unset($_SESSION['flash_message']);
        }

        return $message;
    }

    public static function getUserIP($all = false)
    {
        $ipkeys = array(
            'REMOTE_ADDR',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_CF_CONNECTING_IP',
        );

        $ips = []; $last_detected = '';
        foreach($ipkeys as $key){
            if( !empty($_SERVER[$key])){
                $tmp = explode(',',$_SERVER[$key]);
                $i=0;
                foreach($tmp as $ip) {
                    $ip = trim($ip);
                    if(\filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        if($i!=0){
                            $ips[$key.'_'.$i] = $ip;
                        } else {
                            $ips[$key] = $ip;
                        }
                        $last_detected = $ip;
                        $i+=1;
                    }
                }
            }
        }

        $last_detected = empty($last_detected) && !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : $last_detected;
        if (!$all) return $last_detected;

        return $ips;
    }

    /**
     * Builds url string according parse_url generated components
     * @public
     * @method buildUrl
     * @param {array} urlData
     * @return {string}
     */
    public static function build_url($parsed_url) {
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';

        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    public static function isCountryForbidden($country, $ip = '')
    {
        $whiteListIp = _cfg('whitelist_ip') ?: [];
        if (!empty($_SERVER['WLC_WHITELIST_IP'])) {
            $whiteListIp[] = $ip;
        }

        $siteConfig = Config::getSiteConfig() ?: [];
        Config::setExcludeCountries($siteConfig);

        if (!empty($siteConfig['whitelist_ip']) && is_array($siteConfig['whitelist_ip'])) {
            $whiteListIp = array_merge($whiteListIp, $siteConfig['whitelist_ip']);
        }

        $isWhiteIP = !empty($ip) && in_array($ip, $whiteListIp);

        return in_array($country, $siteConfig['exclude_countries']) && !$isWhiteIP;
    }

    /**
     * Check user region (GeoIp) in stall exclude countries
     *
     * @param string $region
     * @return bool
     */
    public static function isCountryRegionForbidden(string $region): bool
    {
        $siteConfig = Config::getSiteConfig() ?: [];
        Config::setExcludeCountries($siteConfig);

        return in_array($region, $siteConfig['exclude_countries']);
    }


    /**
     * If there is a country in the config, then we do not show 2FA
     *
     * @param User $loggedUser
     * @return bool
     */
    public static function isCountry2FAForbidden(User $loggedUser): bool
    {
        $geoIp = new GeoIp(_cfg('geoipDatabasePath'), _cfg('geoipDatabaseType'));
        $data = $geoIp->get($loggedUser->userData->reg_ip);
        $userIsoCode3 = $geoIp::countryIso3($data->country->isoCode);

        return !(in_array($userIsoCode3, ((array)_cfg('excludeCountriesFrom2faCheck'))) && (self::getGeoData() == $userIsoCode3));
    }

}
