<?php
namespace eGamings\WLC;

class Games extends System
{
    public static $gamesListFile = 'gamesList.json';
    public static $cacheKey = 'gamesList';
    public const FRESH_GAMELIST_TIME = 60*60; // 1h


    function Load()
    {
        if (!isset($_POST['merchant']) || !$_POST['merchant']) {
            $_POST['merchant'] = 0;
        }

        return $this->getGames($_POST['merchant'], (isset($_POST['category']) ? $_POST['category'] : 0));
    }

    public function getLastWins($data)
    {
        $url = '/WLCGames/LastWins/?';

        $transactionId = $this->getApiTID($url);

        $hash = md5('WLCGames/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'TID' => $transactionId,
            'Hash' => $hash,
        );

        if (isset($data['merchant']) && $data['merchant']) {
            $params['IDMerchant'] = $data['merchant'];
        }
        if (isset($data['min']) && $data['min']) {
            $params['Minimum'] = $data['min'];
        }
        if (isset($data['currency']) && $data['currency']) {
            $params['Currency'] = $data['currency'];
        }
        if (isset($data['platform'])) {
            $params['platform'] = $data['platform'];
        }
        if (isset($data['limit'])) {
            $params['limit'] = $data['limit'];
        }

        $url .= '&' . http_build_query($params);

        $responseData = $this->runFundistAPI($url);
        $response = explode(',', $responseData, 2);

        $language = _cfg('language');

        if (!is_numeric($response[0]) || $response[0] !== '1') {
        	return [];
        }

        $result = json_decode($response[1], true);

        if (!is_array($result)) {
        	return [];
        }

        foreach ($result as &$r) {
            if (!empty($r['Email'])) {
                $r['EmailHash'] = md5(mb_strtolower($r['Email']));
                $r['EmailOriginal'] = $r['Email'];
                $r['Email'] = Utils::obfuscateEmail($r['Email']);
            }

            if (!empty($r['LastName'])) {
                $r['LastNameOriginal'] = $r['LastName'];
                $r['LastName'] = mb_substr($r['LastName'], 0, 1);

                /**
                 * Return game name as single string instead of array
                 */
                if (!empty($data['single']) && !empty($r['Game']) && is_array($r['Game'])) {
                    $r['Game'] = !empty($r['Game'][$language]) ? $r['Game'][$language] : $r['Game']['en'];
                }
            }
        }

        return $result;
    }

    public function getMonthTop($data)
    {
        $url = '/WLCGames/MonthTop/?';

        $transactionId = $this->getApiTID($url);

        $hash = md5('WLCGames/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'TID' => $transactionId,
            'Hash' => $hash,
        );

        if (isset($data['limit']) && $data['limit']) {
            $params['Limit'] = $data['limit'];
        }

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);
        $responseNum = substr($response, 0, 1);

        if (is_numeric($responseNum[0])) {
            $answer = json_decode(substr($response, 2));

            foreach ($answer as &$a) {
                if ($a->Email != '') {
                    $mail = explode('@', $a->Email);
                    $mail[0] = substr($mail[0], 0, 2) . '***';
                    $a->Email = implode('@', $mail);
                }
                if ($a->LastName != '') $a->LastName = mb_substr($a->LastName, 0, 1);
            }

            return $answer;
        }

        return $response;
    }

    public function getTopWins($data)
    {
        $url = '/WLCGames/TopWins/?';

        $transactionId = $this->getApiTID($url);

        $hash = md5('WLCGames/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'TID' => $transactionId,
            'Hash' => $hash,
        );

        if (isset($data['merchant']) && $data['merchant']) {
            $params['IDMerchant'] = $data['merchant'];
        }
        if (isset($data['min']) && $data['min']) {
            $params['Minimum'] = $data['min'];
        }
        if (isset($data['currency']) && $data['currency']) {
            $params['Currency'] = $data['currency'];
        }
        if (isset($data['platform'])) {
            $params['platform'] = $data['platform'];
        }

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);
        $responseNum = substr($response, 0, 1);

        if (is_numeric($responseNum[0])) {
            $answer = json_decode(substr($response, 2));

            if ($data['limit'] != 0) {
                array_splice($answer, $data['limit']);
            }

            foreach ($answer as &$a) {
                if ($a->Email != '') {
                    $mail = explode('@', $a->Email);
                    $mail[0] = substr($mail[0], 0, 2) . '***';
                    $a->Email = implode('@', $mail);
                }
                if ($a->LastName != '') $a->LastName = mb_substr($a->LastName, 0, 1);
            }

            return $answer;
        }

        return $response;
    }

    public static function getGamesFullListRaw() {
        $gameListFile = _cfg('cache') . DIRECTORY_SEPARATOR . self::$gamesListFile;
        if (!file_exists($gameListFile) && !self::fetchGamesFullList()) {
            return null;
        }

        return (object) json_decode(file_get_contents($gameListFile), true, 512, JSON_THROW_ON_ERROR);
    }

    public function getGamesData($merchant = 0, $categoryId = 0, $sort = '')
    {
        $category = ['ID' => 0, 'Tag' => ''];
        if (is_numeric($categoryId)) {
            $category = ['ID' => $categoryId, 'Tag' => ''];
        } else if (is_string($categoryId)) {
            $category = ['ID' => 0, 'Tag' => $categoryId];
        }

        try {
            $data = self::getGamesFullListRaw();
        } catch(\Exception $e) {
            error_log('Game file gamesList.json is corrupted');
            Games::DropCache(false);
        }

        if (!is_object($data)) {
            return false;
        }

        if ($sort === 'desc') {
            $data->games = array_reverse($data->games, true);
        }

        if (_cfg('gamesImages')) {
            $gamesImages = _cfg('gamesImages');
            foreach ($data->games as &$v) {
                if (array_key_exists($v['ID'], $gamesImages)) {
                    $v['Image'] = _cfg('img') . '/games/' . $gamesImages[$v['ID']];
                }
                unset($v);
            }
        }

        if (!empty($category['ID']) && $category['ID'] != 0) {
            foreach ($data->games as $k => $v) {
                if (!in_array($category['ID'], $v['CategoryID'])) {
                    unset($data->games[$k]);
                }
            }
        } else if (!empty($category['Tag'])) {
            $IDs = [];
            foreach ($data->categories as $k => $v) {
                if (is_array($v['Tags']) && in_array($category['Tag'], $v['Tags'])) {
                    $IDs[] = $v['ID'];
                } else {
                    unset($data->categories[$k]);
                }
            }

            foreach ($data->games as $k => $v) {
                $hide = true;
                foreach ($IDs as $c => $id) {
                    if (in_array($id, $v['CategoryID'])) $hide = false;
                }
                if ($hide) {
                    unset($data->games[$k]);
                }
            }
        }

        if ($merchant != 0) {
            foreach ($data->games as $k => $v) {
                if (
                    empty($v['MerchantID'])
                    || (
                        $v['MerchantID'] != $merchant
                        && (
                            empty($v['SubMerchantID'])
                            || $v['SubMerchantID'] != $merchant
                        )
                    )
                ) {
                    unset($data->games[$k]);
                }
            }
        }

        $data->games = self::transformUrlByDeviceType($data->games ?? []);

        System::hook('api:games:list', $data);

        return $data;
    }

    public static function transformUrlByDeviceType(array $games, ?bool $overrideIsMobile = null): array
    {
        $isMobile = $overrideIsMobile ?? Utils::isMobile();

        foreach ($games as $k => &$v) {
            $url = $isMobile ? (!empty($v['MobileUrl']) ? $v['MobileUrl'] : '') : (!empty($v['Url']) ? $v['Url'] : '');

            $urlParams = explode('/', $url);

            if (empty($url) || (isset($urlParams[1]) && trim($urlParams[1]) === '')) {
                unset($games[$k]);
                continue;
            }

            $v['Url'] = $url;
        }

        return $games;
    }

    static function fetchGamesFullList($force = false)
    {
    	$file = _cfg('cache') . DIRECTORY_SEPARATOR . self::$gamesListFile;
        $freshLifeTime = empty(_cfg('GameListFreshLifeTime')) ? self::FRESH_GAMELIST_TIME : _cfg('GameListFreshLifeTime');

    	if (
            !$force &&
            file_exists($file) &&
            filesize($file) != 0 &&
            !isset($_GET['force']) &&
            filemtime($file) + $freshLifeTime > time()
        ) {

    		return false;
    	}

    	$gamesList = self::getGamesFullList();
    	if (!is_array($gamesList) || empty($gamesList)) {
            return false;
        }

        $gameImageLocation = _cfg('gameImageLocation');
    	if ($gameImageLocation != '' && is_array($gamesList) && is_array($gamesList['games'])) {
    		$imagePrefix = '/gstatic/';
    		$imagePrefixLen = strlen($imagePrefix);
    		foreach($gamesList['games'] as &$game) {
    			if (empty($game['Image']) || strpos($game['Image'], $imagePrefix) !== 0) continue;
    			$game['Image'] = $gameImageLocation . substr($game['Image'], $imagePrefixLen);
    		}
    	}

    	$data = json_encode($gamesList, JSON_UNESCAPED_UNICODE);

    	return Utils::atomicFileReplace($file, $data);
    }

    static function getGamesList()
    {
    	$system = System::getInstance();
    	$url = '/Game/List?';

    	$transactionId = $system->getApiTID($url);

    	$hash = md5('Game/List/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
    	$params = Array(
    			'TID' => $transactionId,
    			'Hash' => $hash,
    			);

    	$url .= '?&' . http_build_query($params);
    	$response = $system->runFundistAPI($url);

    	$games = json_decode($response, true);
    	if (!$games || !is_array($games)) {
            // Log catalog error
            Logger::log("Unable fetch game list catalog: " . $response);
            return false;
    	}

    	return $games;
    }

    static function getGamesFullList()
    {
        $url = '/Game/FullList';
        $system = System::getInstance();

    	$transactionId = $system->getApiTID($url);

    	if (!_cfg('gameImageWidth')) {
    		$width = 208; //by default it will be 208
    	} else {
    		$width = (int)_cfg('gameImageWidth');
    	}

    	$hash = md5('Game/FullList/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
    	$params = [
    		'TID' => $transactionId,
    		'Hash' => $hash,
    		'ImageWidth' => $width
    	];

    	$url .= '?&' . http_build_query($params);
    	$response = $system->runFundistAPI($url);

    	$list = json_decode($response, true);
    	if (!$list || !is_array($list)) {
            // Log catalog error
            Logger::log("Unable fetch game full list: " . $response);
            return false;
    	}

    	/*
    	 * $list - data array;
    	 * $list['categories'] - list of game categories;
    	 * $list['games'] - list of games;
    	 * $list['merchants'] - list of merchants;
    	 * $list['countriesRestrictions'] - list of banned countries for games;
    	 */

    	return $list;
    }

    public function getGames($merchant = 0, $category = 0, $sort = '')
    {
        $data = $this->getGamesData($merchant, $category, $sort);

        return json_encode($data);
    }

    public function getLatestGames($IDUser, $platform = null, int $limit = null)
    {

        $url = '/WLCGames/Latest/?';

        $transactionId = $this->getApiTID($url);

        $hash = md5('WLCGames/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'Login' => $IDUser,
            'TID' => $transactionId,
            'Hash' => $hash,
        );
        if ($platform) {
            $params['platform'] = $platform;
        }

        if ($limit) {
            $params['limit'] = $limit;
        }

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);

        $data = explode(',', $response);

        if ($data[0] === '0') {
            return $data[1];
        } else if ($data[0] != 1) {
            return $data[1];
        }

        array_shift($data);
        $data = implode(',', $data);

        $data = json_decode($data, true);

        $imagesLocation = _cfg('gameImageLocation');
        if (!$imagesLocation) {
            $imagesLocation = '/gstatic/';
        }

        if (_cfg('gamesImages')) {
            $gamesImages = _cfg('gamesImages');
            foreach ($data as &$v) {
                if (array_key_exists($v['ID'], $gamesImages)) {
                    $v['Image'] = _cfg('img') . '/games/' . $gamesImages[$v['ID']];
                } else {
                    $v['Image'] = $imagesLocation . $v['Image']; //used nginx caching via static.egamings.com
                }
                unset($v);
            }
        } else {
            foreach ($data as &$v) {
                $v['Image'] = $imagesLocation . $v['Image']; //used nginx caching via static.egamings.com
                unset($v);
            }
        }

        return $data;
    }

    public function getGameCategories()
    {
        $url = '/Game/Categories?';

        $transactionId = $this->getApiTID($url);

        $hash = md5('Game/Categories/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'TID' => $transactionId,
            'Hash' => $hash,
        );

        $url .= '?&' . http_build_query($params);
        $response = $this->runFundistAPI($url);

        $cat = json_decode($response, 1);
        if (!$cat || !is_array($cat)) return array();

        return $cat;
    }

    public function getJackpots($data)
    {
        $gamesData = $this->getGamesData();

        if (!$gamesData || empty($gamesData->merchants)) return '[]';
        elseif (!empty($data['merchant'])) {
            $availableMerchants = array_keys((array)$gamesData->merchants);
            if(!in_array($data['merchant'],$availableMerchants)) return '[]';
        }

        $url = '/WLCGames/Jackpots/?';

        $transactionId = $this->getApiTID($url);

        $hash = md5('WLCGames/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'TID' => $transactionId,
            'Hash' => $hash,
        );

        if (isset($data['currency']) && $data['currency']) {
            $params['Currency'] = $data['currency'];
        }
        if (!empty($data['merchant'])) {
            $params['IDMerchant'] = $data['merchant'];
        }

        $params['Platform'] = $data['platform'];

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);

        $data = explode(',', $response);

        if ($data[0] === '0') {
            return $data[1];
        } else if ($data[0] != 1) {
            return '[]';
        }

        array_shift($data);

        $data = implode(',', $data);
        return $data;
    }

    //Deprecated function
    public function Redirect($IDMerchant, $Game = '')
    {
        $demo = 0;
        if (!Front::User('id') || (!empty($_GET['isDemo']) && $_GET['isDemo'] == 1)) {
            $userLogin = '$DemoUser$';
            $userPass = 'demo';
            $demo = 1;
        } else {
            $userLogin = (int)Front::User('id');
            $userPass = Front::User('api_password');
        }

        $url = '/User/DirectAuth/?&Login=' . $userLogin . ($demo == 1 ? '&Demo=1' : null);
        $transactionId = $this->getApiTID($url);

        //User/DirectAuth/[IP]/[TID]/[KEY]/[LOGIN]/[PASSWORD]/[SYSTEM]/[PWD]
        $hash = md5('User/DirectAuth/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $userLogin . '/' . $userPass . '/' . $IDMerchant . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'Password' => $userPass,
            'TID' => $transactionId,
            'Page' => $Game,
            'System' => $IDMerchant,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
            'AdditionalUserIP' => json_encode(System::getUserIP(true)),
            'UserAgent' => !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown',
        );

        if ($demo == 1) {
            $params['Demo'] = 1;
        }

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);
        $data = explode(',', $response);

        if ($data[0] != 1) {
            return 'no';
        }

        array_shift($data);

        $data = implode(',', $data);
        return $data;
    }

    public function LaunchHTML($IDMerchant, $Game, $isDemo = null, $universal = false, $platform = '', ?int $wallet = null, ?string $currency = null)
    {
        //WORKAROUND: compatibility workaround. Remove after switching to new method signature and
        //change default for $isDemo to false.
        if (is_null($isDemo)) {
            $isDemo = isset($_GET['isDemo']) ? $_GET['isDemo'] : false;
        }

        if (!Front::User('id') || $isDemo) {
            $userLogin = '$DemoUser$';
            $userPass = 'demo';
        } else {
            $userLogin = (int)Front::User('id');
            $userPass = Front::User('api_password');
        }

        $url = '/User/AuthHTML/?';
        $transactionId = $this->getApiTID($url);

        //User/DirectAuth/[IP]/[TID]/[KEY]/[LOGIN]/[PASSWORD]/[SYSTEM]/[PWD]
        $hash = md5('User/AuthHTML/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $userLogin . '/' . $userPass . '/' . $IDMerchant . '/' . _cfg('fundistApiPass'));
        $params = [
            'Login' => $userLogin,
            'Password' => $userPass,
            'TID' => $transactionId,
            'Page' => $Game,
            'System' => $IDMerchant,
            'Hash' => $hash,
            'Language' => _cfg('language'),
            'UserIP' => System::getUserIP(),
            'AdditionalUserIP' => json_encode(System::getUserIP(true)),
            'UserAgent' => !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown',
            'UniversalLaunch' => $universal ? 1 : 0,
            'Referer' => !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
            'Platform' => $platform,
            'IsMobile' => (_cfg('mobile') || _cfg('mobileDetected')) ? 1 : 0,
        ];

        if (null !== $wallet) {
            $params['Wallet'] = $wallet;
        }

        if (null !== $currency) {
            $params['Currency'] = $currency;
        }

        if ($userLogin == '$DemoUser$') {
            $params['Demo'] = 1;
        }

        if ($hooked = System::hook('api:games:before:launch', $params, System::getGeoData())) {
            $params = $hooked;
        }

        $url .= '&' . http_build_query($params);
        $response = $this->runFundistAPI($url);

        if ($hooked = System::hook('api:games:after:launch', $response, $hooked, System::getGeoData())) {
            $response = $hooked;
        }

        $data = explode(',', $response, 2);

        if ((int)$data[0] !== 1) {
            $code = $data[0];
            $data = explode(',', $data[1]);
            switch($code){
                case 24:
                    $translates = [
                        'Not allowed player country' => _('Not allowed player country'),
                        'Restricted country' => _('Not allowed player country'),
                        'Demo not supported' => _('Demo not supported'),
                        'Currency not supported' => _('Your currency is not supported by this provider'),
                        'Branded games are not enabled' => _('Branded games are not enabled')
                    ];
                    if(!empty($data[1]) && array_key_exists($data[1], $translates)){
                        return ['error' => $translates[$data[1]] ];
                    }
                    if($data[0] == 'self-exclusion'){
                        return ['error' => !empty($data[1]) ? str_replace('#DATE#', $data[1], _('Self exclusion date') ) : _('Self exclusion')  ];
                    }
                    break;
                default:
                    break;
            }
            return ['error' => !empty($data[1]) ? $data[1] : (!empty($data[0]) ? $data[0] : '')];
        }

        return $data[1];
    }

    public function addRemoveGameFavorites($id)
    {
        if (!Front::User('id')) {
            return false;
        }
        if (Db::fetchRow('SHOW TABLES LIKE "users_favorites"')===false) {
            return false;
        }

        if (Db::fetchRow('SELECT * FROM `users_favorites` where ' .
            '`user_id` = ' . (int)Front::User('id') . ' and  ' .
            '`game_id` = ' . (int)$id.
            ' LIMIT 1')===false){
            Db::query('INSERT INTO `users_favorites` SET ' .
                '`user_id` = ' . (int)Front::User('id') . ', ' .
                '`game_id` = ' . (int)$id
            );
            return array('game_id' => $id, 'favorite' => 1);
        } else {
            Db::query('DELETE FROM `users_favorites` WHERE ' .
                '`user_id` = ' . (int)Front::User('id') . ' AND ' .
                '`game_id` = ' . (int)$id
            );
            return array('game_id' => $id, 'favorite' => 0);
        }
    }

    public function getFavoritesGames()
    {
        if (!Front::User('id')) {
            return false;
        }

        $result = [];
        if (Db::fetchRow('SHOW TABLES LIKE "users_favorites"')) {
            //Fetching favoriteGames data only if table exists, not required for some WLCs
            $favQuery = 'SELECT DISTINCT `game_id` FROM `users_favorites` WHERE `user_id` = "' . (int)Front::User('id') . '"';
            $userFavoriteGames = Db::fetchRows($favQuery);
            if (!empty($userFavoriteGames)) {
                $result = (array) $userFavoriteGames;
            }
        }

        return $result;
    }

    /**
     * Drop games cache
     *
     * @param bool $isPrintMessage Whether to display a message "OK" via echo
     */
    public static function DropCache(bool $isPrintMessage = true): void
    {
        self::fetchGamesFullList(true);
        GZ::setForceUpdate();
        GZ::makeMinFiles();

        $keywords = [self::$cacheKey, 'api:games:list'];
        foreach ($keywords as $keyword) {
            Cache::dropCacheKeys( $keyword );
        }

        if ($isPrintMessage) {
            echo 'OK';
        }
    }

    public static function dropWinsCache()
    {
        self::fetchGamesFullList(true);

        $keywords = ['api-wins-history', 'api-wins'];
        foreach ($keywords as $keyword) {
            Cache::dropCacheKeys($keyword);
        }
    }

    public static function dropGamesSortingCache ()
    {
        $keywords = ['api_games_sorting_new', 'api_games_sorting_popular'];
        foreach ($keywords as $keyword) {
            Cache::dropCacheKeys($keyword);
        }
    }

    /**
     * Get game file time for Bootstrap
     *
     * @return array
     */
    public static function getGameFilesTime(): array
    {
        $gameList = _cfg('cache') . DIRECTORY_SEPARATOR . self::$gamesListFile;
        $gzSlimFile = _cfg('cache') . DIRECTORY_SEPARATOR . GZ::fileNames('slim') . '.json.gz';

        return [
            'gameList' => file_exists($gameList) ? gmdate('d.m.Y H:i:s', filemtime($gameList)) : '-',
            'gzFiles' => file_exists($gzSlimFile) ? gmdate('d.m.Y H:i:s', filemtime($gzSlimFile)) : '-',
        ];
    }

    /**
     * @return void
     */
    public static function dropGamesSortsCache(): void
    {
        $keywords = [
            'api_sorts_for_games_all_none',
            'api_sorts_for_games_all_new',
            'api_sorts_for_games_all_popular',
            'api_sorts_for_games_auto_new',
            'api_sorts_for_games_auto_popular',
        ];

        foreach ($keywords as $keyword) {
            Cache::dropCacheKeys($keyword);
        }

        echo 'OK';
    }
}
