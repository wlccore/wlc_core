<?php
namespace eGamings\WLC;

use eGamings\WLC\Front;
use eGamings\WLC\User;

class Trading {
    static function login($mode = 'url', $refresh = false) {
        $rv = [];

        if (!$refresh && $mode == 'url' && !empty($_SESSION['user']['tradingURL'])) {
            $rv = $_SESSION['user']['tradingURL'];
        } else if (Front::User('id')) {
            $u = new User();
            $result = $u->generateTradingUrl(Front::User(), $mode);

            if ($mode == 'url' && $result) {
                $_SESSION['user']['tradingURL'] = $rv = $result;
            } else if ($mode == 'json' && $result) {
                $rv = json_decode($result, true);
            } else {
                $rv = $result;
            }
        } else {
            $rv = _cfg('frameLink').'/'._cfg('language').'/?system='._cfg('spotOptionSystem').'&compatibility_mode=1';
            
            if ( false && Front::User('timezone') ) {
                $rv .= '&timezone='.Front::User('timezone');
            }
            else {
                $rv .= '&timezone='._cfg('defaultTimeZone');
            }
        }

        return $rv;
    }
    
    static function logout() {
        $rv = [];
        if (!empty($_SESSION['user']['tradingURL'])) {
            unset($_SESSION['user']['tradingURL']);
        }
        return $rv;
    }
}
