<?php
namespace eGamings\WLC\Loyalty;

use eGamings\WLC\Loyalty;
use eGamings\WLC\Front;
use eGamings\WLC\System;

class LoyaltyStoreResource extends LoyaltyAbstractResource {
    static function StoreItems() {
        $path = 'Store/Get';
        $params = array();

        $fundist_uid = Front::User('fundist_uid');
        if ($fundist_uid) {
            $params['IDUser'] = $fundist_uid;
            $params['UserTags'] = !empty($_SESSION['user']['UserTags']) ? $_SESSION['user']['UserTags'] : '';
        }
        
        $result = Loyalty::Request($path, $params);
        if (!is_array($result)) {
            $result = [];
        }
        
        $result['Items'] = !empty($result['Items']) ? $result['Items'] : [];
        foreach($result['Items'] as $k => &$v) {
            $v['Image'] = $v['Img'];
            unset($v['Img']);
        }
        $result['Categories'] = !empty($result['Categories']) ? $result['Categories'] : [];
        
        $result['Items'] = self::localizeRows($result['Items']);
        $result['Categories'] = self::localizeRows($result['Categories']);
         
        return $result;
    }
    
    static function StoreBuy($itemId, $orderQuantity) {
        $userId = Front::User('id');
        $fundistUserId = Front::User('fundist_uid');

        if (!$fundistUserId || !$userId) {
            return false;
        }

        $User = Front::get('_user');
        $url = '/WLCInfo/Store/Buy?';
        
        $transactionId = $User->getApiTID($url);
        
        $hash = md5('WLCInfo/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $userId . '/' . _cfg('fundistApiPass'));
        $params = [
            'IDItem' => $itemId,
            'Quantity' => $orderQuantity,
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
    
    static function StoreOrders() {
        $path = 'Store/Orders';
        $params = [];
        
        if (!Front::User('fundist_uid')) {
            return [];
        }
        
        $params['IDUser'] = Front::User('fundist_uid');

        if (isset($_GET['status']) && in_array((int)$_GET['status'], [1, 99, 100])) {
            $params['Status'] = (int)$_GET['status'];
        }

        $orders = Loyalty::Request($path, $params);

        return self::localizeRows($orders);
    }

    public static function StoreGetCategories() {
        $path = 'Store/Categories/List';
        $params = [];
        if (isset($_GET['showAll'])) {
            $params['showAll'] = $_GET['showAll'];
        }

        $result = Loyalty::Request($path, $params);
        return $result;
    }
}
