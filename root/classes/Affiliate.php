<?php
namespace eGamings\WLC;

use eGamings\WLC\System;

class Affiliate
{
    protected static $affiliateSystem = '';
    protected static $fields = [];

    // number of days to store cookie
    protected static $cookie_days_period = 0;

    // max size of parameter to store in cookie (bytes)
    protected static $max_cookie_size = 256;

    protected static $redirectUrl = '';

    public static $_aff_cookie = '';

    public static $optionalFaffDataKeys = [
        'cid',
        'pid',
        'sid',
        'sub_id1',
        'sub_id2',
        'sub_id3',
        'sub_id4',
        'sub_id5'
    ];

    public static function identifyAffiliate(array $fields = [], bool $needToRedirect = true): ?string
    {
        if (!_cfg('enableAffiliates')) {
            return false;
        }

        if (_cfg('keepPreviousAffiliateCookie') && !empty($_COOKIE['_aff'])) {
            return null;
        }

        $tmp = System::hook('affilate:ident:before', $fields);
        if (!empty($tmp)) $fields = $tmp;

        self::$fields = $fields;

        parse_str($_SERVER['QUERY_STRING'], $queryStringAsArray);
        unset($queryStringAsArray['route']);
        $queryString = http_build_query($queryStringAsArray);

        self::$cookie_days_period = _cfg('setAffiliateCookieInDays') ?: 7; // config-defined value or +7 days
        $affiliateId = '';
        $affiliateData = '';
        $affiliateParams = $queryString;

        if (!empty($fields['uid']) && !empty($fields['pid']) && !empty($fields['cid']) && !empty($fields['lid'])) {
            self::$affiliateSystem = 'goldtime';
            $affiliateId = $fields['pid'];
            $affiliateData = Utils::encodeURIComponent(sprintf('uid=%s&cid=%s&lid=%s', $fields['uid'], $fields['cid'], $fields['lid']));
        } else if (!empty($fields['faff']) && !empty($fields['clickid'])) {
            self::$affiliateSystem = 'faff';
            $affiliateId = filter_var($fields['faff'], FILTER_SANITIZE_NUMBER_INT);
            $affiliateData = $fields['clickid'];
        } else if (!empty($fields['faff'])) {
            self::$affiliateSystem = 'faff';
            $affiliateId = filter_var($fields['faff'], FILTER_SANITIZE_NUMBER_INT);

            $tmpFaffOptions = [];
            foreach(self::$optionalFaffDataKeys as $key) {
                if (!empty($fields[$key])) {
                    $tmpFaffOptions[$key] = $fields[$key];
                }
            }

            $affiliateData = self::appendCampaignName(http_build_query($tmpFaffOptions), $fields['sub']);
        } else if (!empty($fields['fref'])) {
            self::$affiliateSystem = 'fref';
            $affiliateId = $fields['fref'];
            $affiliateData = $fields['sub'] ?? '';
        } else if (!empty($fields['btag'])) {
            $btagArr = '';
            $bTag = $fields['btag'];
            if (strpos($bTag, 'b_') > 0) {
                self::$affiliateSystem = 'income';
                $btagArr = explode("b_", $bTag, 2);
                $affiliateId = $btagArr[0];
                $affiliateData = $btagArr[1] ? 'b_' . $btagArr[1] : '';
            } else {
                self::$affiliateSystem = 'netrefer';
                $btagArr = explode("_", $bTag, 2);
                $affiliateId = $btagArr[0];

                $affInfo = $btagArr[1] ?? '';
                $affiliateData = !empty($fields['subid']) ? $affInfo . '_' . $fields['subid'] : $affInfo;
            }
        } else if (!empty($fields['utm_source'])) {
            self::$affiliateSystem = $fields['utm_source'];
            $affiliateId = $fields['sub'] ?? 'unknown';
            $affiliateData = $fields['clickid'] ?? '';
        } else if (!empty($fields['qtag'])) {
            self::$affiliateSystem = 'quintessence';
            $affiliateId = 'unknown';
            if (($tags = strpos($fields['qtag'], '_p')) !== false && strpos($fields['qtag'], '_pub_id') === false) {
                $tags = explode('_p', $tags);
                $params = explode('_p', $affiliateParams);
                $affiliateData = $tags[0];
                $affiliateParams = $params[0];
                self::sendCookie('affPromoCode', $tags[1]);
            } else {
                $affiliateData = $fields['qtag'];
                if ($_COOKIE['affPromoCode']) {
                    self::removeCookie('affPromoCode');
                }
            }
        } else if (!empty($fields['stag'])) {
            self::$affiliateSystem = 'affilka';
            if (strpos($fields['stag'], '_') !== false) {
                list($affiliateId, $affiliateData) = explode('_', $fields['stag']);
            }
        } else if (!empty($fields['visitorId']) && !empty($fields['a_aid']) && !empty($fields['a_bid']) ) {
            self::$affiliateSystem = 'pap';
            $affiliateId = $fields['a_aid'];
            $affiliateData = $fields['a_bid'] . '_' . $fields['visitorId'];
        } else if (!empty($fields['a_aid'])) {
            self::$affiliateSystem = 'pap';
            $affiliateId = $fields['a_aid'];
            $affiliateData = $fields['a_bid'] ?? '';
        } else if (!empty($fields['paff']) || !empty($fields['Paff'])) {
            self::$affiliateSystem = 'paff';
            $affiliateId = $fields['paff'] ?? $fields['Paff'];
            $affiliateData = $fields['paff'] ?? $fields['Paff'];
        }

        System::hook('affilate:ident:after', $fields, self::$affiliateSystem);

        if (self::$affiliateSystem !== '') {
            $affData = [
                'system' => Utils::encodeURIComponent(self::$affiliateSystem),
                'id' => Utils::encodeURIComponent($affiliateId),
                'data' => Utils::encodeURIComponent($affiliateData),
                'params' => Utils::encodeURIComponent($affiliateParams)
            ];
            $affiliateCookieValue = "system={$affData['system']}&id={$affData['id']}&data={$affData['data']}&params={$affData['params']}";
            self::sendCookie('_aff', $affiliateCookieValue, "/", null, !empty($_SERVER['AFF_DOMAIN']) ? $_SERVER['AFF_DOMAIN'] : "");

            self::$_aff_cookie = $affiliateCookieValue;
            self::saveTracking($fields);

            if (_cfg('unsetAffKeys')) {
                $unsetKeys = array_unique(array_merge([
                    'uid', 'pid', 'sid', 'cid', 'sub','lid', 'faff', 'clickid', 'fref', 'paff', 'Paff',
                    'btag', 'subid', 'qtag', 'utm_source', 'stag', 'a_aid', 'a_bid', 'visitorId'
                ], self::$optionalFaffDataKeys));

                foreach($unsetKeys as $key) {
                    unset($queryStringAsArray[$key]);
                }
            }
            else {
                 $needToRedirect = false; // avoid infinity redirects
            }

            $requestUrl = strtok($_SERVER['REQUEST_URI'], '?');
            $queryString = http_build_query($queryStringAsArray);

            if ($needToRedirect) {
                self::$redirectUrl = "https://{$_SERVER['HTTP_HOST']}" . ($requestUrl ?: '') . ($queryString ? '?' . $queryString : '');
                self::redirect($fields);
            } else {
                return self::$affiliateSystem;
            }
        }

        return null;
    }

    /**
     * Required format:
     * campName&some=param&other=param...
     *
     * If $ data is empty, remove the added "&" to the company name (if it exists and is added)
     *
     * @param string $data some=param&other=param...
     * @param string $camp campName
     *
     * @return string
     */
    public static function appendCampaignName(string $data, ?string $camp): string {
        return trim(($camp ? $camp . '&' : '') . $data, " \n\r\t\v\0&");
    }

    public static function saveTracking($fields = array())
    {
        if (empty(self::$affiliateSystem)) {
            return;
        }

        $affiliate_id = self::getAffiliateId($fields);
        $userIP = System::getUserIP();
        switch (self::$affiliateSystem) {
            case 'globo-tech':
                if (!empty($affiliate_id)) {
                    self::sendCookie('globo-tech', $affiliate_id);

                    //-- save hit with partner_id
                    Db::query('INSERT INTO affiliate_hits (ip, affiliate_id, affiliate_system, add_date)
                                VALUES("' . Db::escape($userIP) . '", "' . Db::escape($affiliate_id) . '", "globo-tech", NOW())'
                    );
                }

                break;
            case 'quintessence':
                if (!empty($affiliate_id)) {
                    self::sendCookie('quintessence', $affiliate_id);

                    //-- save hit with partner_id
                    Db::query('INSERT INTO affiliate_hits (ip, affiliate_id, affiliate_system, add_date)
                                VALUES("' . Db::escape($userIP) . '", "' . Db::escape($affiliate_id) . '", "quintessence", NOW())'
                    );
                }
                break;
            case 'faff':
                if (!empty($affiliate_id)) {

                    $unique = self::isFaffUniqueVisitor($affiliate_id, $userIP);
                    if ($unique) {
                        self::sendCookie('faff', $affiliate_id, "/", null, !empty($_SERVER['AFF_DOMAIN']) ? $_SERVER['AFF_DOMAIN'] : "");
                        //-- save hit with partner_id
                        Db::query('INSERT INTO affiliate_hits (ip, affiliate_id, affiliate_system, add_date)
                                    VALUES("' . Db::escape($userIP) . '", "' . Db::escape($affiliate_id) . '", "faff", NOW())'
                        );
                        $data = [];
                        parse_str($affiliate_id, $data);
                        if (!empty($data['faff'])) {
                            $affiliateId = $data['faff'];
                            $affiliateClickId = self::appendCampaignName(
                                http_build_query(
                                    array_intersect_key($data, array_flip(self::$optionalFaffDataKeys)
                                )
                            ), $data['sub']);
                            self::affiliateUniqueVisitor($affiliateId, $affiliateClickId);
                        }
                    }
                }
                break;
            case 'income':
                if (!empty($affiliate_id)) {
                    self::sendCookie('income', $affiliate_id);

                    //-- save hit with partner_id
                    Db::query('INSERT INTO affiliate_hits (ip, affiliate_id, affiliate_system, add_date)
                                VALUES("' . Db::escape($userIP) . '", "' . Db::escape($affiliate_id) . '", "income", NOW())'
                    );
                }
                break;
        }
    }

    public static function redirect($fields = array())
    {
        if (isset($fields['redir']) && ($fields['redir'] == 'no' || $fields['redir'] == 0)) {
            return false;
        }

        if (empty(self::$affiliateSystem)) {
            return false;
        }

        if (empty(self::$redirectUrl)) {
            $url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . '/';
        } else {
            $url = self::$redirectUrl;
        }

        header("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        header('Location: ' . $url);

        exit();
    }

    public static function getAffiliateId($fields = [])
    {
        if (empty(self::$affiliateSystem)) {
            return false;
        }

        if (!empty(self::$fields)) {
            $fields = self::$fields;
        }

        $values = [];

        switch (self::$affiliateSystem) {
            case 'globo-tech':

                foreach (['partner_id', 'subid'] as $field) {
                    if (isset($fields[$field]) && strlen($fields[$field]) > 0 && strlen($fields[$field]) < self::$max_cookie_size) {
                        $values[] = $fields[$field];
                    }
                }

                $affiliateID = implode('&subid=', $values);

                break;
            case 'quintessence':

                if (isset($fields['affiliate'])) {
                    foreach (['affiliate', 'tracker', 'creative', 'source'] as $field) {
                        if (isset($fields[$field]) && strlen($fields[$field]) < self::$max_cookie_size) {
                            $values[$field] = $fields[$field];
                        }
                    }
                } elseif (isset($fields['tracker'])) {
                    foreach (['tracker', 'anid', 'creative'] as $field) {
                        if (isset($fields[$field]) && strlen($fields[$field]) > 0 && strlen($fields[$field]) < self::$max_cookie_size) {
                            $values[$field] = $fields[$field];
                        }
                    }
                } /*else {
                    if (strlen(trim($fields['sr'])) == 0 ) {
                        break;
                    }
                    $values['tracker'] = $fields['sr'];
                    if (isset($fields['anid']) && strlen($fields['anid']) > 0 && strlen($fields['anid']) < self::$max_cookie_size) {
                        $values['anid'] = $fields['anid'];
                    }
                }*/

                $affiliateID = http_build_query($values);

                break;
            case 'faff':

                foreach (array_merge(['faff', 'sub'], self::$optionalFaffDataKeys) as $field) {
                    if (isset($fields[$field]) && $fields[$field] !== ''
                        && strlen($fields[$field]) < self::$max_cookie_size) {
                        $values[$field] = $fields[$field];
                    }
                }
                $affiliateID = http_build_query($values);
                break;
            case 'income':
                if (!empty($fields['btag'])) {
                    $affiliateID = $fields['btag'];
                }
                break;
        }

        return $affiliateID ?? null;
    }

    public static function getAffiliateData()
    {
        $data = [];
        $tmp = [];

        if (isset($_COOKIE['_aff']) || !empty(self::$_aff_cookie)) { // Global Affiliate tracking script
            $cookie = isset($_COOKIE['_aff']) ? $_COOKIE['_aff'] : self::$_aff_cookie;
        	parse_str($cookie, $tmp);
        	if (!empty($tmp['system']) && !empty($tmp['id'])) {
            	$data['affiliateSystem'] = $tmp['system'];
            	$data['affiliateId'] = $tmp['id'];
            	$data['affiliateClickId'] = (!empty($tmp['data'])) ? $tmp['data'] : '';
                $data['affiliateParams']  = [];
                if (!empty($tmp['params'])) { @parse_str($tmp['params'], $data['affiliateParams']); }
            }
        } else if (isset($_COOKIE['pid'])) { // Goldtime
        	$data['affiliateId'] = $_COOKIE['pid'];
        	$tmp = array();
        	if (!empty($_COOKIE['uid'])) $tmp['uid'] = $_COOKIE['uid'];
        	if (!empty($_COOKIE['cid'])) $tmp['cid'] = $_COOKIE['cid'];
        	if (!empty($_COOKIE['lid'])) $tmp['lid'] = $_COOKIE['lid'];
        	$data['affiliateClickId'] = http_build_query($tmp);
        } else if (isset($_COOKIE['ref'])) {
            $data['affiliateId'] = (int) $_COOKIE['ref']; //Not set for now
        } else {
            if (isset($_COOKIE['egass'])) {
                $aff = explode(':', $_COOKIE['egass']);
                if (isset($aff[1])) $data['affiliateId'] = $aff[1];
                if (isset($aff[0])) $data['affiliateClickId'] = $aff[0];
                $data['affiliateSystem'] = 'egass';
            } elseif (isset($_COOKIE['quintessence'])) {
                $tmp = [];
                parse_str($_COOKIE['quintessence'], $tmp);
                $data['affiliateId'] = $tmp['tracker'];
                unset($tmp['tracker']);
                $data['affiliateClickId'] = http_build_query($tmp);
                $data['affiliateSystem'] = 'quintessence';
            } elseif (isset($_COOKIE['faff'])) {
                $tmp = [];
                parse_str($_COOKIE['faff'], $tmp);
                if (!empty($tmp['faff'])) {
                    $data['affiliateId'] = $tmp['faff'];
                    $data['affiliateClickId'] = !empty($tmp['sub']) ? $tmp['sub'] : '';
                    $data['affiliateSystem'] = 'faff';
                }
            } elseif (isset($_COOKIE['globo-tech'])) {
                $data['affiliateId'] = $_COOKIE['globo-tech'];
                $data['affiliateSystem'] = 'globo-tech';
            } else {
                if (isset($_COOKIE['income'])) {
                    list($affiliateId, $affiliateClickId) = explode('b_', $_COOKIE['income'], 2);
                    $data['affiliateId'] = $affiliateId;
                    $data['affiliateClickId'] = 'b_' . $affiliateClickId;
                    $data['affiliateSystem'] = 'income';
                }
            }
        }

        /* Looks like buggy logic
        if (Affiliate::identifyAffiliate($data)) {
            Affiliate::saveTracking($data);
            $affiliateId = trim(Affiliate::getAffiliateId($data));

            if (strlen($affiliateId)) {
                $data['affiliateId'] = $affiliateId;
                $data['affiliateSystem'] = Affiliate::getSystem();
            }
        }
        */

        return $data;
    }

    public static function getAffiliateIdByUrl($url)
    {
        $decoded = json_decode($url, JSON_OBJECT_AS_ARRAY);

        if (
            $decoded !== NULL &&
            !empty($decoded['FaffCodes']) &&
            count($decoded['FaffCodes']) == 1
        ) {
            return (int)$decoded['FaffCodes'][0];
        } elseif ($decoded !== NULL || is_numeric($url)) {
            $url = (!empty($decoded['Url'])) ? $decoded['Url'] : $url;
        }

        $params = explode('&', $url);
        
        // faff=1234
        if (([$affSystem, $affId] = explode('=', $params[0], 2)) && $affId) {
            unset($_COOKIE['_aff']);

            Affiliate::identifyAffiliate([
                $affSystem => $affId
            ], false);
            //http_response_code(403);var_dump(456);exit;
            return is_numeric($affId) ? +$affId : $affId;
        }

        return !empty($params[0]) ? $params[0] : null;
    }

    public static function setGlobalAffiliateCookie($system, $id, string $affiliateUrl = '')
    {
        $cookie = [
            'system' => $system,
            'id' => $id,
            'data' => '',
            'params' => $system.'='.$id,
        ];

        $affiliateUrl = json_decode($affiliateUrl, JSON_OBJECT_AS_ARRAY);
        $affiliateUrl = explode('&', $affiliateUrl['Url'] ?? '', 2);
        if (isset($affiliateUrl[1])) {
            $cookie['data'] = $affiliateUrl[1];
        }

        $cookie_str = http_build_query($cookie);
        $globalAffCookieSecPeriod = time() + (_cfg('globalAffCookieSecPeriod') ?: 60 * 60 * 24 * 30);

        if (self::sendCookie("_aff", $cookie_str, "/", $globalAffCookieSecPeriod, !empty($_SERVER['AFF_DOMAIN']) ? $_SERVER['AFF_DOMAIN'] : "")) {
            self::$_aff_cookie = $cookie_str;
        }
    }

    public static function getSystem()
    {
        return self::$affiliateSystem;
    }

    public static function setSystem($system)
    {
        self::$affiliateSystem = $system;
    }

    public static function getRedirectUrl()
    {
        return self::$redirectUrl;
    }

    public static function getCookieDaysPeriod()
    {
        return self::$cookie_days_period;
    }

    protected static function isFaffUniqueVisitor($affiliateId, $userIP)
    {
        if (isset($_COOKIE['faff']) && $_COOKIE['faff'] == $affiliateId) {
            return false;
        }

        $query = 'SELECT id
                  FROM affiliate_hits
                  WHERE ip="' . Db::escape($userIP) . '"
                  AND affiliate_id="' . Db::escape($affiliateId) . '"
                  AND affiliate_system="faff"
                  AND add_date > NOW() - INTERVAL 1 HOUR
                  LIMIT 1';

        $queryResult = Db::fetchRow($query);

        return empty($queryResult->id);
    }

    public static function affiliateUniqueVisitor($affiliateId, $affiliateClickId)
    {
        $url = '/WLC/Visitor?';
        $system = System::getInstance();
        $transactionId = $system->getApiTID($url);

        $hash = md5('WLC/Visitor/0.0.0.0/' . $transactionId . '/' .
            _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass')
        );
        $params = array(
            'AffiliateID' => $affiliateId,
            'AffiliateClickID' => $affiliateClickId,
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
        );

        $url .= '&' . http_build_query($params);
        $response = $system->runFundistAPI($url);

        return $response;
    }

    protected static function sendCookie(string $key, string $value, string $path = '/', ?int $ttl = null, string $domain = ""): bool {
        $ttlCookie = $ttl ?? time() + 24 * 60 * 60 * self::$cookie_days_period;

        return setcookie($key, $value, $ttlCookie, $path, $domain);
    }

    protected static function removeCookie(string $key): void {
        setcookie($key, '', time() - 3600);
    }

    public static function setAffilkaCookie(string $id, string $data): void
    {
        $affData = [
            'system' => 'affilka',
            'id' => Utils::encodeURIComponent($id),
            'data' =>  Utils::encodeURIComponent($data),
            'params' => Utils::encodeURIComponent("stag={$id}_{$data}")
        ];
        $affiliateCookieValue = "system={$affData['system']}&id={$affData['id']}&data={$affData['data']}&params={$affData['params']}";

        self::$affiliateSystem = 'affilka';
        self::$_aff_cookie = $affiliateCookieValue;
        self::sendCookie('_aff', $affiliateCookieValue, "/", null, !empty($_SERVER['AFF_DOMAIN']) ? $_SERVER['AFF_DOMAIN'] : "");
    }
}
