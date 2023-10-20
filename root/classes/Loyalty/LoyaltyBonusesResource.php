<?php
namespace eGamings\WLC\Loyalty;

use eGamings\WLC\Loyalty;
use eGamings\WLC\Ajax;
use eGamings\WLC\Front;
use eGamings\WLC\System;

class LoyaltyBonusesResource extends LoyaltyAbstractResource {
    
    static function BonusesList() {
        $a = new Ajax();
        
        $rv = [];
        try {
            $rv = $a->Bonus();
        } catch(\Exception $ex) {
            $rv['error'] = $ex->getMessage();
        }
        return $rv;
    }
    
    static function BonusGet($bonus) {
        $rv = false;
        try {
            $path = 'Bonuses/Get';

            if(is_array($bonus)){
                $params = !empty($bonus['id']) ? ['IDBonus' => $bonus['id']] : ['PromoCode' => $bonus['code']];
            } else {
                return $rv;
            }

            $fundist_uid = Front::User('fundist_uid');
            if ($fundist_uid) {
                $params['IDUser'] = $fundist_uid;
                $params['UserTags'] = !empty($_SESSION['user']['UserTags']) ? $_SESSION['user']['UserTags'] : '';
            }
            $params['Country'] = System::getGeoData();

            $result = Loyalty::Request($path, $params);
            if (sizeof($result) > 0 && isset($result[0])) {
                $rv = $result[0];
                $rv = self::localizeRow($rv);
            }
                
        } catch(\Exception $ex) {
            throw $ex;
        }
        
        return $rv;
    }

    static function BonusData($id) {
        $rv = false;
        try {
            $path = 'Bonuses/Data';

            if(!empty($id)){
                $params =  ['ID' => $id];
            } else {
                return $rv;
            }

            $fundist_uid = Front::User('fundist_uid');
            if ($fundist_uid) {
                $params['IDUser'] = $fundist_uid;
                $params['UserTags'] = !empty($_SESSION['user']['UserTags']) ? $_SESSION['user']['UserTags'] : '';
            }
            $params['Country'] = System::getGeoData();

            $result = Loyalty::Request($path, $params);

            if (!empty($result)) {
                $rv = self::localizeRow($result);
            }

        } catch(\Exception $ex) {
            throw $ex;
        }

        return $rv;
    }
    
    static function BonusesHistory($vars = array()) {
        if (!Front::User('fundist_uid')) {
            return ['error' => ['id' => 'not_logged_in', 'text' => _('must_login')]];
        }
        
        $path = 'Bonuses/History';
        $params = ['IDUser' => Front::User('fundist_uid')] ;
        
        $bonus = [];
        
        try {
            $bonus = Loyalty::Request($path, $params);
        } catch (\Exception $ex) {
            $bonus = ['error' => $ex->getMessage()];
        }
        
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
        
        if (!isset($bonus['error'])) foreach ($bonus as $k => $v) {
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
        
        return ['result' => $bonus];
    }

    static function BonusesCancelInfo($LBID) {
        $userId = Front::User('id');

        if (!$userId) {
            return false;
        }

        $User = Front::get('_user');
        $url = '/WLCInfo/Bonus/CancelInfo?';

        $transactionId = $User->getApiTID($url);

        $hash = md5('WLCInfo/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $userId . '/' . _cfg('fundistApiPass'));
        $params = [
            'LBID' => $LBID,
            'Login' => $userId,
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
            'Country' => System::getGeoData(),
        ];

        $url .= '&' . http_build_query($params);
        $response = $User->runFundistAPI($url);
        $result = [];
        $data = explode(',', $response, 2);

        if ($data[0] == 1) {
            $resultData = json_decode($data[1], true);

            if (!is_array($resultData)) {
                $result['error'] = _('purchase_error');
            } else {
                if (!empty($resultData['error'])) {
                    $result['error'] = dgettext('loyalty', $resultData['error']);
                } else {
                    $result = $resultData;
                }
            }
        } else {
            $result['error'] = _('purchase_error');
        }

        return $result;
    }
}
