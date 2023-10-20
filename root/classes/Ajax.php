<?php
namespace eGamings\WLC;

use eGamings\WLC\Cache;

class Ajax extends System
{
    public $user;
    protected $template;

    public function __construct() {
        parent::__construct();
        parent::startGetText();
        parent::initApiUrl();

        $this->user = new User;
    }

    public function ajaxRun($data)
    {
        $controller = $data['control'];

        if (method_exists($this, $controller)) {
            $result = $this->$controller($data);
            echo is_array($result) ? json_encode($result) : $result;

            return true;
        } else {
            echo _('controller_does_not_exists');

            return false;
        }
    }

    public function isLogged($data)
    {
        if (isset($_SESSION['user']) && $_SESSION['user'] && $_SESSION['user']['id'] && $_SESSION['user']['id'] != 0) {
            return 1;
        }

        return 0;
    }

    public function updateTheme($data)
    {
        $num = 1;
        if ($data['num'] >= 1 || $data['num'] <= 8) {
            $num = (int)$data['num'];
        }
        Db::query(
            'UPDATE `users_data` SET ' .
            '`background` = ' . $num . ' ' .
            'WHERE `user_id` = ' . (int)Front::User('id') . ' ' .
            'LIMIT 1 '
        );

        return true;
    }

    public function getJackpot($data)
    {
        if (!file_exists(_cfg('cache') . '/jackpotsList.json')) {
            $G = new Games();
            $Jackposts = $G->getJackposts();
            print_r($Jackposts);

            die();
        }
    }

    public function getLastWins($data)
    {

        $res = Cache::result(
            'last_wins',
            function () use ($data) {
                $G = new Games();
                $params = array(
                    'merchant' => isset($data['merchant']) ? (int)$data['merchant'] : 0,
                    'limit' => isset($data['limit']) ? (int)$data['limit'] : 3,
                    'min' => isset($data['min']) ? (int)$data['min'] : 50,
                    'currency' => isset($data['currency']) ? $data['currency'] : '',
                );

                $games = $G->getLastWins($params);
                if (!is_array($games) || empty($games)) return '';

                return json_encode($games);
            },
            60, // ttl 1 min
            array($data)
        );

        return $res;
    }

    public function getTopWins($data)
    {
        $res = Cache::result(
            'top_wins',
            function () use ($data) {
                $G = new Games();
                $params = array(
                    'merchant' => isset($data['merchant']) ? (int)$data['merchant'] : 0,
                    'limit' => isset($data['limit']) ? (int)$data['limit'] : 3,
                    'min' => isset($data['min']) ? (int)$data['min'] : 50,
                    'currency' => isset($data['currency']) ? $data['currency'] : '',
                );

                $games = $G->getTopWins($params);
                if (!is_array($games) || empty($games)) return '';

                return json_encode($games);
            },
            300, // ttl 5 min
            array($data)
        );

        return $res;
    }

    public function getMonthTop($data)
    {
        $res = Cache::result(
            'month_top',
            function () use ($data) {
                $G = new Games();
                $params = array(
                    'limit' => isset($data['limit']) ? (int)$data['limit'] : 10,
                );

                $games = $G->getMonthTop($params);
                if (!is_array($games) || empty($games)) return '';

                return json_encode($games);
            },
            600, // ttl 10 min
            array($data)
        );

        return $res;
    }

    public function connectSocialAccount($data)
    {
        $answer = Front::Social($data['provider']);

        if (!$answer) {
            $answer = '0;' . _('social_not_found');
        } else {
            $answer = '1;' . $answer;
        }

        return $answer;
    }

    public function disconnectSocialAccount($data)
    {
        $answer = User::socialDisconnect($data);

        if ($answer !== true) {
            $answer = '0;' . $answer;
        } else {
            $answer = '1;1';
        }

        return $answer;
    }

    /**
    * @return array('Items' => array, 'Categories' => array)
    */
    public function Store()
    {
        $path = 'Store/Get';
        $params = array();

        $store = json_decode($this->runLoyaltyAPI($path, $params), 1);

        return $store;
    }

    public function store_orders()
    {
        $path = 'Store/Orders';
        $params = $_POST;

        if (!Front::User('fundist_uid')) {
            return json_encode(array('error' => "No user id"));
        }
        $params['IDUser'] = Front::User('fundist_uid');

        $orders = $this->runLoyaltyAPI($path, $params);

        return $orders;
    }

    public function store_buy($data = [])
    {
        $url = '/WLCInfo/Store/Buy?';

        $transactionId = $this->getApiTID($url);

        $hash = md5('WLCInfo/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . Front::User('id') . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'IDItem' => isset($data['IDItem']) ? $data['IDItem'] : $_POST['IDItem'],
            'Login' => Front::User('id'),
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
        );

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);

        $data = explode(',', $response, 2);

        if ($data[0] == 1) {
            return '1;' . $data[1];
        } else {
            return '0;' . _('purchase_error');
        }
    }

    public function addGameFavorite($data)
    {
        if (!Front::User('id')) {
            return false;
        }

        //Limit not required... for now?
        /*$row = Db::fetchRow('SELECT COUNT(*) AS `count` FROM `users_favorites` WHERE '.
            '`user_id` = '.(int)Front::User('id').' '
        );

        if ($row->count >= 10) {
            return _('max_num_favorited_added');
        }*/

        Db::query('INSERT INTO `users_favorites` SET ' .
            '`user_id` = ' . (int)Front::User('id') . ', ' .
            '`game_id` = ' . (int)$data['game_id']
        );

        return _('game_added_to_favorites');
    }

    public function removeGameFavorite($data)
    {
        if (!Front::User('id')) {
            return false;
        }

        Db::query('DELETE FROM `users_favorites` WHERE ' .
            '`user_id` = ' . (int)Front::User('id') . ' AND ' .
            '`game_id` = ' . (int)$data['game_id']
        );

        return _('game_deleted_from_favorites');
    }

    public function loyalty($data)
    {

        $path = 'Loyalty/Get';
        $params = $_POST;

        return $this->runLoyaltyAPI($path, $params);
    }

    public function getUserInfo()
    {
        $Login = Front::User('id');
        $data = Cache::result('fundist_user_balance', function() use ($Login) {
                return User::getInfo($Login, false);
        }, 5, array('User', 'getInfo', $Login));

        $user = Front::User();
        $balance = isset($data['balance']) ? $data['balance'] : 0;
        $profile = 'ok';

        if (is_object($user) && $balance > 0) {
            if (!$user->Name || !$user->LastName || !$user->Phone) {
                $profile = 'empty';
            }
        }

        return '{"Balance":"' . (isset($data['balance']) ? $data['balance'] : 0) . '",
                  ' . (isset($data['openPositions'])  && is_array($data['openPositions']) ? '"openPositions":'.json_encode($data['openPositions']).',' : '') . '
                  ' . (isset($data['additional'])  && is_array($data['additional']) ? '"additional":'.json_encode($data['additional']).',' : '') . '
                 "Loyalty":' . (isset($data['loyalty']) && is_array($data['loyalty']) ? json_encode($data['loyalty']) : '{}') . ',"Profile":"' . $profile . '"}';
    }

    public function bonus_cancel($data = [])
    {
        if (!Front::User('fundist_uid')) {
            return _('must_login');
        }

        $url = '/WLCInfo/Bonus/Cancel?';

        $transactionId = $this->getApiTID($url);

        $hash = md5('WLCInfo/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . Front::User('id') . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'IDBonus' => isset($data['IDBonus']) ? $data['IDBonus'] : $_POST['IDBonus'],
            'LBID' => isset($data['LBID']) ? $data['LBID'] : (isset($_POST['LBID']) ? $_POST['LBID'] : 0),
            'Login' => Front::User('id'),
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
        );

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);

        $data = explode(',', $response, 2);

        if ($data[0] == 1) {
            $arr = array('balance' => $data[1]);
        } else {
            $arr = array('error' => dgettext('loyalty', $data[1]));
        }

        return json_encode($arr);
    }

    public function bonus_history()
    {
        if (!Front::User('fundist_uid')) {
            return _('must_login');
        }

        $path = 'Bonuses/History';
        $params = ['IDUser' => Front::User('fundist_uid')];

        $bonus = $this->runLoyaltyAPI($path, $params);
        $bonus = json_decode($bonus, 1);

        $lang_check = array(
            'Name' => _('No bonus name'),
            'Description' => _('No bonus descripton'),
            'Image' => '',
            'Image_promo' => '',
            'Image_main' => '',
            'Image_description' => '',
            'Image_reg' => '',
            'Image_store' => '',
            'Image_deposit' => '',
            'Image_other' => '',
        );

        foreach ($bonus as $k => $v) {
            foreach ($lang_check as $field => $default) {
                $tmp = $v[$field];

                if (is_array($tmp)) {
                    if (isset($tmp[_cfg('language')])) {
                        $tmp = $tmp[_cfg('language')];
                    } elseif (isset($tmp['en'])) {
                        $tmp = $tmp['en'];
                    } else {
                        $tmp = $default;
                    }

                    $bonus[$k][$field] = $tmp;
                }
            }
        }

        return json_encode($bonus);
    }

    public function Bonus($data = '') {

        if ($data != '' && isset($data['IDBonus'])) {
            if (!Front::User('fundist_uid')) {
                return _('must_login');
            }

            $url = '/WLCInfo/Bonus/Select?';

            $transactionId = $this->getApiTID($url);

            $hash = md5('WLCInfo/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . Front::User('id') . '/' . _cfg('fundistApiPass'));
            $params = Array(
                'IDBonus' => $data['IDBonus'],
                'Status' => $data['Status'],
                'Login' => Front::User('id'),
                'TID' => $transactionId,
                'Hash' => $hash,
                'UserIP' => System::getUserIP(),
                'Country' => System::getGeoData(),
            );
            if (isset($data['PromoCode'])) $params['PromoCode'] = strtolower($data['PromoCode']);

            $url .= '&' . http_build_query($params);

            $response = $this->runFundistAPI($url);
            $data = explode(',', $response, 2);

            if ($data[0] != 1) {
                $errors_arr = [
                    'No user id' => _('No user id'),
                    'No bonus id' => _('No bonus id'),
                    'No status' => _('No status'),
                    'Bonus not found' => _('Bonus not found'),
                    'Already have active' => _('Already have active'),
                    'Bonus is not available' => _('Bonus is not available'),
                    'First deposit, already deposited' => _('First deposit, already deposited'),
                    'No loyalty record' => _('No loyalty record'),
                    'Dont satisfy bonus conditions' => _('Dont satisfy bonus conditions'),
                    'Bonus used' => _('Bonus used'),
                    'Promo code needed' => _('Promo code needed'),
                    'Wrong promo code' => _('Wrong promo code'),
                    'Bonuses are forbidden for user' => _('Bonuses are forbidden for user'),
                    'Already have an active bonus' => _('Already have an active bonus'),
                ];
                return isset($errors_arr[$data[1]]) ? $errors_arr[$data[1]] : _($data[1]);
            }

            return $data[1];
        } else {
            $path = 'Bonuses/Get';
            $params = ['ForWLC' => 1];
            $fundist_uid = Front::User('fundist_uid');
            if ($fundist_uid) {
                $params['IDUser'] = $fundist_uid;
                $params['UserTags'] = !empty($_SESSION['user']['UserTags']) ? $_SESSION['user']['UserTags'] : '';
            } else {
                $params['CheckAffiliates'] = true;
                $params['AffiliateData'] = Affiliate::getAffiliateData();
            }
            $params['Country'] = System::getGeoData();
            if (isset($data['PromoCode'])) $params['PromoCode'] = strtolower($data['PromoCode']);
            if (!empty($data['event']) && in_array($data['event'], ['store', 'registration'])) {
                $params['Event'] = $data['event'];
            }
            $params['ExcludeHeavyFields'] = !empty(_cfg('bonusesExcludeHeavyFields'));
            if (!empty($data['lang'])) {
                $params['Lang'] = $data['lang'];
            }
            if (!empty($data['type']) && $data['type'] == 'lootboxPrizes') {
                $params['Type'] = 'lootboxPrizes';
            }
            $bonus = $this->runLoyaltyAPI($path, $params);

            $lang_check = array(
                'Name' => _('No bonus name'),
                'Description' => _('No bonus descripton'),
                'Image' => '',
                'Image_promo' => '',
                'Image_main' => '',
                'Image_description' => '',
                'Image_reg' => '',
                'Image_store' => '',
                'Image_deposit' => '',
                'Image_other' => '',
                'Terms' => ''
            );

            $bonus = json_decode($bonus, 1);

            foreach ($bonus as $k => $v) {
                if ($bonus[$k]['Selected'] == 0
                    && $bonus[$k]['Active'] == 0
                    && $bonus[$k]['Inventoried'] == 0
                    && (empty($params['Event']) && $bonus[$k]['AllowCatalog'] != 1)
                    && empty($params['Type'])
                ) {
                    unset($bonus[$k]);
                    continue;
                }

                foreach ($lang_check as $field => $default) {
                	if (empty($v[$field]) || !is_array($v[$field])) {
                		continue;
                	}

                    $tmp = $v[$field];

                    if (isset($tmp[_cfg('language')]) && !empty($tmp[_cfg('language')])) {
                        $tmp = $tmp[_cfg('language')];
                    } elseif (isset($tmp['en'])) {
                        $tmp = $tmp['en'];
                    } else {
                        $tmp = $default;
                    }

                    $bonus[$k][$field] = $tmp;
                }

                if (is_array($bonus[$k]['Bonus'])) {
                    $currency = Front::User('currency') ?: 'EUR';

                    if (!array_key_exists($currency, $bonus[$k]['Bonus'])) {
                        $currency = 'EUR';
                    }
                    
                    $bonus[$k]['Bonus'] = $bonus[$k]['Bonus'][$currency];
                }

                $terms_translates = array(
                    '[registration]' => _('registration'),
                    '[verification]' => _('verification'),
                    '[sign up]' => _('sign up'),
                    '[store]' => _('store'),
                    '[login]' => _('login'),
                    '[deposit]' => _('deposit'),
                    '[deposit first]' => _('deposit first'),
                    '[deposit sum]' => _('deposit sum'),
                    '[deposit repeated]' => _('deposit repeated'),
                    '[bet]' => _('bet'),
                    '[bet sum]' => _('bet sum'),
                    '[win sum]' => _('win sum'),
                    '[loss sum]' => _('loss sum'),
                    '[once]' => _('once'),
                    '[day]' => _('day'),
                    '[week]' => _('week'),
                    '[month]' => _('month'),
                    '[all]' => _('all'),
                    '[bonus]' => _('bonus'),
                    '[win]' => _('win'),
                    '[winbonus]' => _('win and bonus'),
                    '[winevent]' => _('winevent'),
                    '[winbonusevent]' => _('winbonusevent'),
                    '[none]' => _('none'),
                    '[balance]' => _('balance'),
                    '[experience]' => _('experience'),
                    '[loyalty]' => _('loyalty'),
                    '[absolute]' => _('absolute'),
                    '[relative]' => _('relative'),
                    '[bets]' => _('bets'),
                    '[wins]' => _('wins'),
                    '[turnovers]' => _('turnover'),
                    '[turnovers_loose]' => _('negative turnover'),
                    '[unlimited]' => _('unlimited'),
                    '[1 day]' => _('one day'),
                    '[2 days]' => _('two days'),
                    '[3 days]' => _('three days'),
                    '[7 days]' => _('seven days'),
                    '[10 days]' => _('ten days'),
                    '[14 days]' => _('fourteen days'),
                    '[30 days]' => _('thirty days'),
                    '[60 days]' => _('sixty days'),
                    '[90 days]' => _('ninety days'),
                    '[1 week]' => _('one week'),
                    '[2 weeks]' => _('two weeks'),
                    '[1 month]' => _('one month'),
                    '[every 1 day]' => _('every day'),
                    '[every 1 week]' => _('every week'),
                    '[every 2 weeks]' => _('every two weeks'),
                    '[every 1 month]' => _('every month'),
                    '[turnover]' => _('turnover'),
                    '[fee]' => _('fee'),
                    '[turnover_fee]' => _('turnover and fee'),
                    '[everyday]' => _('everyday'),
                    '[Mon]' => _('monday'),
                    '[Tue]' => _('tuesday'),
                    '[Wed]' => _('wednesday'),
                    '[Thu]' => _('thursday'),
                    '[Fri]' => _('friday'),
                    '[Sat]' => _('saturday'),
                    '[Sun]' => _('sunday'),
                );

                if (!empty($bonus[$k]['Terms'])) {
                    $bonus[$k]['Terms'] = strtr($bonus[$k]['Terms'], $terms_translates);
                }
            }
            $bonus = array_values($bonus);
        }

        //set cache bonuses a 20 minutes
        $cacheKey = implode(':', ['bonuses', _cfg('fundistApiKey')]);
        if (!empty($data['lang'])) {
            $cacheKey .= ':' . $data['lang'];
        }
        Cache::set($cacheKey, $bonus, 60 * 20);
        return $bonus;
    }

    public function Achievement($data = '')
    {
        $path = 'Achievements/Get';
        $params = [];
        $params['ForWLC'] = 1;

        if (Front::User('fundist_uid')) {
            $params['IDUser'] = Front::User('fundist_uid');
        }

        if (
            !empty($_GET['groups'])
            && $_GET['groups'] == 'true'
        ) {
            $params['groups'] = true;
        }

        $achievement = $this->runLoyaltyAPI($path, $params);

        $achievement = json_decode($achievement, 1);

        return array_values($achievement);
    }

    public function events($data)
    {
        $path = 'Events/' . $_POST['Type'];
        $params = $_POST;

        $params['ID'] = substr(time(), -8);
        $params['IDUser'] = $_POST['Data']['IDUser']; //Front::User('id');
        unset($params['Data']['IDUser']);

        return $this->runLoyaltyAPI($path, $params);
    }

    public function social($data)
    {
        return Front::Social($data['provider']);
    }

    public function games()
    {
        $G = new Games();

        return $G->Load();
    }

    public function fetchEcommpaySystems()
    {
        $url = '/WLCAccount/Payments/Tools/?&Login=' . (int)Front::User('id');

        $url .= '&System=' . _cfg('ecommpayIdInFundist');

        $transactionId = $this->getApiTID($url);

        $hash = md5('WLCAccount/Payments/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . Front::User('id') . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'Password' => $this->user->userData->api_password,
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
        );

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);

        $response = substr($response, 2);

        return $response;
    }

    public function cancelTransaction($data)
    {
        return $this->user->cancelDebet($data);
    }

    public function userTransactions($data)
    {
        $perPage = 10;
        $page = (int)$data['page'];
        if (!isset($this->user->userData) || !$this->user->userData) {
            return '0;<p>' . _('transaction_list_is_empty') . '</p>';
        }

        $dateFrom = 0;
        $dateTo = 0;
        if ($data['dateFrom'] != 0) {
            $dateFrom = strtotime($data['dateFrom']);
        }
        if ($data['dateTo'] != 0) {
            $dateTo = strtotime($data['dateTo']);
        }

        $init = explode(',', $this->user->fetchTransactions());

        if ($init[0] != 1) {
            return '0;<p>' . _('transaction_list_is_empty') . '</p>';
        } else {
            if ($dateFrom > $dateTo) {
                return '0;<p>' . _('date_incorrect_from_date_higher_then_to_date') . '</p>';
            }
        }

        array_shift($init);
        $init = json_decode(implode(',', $init));
        // @codeCoverageIgnoreStart
        $status = array(
            -55 => _('cancelled'),
            -50 => _('rejected'),
            -5 => _('error'),
            0 => _('in_progress'),
            50 => _('confirmed'),
            95 => _('pending'),
            99 => _('system_error'),
            100 => _('completed'),
            'aborted' => _('aborted'),
        );
        // @codeCoverageIgnoreEnd

        $type = array(
            'credit' => _('credit'),
            'debet' => _('debet')
        );
        $rowsTmp = array();
        $rows = array();
        $i = 0;
        $showFrom = $page * 10;
        $showTill = 9 + $page * 10;
        $date = array();
        $checkDate = 0;

        arsort($init);
        foreach ($init as $k => $v) {
            if (!$status[$v->Status]) {
                $v->Status = 1;
                $status[$v->Status] = _('in_progress');
            }

            $date = explode(' ', $v->Date);
            $checkDate = strtotime($date[1]);
            if (
                ($dateFrom == 0 && $dateTo == 0) ||
                ($dateFrom != 0 && $dateTo == 0 && $checkDate >= $dateFrom) ||
                ($dateFrom == 0 && $dateTo != 0 && $checkDate <= $dateTo) ||
                ($dateFrom != 0 && $dateTo != 0 && $checkDate >= $dateFrom && $checkDate <= $dateTo)
            ) {
                $rowsTmp[] = array(
                    'class' => $v->Amount > 0 ? 'credit' : 'debet',
                    'type' => $v->Amount > 0 ? $type['credit'] : $type['debet'],
                    'typeExtra' => $v->Amount > 0 ? 'credit' : 'debet',
                    'cancel' => $v->Status == 0 && $v->Amount < 0 ? 1 : 0,
                    'ID' => $v->ID,
                    'system' => $v->System,
                    'amount' => $v->Amount,
                    'status' => $v->Amount > 0 && $v->Status == 0 ? $status['aborted'] : $status[$v->Status],
                    'statusExtra' => $v->Amount > 0 && $v->Status == 0 ? 'aborted' : $v->Status,
                    'note' => $v->Note != '' ? $v->Note : '',
                    'date' => date('H:i d/m/Y', strtotime($v->Date)),
                );
                ++$i;
            }
        }

        $i = 0;
        foreach ($rowsTmp as $k => $v) {
            if ($i >= $showFrom && $i <= $showTill) {
                $rows[$k] = $v;
            }
            ++$i;
        }

        $rows['numberRecords'] = count($rowsTmp);

        if (empty($rows)) {
            return '0;<p>' . _('transaction_list_is_empty') . '</p>';
        }

        return json_encode($rows);
    }

    public function payments($data)
    {
        if ($data['action'] == 'Check') {
            $Data = $this->user->getWithdrawData($data['system']);

            $Data = explode(',', $Data, 2);
            if ($Data[0] == 0) {
                return '0,' . _('withdraw_unavailable');
            } else {
                if ($Data[0] == 1) {
                    if (isset($Data[1]) && $Data[1] > 0) {
                        return '1,' . $Data[1];
                    } else {
                        return '1';
                    }
                } else {
                    return '0,' . _('withdraw_check_error');
                }
            }
        }

        if (!isset($data['amount']) || $data['amount'] <= 0) {
            return '0;' . _('set_amount');
        }

        if (!isset($data['system']) || $data['system'] <= 0) {
            return '0;' . _('set_payment_system');
        }

        if ($data['action'] == 'Credit') {
            if ($data['system'] == 29) {
                if (!$this->user->userData->country || $this->user->userData->birth_day == 0
                    || $this->user->userData->birth_month == 0 || $this->user->userData->birth_year == 0
                ) {
                    return '0;' . _('country_and_birth_date_required');
                }
            }

            $apiAnswer = $this->user->credit($data);

            $init = explode(',', $apiAnswer, 2);

            if ($init[0] == 1) {
                $init = $init[1];
            } else {
                if (!isset($init[1])) {
                    return '0;Init error!' . $apiAnswer;
                } else {
                    if ($tmp = json_decode($init[1], 1)) {
                        if ($tmp[0] == 'ERR') {
                            return '0;' . $tmp[1];
                        } else {
                            return '0;' . $tmp[0];
                        }
                    } else {
                        return '0;' . $init[1];
                    }
                }
            }

            if ($init == 1) {
                return '1;1;' . _cfg('href') . '/payment-complete/';//._('payment_processed');
            }

            $init = json_decode($init);
            if ($init[0] == 'ERR') {
                return '0;' . $init[1];
            } else {
                if ($init[0] == 'MSG') {
                    if ($init[1]->Code == 1) {
                        return '0;' . _('payment_request_sent');
                    } else {
                        return '0;' . _('payment_request_error');
                    }
                }
            }

            $response = '1;';
            if (_cfg('ecommpayAsPopup') == 1) {
                //Giving answer[1] = 1, means that we have to open ecommpay as popup, with GET params
                $response .= '1;' . $init[1]->URL . '?';
                foreach ($init[1] as $k => $v) {
                    if ($k == 'URL') {
                        continue;
                    }
                    $v = str_replace('[:LANG:]', _cfg('site'), $v);
                    $v = urlencode($v);
                    $response .= $k . '=' . $v . '&';
                }
                $response = substr($response, 0, -1);
            } else {
                //Giving answer[1] = 0, means that we have to open ecommpay in the same window, but with POST params
                $response .= '0;<form method="' . $init[0] . '" action="' . $init[1]->URL . '">';
                foreach ($init[1] as $k => $v) {
                    if ($k == 'URL') {
                        continue;
                    }
                    $response .= '<input name="' . $k . '" value="' . str_replace('[:LANG:]',
                            _cfg('site'), $v) . '" type="hidden"><br/>';
                }
                $response .= '</form>';
            }

            return $response;
        } else {
            if ($data['action'] == 'Debet') {

                $withdrawalStatus = $this->user->checkUserWithdrawalStatus();

                if ($withdrawalStatus->fundistWidthdrawQueries >= _cfg('maxWithdrawalQueries')) {
                    return '0;' . _('max_limit_of_withdraw_reached');
                }

                if ($data['system'] == _cfg('ecommpayIdInFundist')) {
                    if ($this->allowEcommpayDebet() == 'false') {
                        return '0;' . _('sys_error_message');
                    }
                }

                $apiAnswer = $this->user->debet($data);

                if (isset($data['paymentTypeID']) && !$data['purse'] && substr($data['paymentTypeID'],
                        0, 1) != '1'
                ) {
                    return '0;' . _('input_purse');
                }

                $init = explode(',', $apiAnswer, 2);

                if ($init[0] == 1) {
                    $init = json_decode($init[1],1);

                    if ($init[0] == 1) {
                        return '1;' . _('payment_withdraw_sent');
                    } else {

                        if ($init[0] == 'GET' || $init[0] == 'POST') {
                            $response = '1;<form method="' . $init[0] . '" action="' . $init[1]['URL'] . '">';
                            foreach ($init[1] as $k => $v) {
                                if ($k == 'URL') {
                                    continue;
                                }
                                $response .= '<input name="' . $k . '" value="' . str_replace('[:LANG:]',
                                    _cfg('site'), $v) . '" type="hidden"><br/>';
                            }
                            $response .= '</form>';

                            return $response;
                        } else if ($init[0] == 'ERR') {
                            return '0;' . $init[1];
                        } else {
                            return '0;' . $init[0];
                        }
                    }
                } else {
                    return '0;' . $init[1];
                }
            }
        }

        return false;
    }

    public function restorePassword($data)
    {
        if (empty($data['newPassword']) || ($data['newPassword'] = trim($data['newPassword'])) == '') {
            return '2;<p>' . _('New password is empty') . '</p>';
        }

        if (empty($data['repeatPassword']) || ($data['repeatPassword'] = trim($data['repeatPassword'])) == '') {
            return '3;<p>' . _('Repeat password is empty') . '</p>';
        }

        if (empty($data['code']) || ($data['code'] = trim($data['code'])) == '') {
            return '4;<p>' . _('Code is empty') . '</p>';
        }

        $user = new User();
        $user->logUserData('restore', json_encode(Utils::obfuscatePassword($data)));
        $restoreData = $user->checkRestoreCode($data['code']);

        if (empty($restoreData)) {
            return '4;<p>' . _('Code is incorrect') . '</p>';
        }

        if (strlen($data['newPassword']) < 6) {
            return '5;<p>' . _('Password must be at least 6 characters long') . '</p>';
        }

        if ($data['newPassword'] != $data['repeatPassword']) {
            return '5;<p>' . _('pass_not_match') . '</p>';
        }

        return $this->user->restorePassword($restoreData['email'], $data['newPassword'], $data['code']);
    }

    public function checkEmail($data)
    {
        return $this->user->checkIfEmailExist($data);
    }

    public function cleanData()
    {
        $this->user->logout();

        return true;
    }

    public function checkNetEnt()
    {
        if (!$this->user->userData) {
            return '0;' . _('Session expired');
        }

        if (!$this->user->userData->country ||
            !$this->user->userData->birth_day ||
            !$this->user->userData->birth_month ||
            !$this->user->userData->birth_year ||
            !$this->user->userData->sex
        ) {
            return false;
        }

        return true;
    }

    public function updateProfileInfo($data)
    {
        return $this->user->profileAdditionalUpdate($data);
    }

    public function updateProfile($data)
    {
        //ddump($this->data->user);
        return $this->user->profileUpdate($data);
    }

    public function fetchLastOptions()
    {
        if (_cfg('env') != 'prod') {
            $context = stream_context_create(
                array(
                    "ssl" => array(
                        "verify_peer" => false,
                        "verify_peer_name" => false,
                    ),
                )
            );
        } else {
            $context = null;
        }

        $list = file_get_contents(_cfg('frameLink') . "/cache/LastOptions.json", false, $context);
        if (!$list) {
            $list = json_encode(Array());
        }

        return $list;
    }

    public function fetchCountryList($data)
    {
        //If no language given, setting "en" as default
        if (
            !isset($data['language'])
            || !$data['language']
            || (isset($data['language']) && !in_array($data['language'], Array('en', 'ru')))
        ) {
            $data['language'] = 'en';
        }

        //Checking if there is countries that must be excluded from the list
        $exclude = Config::getSiteConfig();
        if (empty($exclude['exclude_countries'])) $exclude = [];
        else $exclude = $exclude['exclude_countries'];

        //Setting how should we order it, Z to A or A to Z (last one is default)
        if (isset($data['orderHow']) && $data['orderHow'] == 'desc') {
            $data['orderHow'] = 'sortByNameDesc';
        } else {
            $data['orderHow'] = 'sortByNameAsc';
        }

        //Getting only countries with specific language
        $countries = Classifier::getCountryList();
        $gatherCountries = array();

        if (is_array($countries)) {
            foreach ($countries as $k => $v) {
                if ($v->Lang == $data['language'] && !in_array($v->Iso3, $exclude)
                    && !in_array($v->Iso3, $exclude)) {
                    $gatherCountries[] = $v;
                }
            }

            //Sorting by alphabet
            usort($gatherCountries, array($this, $data['orderHow']));

            $gatherCountries = (object)$gatherCountries;
        }

        return json_encode($gatherCountries);
    }

    /**
     * For Ecommpay debet required to fill user fields
     * @return string json_encode(bool)
     */
    public function allowEcommpayDebet()
    {
        $data = $this->user->getFundistUser();
        $data = explode(',', $data, 2);
        if ($data[0] === '1') {
            $data = json_decode($data[1], true);
            $req_fields = [
                'Name',
                'LastName',
                'MiddleName',
                'Phone',
                'DateOfBirth',
                'Address',
                'IDNumber',
                'IDIssueDate',
                'IDIssuer',
            ];
            $data = array_intersect_key($data, array_flip($req_fields));
            if (count(array_filter(array_values($data))) != count($data)) {
                return json_encode(false);
            }
        } else {
            return json_encode(false);
        }
        return json_encode(true);
    }

    /**
     * @param array $data
     * @return Fundist API response
     */
    public function updateFundistUser($data)
    {
        $answer = $this->user->updateFundistUser($data);
        return $answer;
    }

    protected function userLogOut()
    {
        return $this->user->logout();
    }

    protected function userLogin($data)
    {
        return $this->user->login($data);
    }

    protected function userReLogin($data)
    {
        $data['login'] = $this->user->userData->email;
        $data['pass'] = $this->user->userData->password;
        $data['relogin'] = 1;

        return $this->user->login($data);
    }

    protected function userRegister($data)
    {
        return $this->user->register($data);
    }

    protected function userSocialNewConnection($data)
    {
        if (!$_SESSION['social']) {
            return _('social_account_expired');
        }

        return $this->user->tryConnectSocial($data);
    }

    protected function userSocialRegistration($data)
    {
        if (!$_SESSION['social']) {
            header('Location: ' . _cfg('site') . '/' . _cfg('language'));
            exit();
        }

        return $this->user->registerFinishSocial($data);
    }

    private function sortByNameDesc($a, $b)
    {
        return strcmp($b->Name, $a->Name);
    }

    private function sortByNameAsc($a, $b)
    {
        return strcmp($a->Name, $b->Name);
    }
}
