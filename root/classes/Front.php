<?php
namespace eGamings\WLC;

class Front
{
    static private $f = false;

    private $_user;
    private $_games;
    private $_social;
    private $_storage;

    function __construct()
    {
        $this->_user = new User();
        $this->_games = new Games();
        $this->_social = new Social();
        $this->_storage = new Storage();
    }

    public static function Games($request = false, $params = null)
    {
        $f = self::getInstance();

        if (method_exists($f->_games, $request)) {
            return call_user_func_array(array($f->_games, $request), $params);
        } else {
            if (property_exists($f->_games, $request)) {
                return $f->_games->{$request};
            } else {
                return $f->_games;
            }
        }
    }

    public static function getInstance()
    {
        if (!self::$f) {
            self::$f = new Front();
        }

        return self::$f;
    }

    public static function Social($provider = '')
    {
        $f = self::getInstance();

        if ($provider == '') {
            return $f->_social->Verify();
        }

        return $f->_social->getToken($provider);
    }

    public static function Store()
    {
        $a = new Ajax();

        return $a->Store();
    }

    public static function Storage($request = false, $params = null)
    {
        $f = self::getInstance();

        if (method_exists($f->_storage, $request)) {
            return call_user_func_array(array($f->_storage, $request), $params);
        } else {
            if (property_exists($f->_storage, $request)) {
                return $f->_storage->{$request};
            } else {
                return $f->_storage;
            }
        }
    }

    public static function Bonus()
    {
        $a = new Ajax();

        return $a->Bonus();
    }

    public static function PaySystems() 
    {
        $S = new System();

        $params = Array(
            'UserIP' => System::getUserIP(),
        );

        if (trim(self::User('id')) == '') {
            // not authorized user

            $className = "WLCClassifier";
            $url = '/' . $className . '/Systems/?';
            $transactionId = $S->getApiTID($url);
            $hash = md5($className . '/Systems/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
        } else {
            // authorized user

            $className = "WLCAccount";
            $urlParamKey = 'Login';
            $urlParamValue = self::User('id');
            // advanced params
            $params['Password'] = self::User('api_password');
            $url = '/' . $className . '/Systems/?&' . $urlParamKey . '=' . $urlParamValue;
            $transactionId = $S->getApiTID($url);
            $hash = md5($className . '/Systems/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $urlParamValue . '/' . _cfg('fundistApiPass'));

            if (!empty($_GET['version'])) {
                $params['Version'] = (float)$_GET['version'];
            }

            if (!empty($_GET['currency'])) {
                $params['Currency'] = $_GET['currency'];
            }
        }

        $params['TID'] = $transactionId;
        $params['Hash'] = $hash;

        $url .= '&' . http_build_query($params);

        $response = $S->runFundistAPI($url);
        $response = explode(',', $response, 2);

        if ($response[0] == '1') {
            $list = json_decode($response[1], 1);
            if ($list) {
                return $list;
            }
        }

        return array();
    }

    public static function User($request = false, $params = array())
    {
        $f = self::getInstance();

        if (method_exists($f->_user, $request)) {
            return call_user_func_array(array($f->_user, $request), $params);
        } else {
            if ($f->_user->userData && property_exists($f->_user->userData, $request)) {
                return $f->_user->userData->$request;
            } else {
                return $f->_user->userData;
            }
        }
    }

    public static function Orders()
    {
        $a = new Ajax();

        return $a->Orders();
    }


    public static function get($obj, $request = false)
    {
        $f = self::getInstance();

        if (isset($f->{$obj}) && is_object($f->{$obj})) {
            if (method_exists($f->{$obj}, $request)) {
                return $f->{$obj}->{$request}();
            } else {
                if (property_exists($f->{$obj}, $request)) {
                    return $f->{$obj}->{$request};
                } else {
                    return $f->{$obj};
                }
            }
        }

        return false;
    }

    public static function sendMail($email, $subject, $msg, $files = array())
    {
        return Email::send($email, $subject, $msg, $files);
    }
}
