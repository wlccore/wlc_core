<?php
namespace eGamings\WLC\Loyalty;

use Egamings\UserDataMasking\UserDataMasking;
use eGamings\WLC\Config;
use eGamings\WLC\Db;
use eGamings\WLC\Loyalty;
use eGamings\WLC\Front;
use eGamings\WLC\System;
use eGamings\WLC\User;
use eGamings\WLC\Utils;

class LoyaltyTournamentsResource extends LoyaltyAbstractResource {
    static function TournamentStatus($data) {
        $rv = array();
        $fundist_uid = Front::User('fundist_uid');
    
        if (!$fundist_uid) {
            $rv['error'] = [id => 'not_logged_in', text => _('must_login')];
            return json_encode($rv);
        }
    
        // Sending Loyalty Request
        $path = 'Tournaments/Get';
        $params = array();
        $params['IDUser'] = $fundist_uid;
    
        return json_encode($data);
    }
    
    /**
     * TournamentsList - fetch list of available tournaments
     */
    static function TournamentsList($tournamentCurrency = '') {
        $path = 'Tournaments/Get';
        $params = array();

        $fundist_uid = Front::User('fundist_uid');
        if ($fundist_uid) {
            $params['IDUser'] = $fundist_uid;
            $params['UserTags'] = !empty($_SESSION['user']['UserTags']) ? $_SESSION['user']['UserTags'] : '';
        }

        if (!empty($tournamentCurrency)) {
            $params['Currency'] = $tournamentCurrency;
        }

        $config = Config::getSiteConfig();
        if (is_array($config['currencies'])) {
            $currencies = array_values(array_map(function($v) { return $v['Name']; }, $config['currencies']));
            $params['FeeCurrencies'] = json_encode($currencies);
        }
        

        return self::localizeRows(Loyalty::Request($path, $params));
    }
    
    /**
     * TournamentsRegistered - unregister from specific tournament
     */
    static function TournamentsRegistered() {
        $path = 'Tournaments/Get';
        $params = array();
        $rv = [];
        
        $fundist_uid = Front::User('fundist_uid');
        if (!$fundist_uid) {
            return $rv;
        }

        $params['IDUser'] = $fundist_uid;
    
        try {
            $result = Loyalty::Request($path, $params);
            if (is_array($result)) foreach($result as $k=>$tournament) {
                if ($tournament['Selected']) {
                    $rv[] = $tournament;
                }
            }
            $rv = self::localizeRows($rv);
        } catch (\Exception $ex) { }

        return $rv;
    }
    
    /**
     * TournamentGet - fetch selected tournament information
     */
    static function TournamentGet($tournamentId, $tournamentCurrency = '') {
    
        $rv = false;
        try {
            $path = 'Tournaments/Get';
            $params = array(
                'ID' => $tournamentId
            );
    
            $fundist_uid = Front::User('fundist_uid');
            if ($fundist_uid) {
                $params['IDUser'] = $fundist_uid;
            }

            if (!empty($tournamentCurrency)) {
                $params['Currency'] = $tournamentCurrency;
            }

            $config = Config::getSiteConfig();
            if (is_array($config['currencies'])) {
                $currencies = array_values(array_map(function($v) { return $v['Name']; }, $config['currencies']));
                $params['FeeCurrencies'] = json_encode($currencies);
            }

            $result = Loyalty::Request($path, $params);
            if (sizeof($result) > 0 && isset($result[0])) {
                $rv = $result[0];
                $rv = self::localizeRow($rv);
            }
        } catch(\Exception $ex) {
            echo $ex->getMessage();
        }
    
        return $rv;
    }
    
    static function TournamentStatistics($tournamentId) {
        $rv = array();
        try {
            $path = 'Tournaments/Stats';
            $fundist_uid = Front::User('fundist_uid');
            $params = array(
                'IDTournament' => $tournamentId
            );
    
            if ($fundist_uid) {
                $params['IDUser'] = $fundist_uid;
            }
    
            $rv['top'] = self::TournamentWidgetsTop($tournamentId);
            $rv['tournament'] = self::TournamentGet($tournamentId);
            if ($fundist_uid) {
                $rv['user'] = self::TournamentWidgetsUser($tournamentId);
            }
        } catch(\Exception $ex) {
        }
    
        return $rv;
    }
    
    /**
     * TournamentWidgetsTop - get Top Widget for tournament
     *
     * @return array:
     */
    static function TournamentWidgetsTop($tournamentId, $vars = []) {
        $rv = array();
        $path = 'Tournaments/Widgets/Top';
        $params = array(
            'IDTournament' => $tournamentId
        );
        
        $allowVars = ['Start' => 0, 'Limit' => 50];
        
        foreach($allowVars as $k => $v) {
            if (isset($vars[$k])) {
                $params[$k] = $vars[$k];
            } else {
                $params[$k] = $v;
            }
        }
    
        $fundist_uid = Front::User('fundist_uid');
        if ($fundist_uid) {
            $params['IDUser'] = $fundist_uid;
        }
            
        try {
            $rv = Loyalty::Request($path, $params);

            if (is_array($rv) && isset($rv['results']) && is_array($rv['results'])) {
                $users = [];
                foreach($rv['results'] as $resultRowId => $resultRow) {
                    $loginArr = explode('_', $resultRow['Login']);
                    if (!isset($loginArr[1]) || (int)$loginArr[1] == 0) continue;
                    $users[(int)$loginArr[1]] = $resultRowId;
                }

                if (sizeof($users) > 0) {
                    $siteConfig = Config::getSiteConfig();

                    $sql = "SELECT id, first_name, last_name, login, email FROM users WHERE id IN ('".implode("','", array_keys($users))."')";

                    $result = Db::query($sql);
                    if ($result) while($row = $result->fetch_assoc()) {
                        if (!isset($users[$row['id']])) continue;

                        $resultRowId = $users[$row['id']];
                        $rv['results'][$resultRowId]['Email'] = Utils::hideStringWithWildcards($row['email']);

                        $rv['results'][$resultRowId]['FirstName'] = $row['first_name'];
                        $rv['results'][$resultRowId]['LastName'] = mb_substr($row['last_name'], 0, 1);
                        $rv['results'][$resultRowId]['UserLogin'] = $row['login'];

                        $rv['results'][$resultRowId]['ScreenName'] = (new UserDataMasking(
                            $siteConfig['MaskTypeForNameAndLastName'] ?? '',
                            $row['first_name'] ?? '',
                            $row['last_name'] ?? '',
                            $row['email'] ?? '')
                        )->getScreenName();
                    }
                }
            }            
        } catch (\Exception $ex) {
            $rv['error'] = $ex->getMessage();
        }
        return $rv;
    }
    
    /**
     * TournamentWidgetsUser - return user place widget info for tournament
     *
     * @return array:
     */
    static function TournamentWidgetsUser($tournamentId) {
        $rv = [];
        $path = 'Tournaments/Widgets/User';
        $params = ['IDTournament' => $tournamentId];
        
        $fundist_uid = Front::User('fundist_uid');
        if ($fundist_uid) {
            $params['IDUser'] = $fundist_uid;
        }
        
        try {
            $rv = Loyalty::Request($path, $params);
        } catch (\Exception $ex) {
            $rv['error'] = $ex->getMessage();
        }
        return $rv;
    }
    
    
    /**
     * TournamentsHistory - get user tournaments history list
     */
    static function TournamentsHistory() {
        $path = 'Tournaments/History';
        $params = array();
    
        $fundist_uid = Front::User('fundist_uid');
        if ($fundist_uid) {
            $params['IDUser'] = $fundist_uid;
        }
    
        $rv = [];
        
        
        $StatusesText = [
            '0' => _('Selected'),
            '1' => _('Qualified'),
            '-50' => _('Deleted'),
            '-95' => _('Deactivated'),
            '-99' => _('Canceled'),
            '99' => _('Ending'),
            '100' => _('Ended')
        ];
        
        try {
            $result = Loyalty::Request($path, $params);
            if (is_array($result)) {
                foreach($result as $k => &$row) {
                    $row['StatusText'] = isset($StatusesText[$row['Status']]) ? $StatusesText[$row['Status']] : $row['Status'];
                }
            }
            $result = self::localizeRows($result);
            $rv['result'] = $result;
        } catch(\Exception $ex) {
            $rv['error'] = ['id' => 'tournaments_history_error', 'text' => $ex->getMessage()];
        }
    
        return $rv;
    }
    
    
    /**
     * TournamentsSelect - select/unselect specific tournament
     * 
     * @param int $tournamentId - Tournament Identifier
     * @param boolean $status - 
     */
    static function TournamentsSelect($tournamentId, $status, $promocode = '', $wallet = null) {
        $fundist_uid = Front::User('fundist_uid');
        if (!$fundist_uid) return ['error' => _('must_login')];

        $url = '/WLCInfo/Tournament/Select?';

        $system = new System();
        $transactionId = $system->getApiTID($url);

        $hash = md5('WLCInfo/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . Front::User('id') . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'IDTournament' => $tournamentId,
            'Status' => $status,
            'Login' => Front::User('id'),
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
        );

        if (!empty($promocode)) {
            $params['PromoCode'] = strtolower($promocode);
        }

        if (null !== $wallet) {
            $params['Wallet'] = $wallet;
        }


        $url .= '&' . http_build_query($params);

        $response = $system->runFundistAPI($url);
        $data = explode(',', $response, 2);

        if ($data[0] != 1) {
            return ['error' => !empty($data[1]) ? dgettext('loyalty', $data[1]) : _('Loyalty API Failure')];
        }

        $result = json_decode($data[1],1);

        if (isset($result['error'])) {
            return ['error' => dgettext('loyalty', $result['error'])];
        }

        return ['result' => $result];
    }
}
