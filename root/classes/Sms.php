<?php
namespace eGamings\WLC;

use Exception;
use RuntimeException;

class Sms
{
    private static $_instance = false;

    private function __construct() {}

    public static function getInstance()
    {
        if (!self::$_instance) {
            $smsConfig = _cfg('smsConfig');
            if (!empty($smsConfig['provider'])) {
                $provider = self::getSmsProvider($smsConfig['provider']);
                self::$_instance = class_exists($provider) ? new $provider($smsConfig) : false;
            }
        }
        return self::$_instance;
    }

    private static function getSmsProvider($nameProvider)
    {
        return 'eGamings\\WLC\\Sms\\' . $nameProvider . 'Provider';
    }

    public static function unsetInstance() : void
    {
        if (self::$_instance) {
            self::$_instance = false;
        }
    }

    /**
     * @param string $phone
     * @return string
     */
    public static function sendSmsPasswordRestoreCode(string $phone) : string
    {
        $user = Db::fetchRow('SELECT phone1, phone2, first_name, last_name, id, api_password, email FROM users WHERE phone2 = "' . Db::escape($phone) . '"');
        if ($user === false) {
            if (_cfg('hidePhoneExistence')) {
                return '1;' . _('Sms sent, recovery code will be available for 30 minutes');
            }

            return '0;' . _('Error, account with this phone does not exist.');
        }
        $redis = System::redis();

        $restoreData = [
            'phone' => (string)$user->phone1 . (string)$user->phone2,
            'code' => rand(10000, 99999),
            'time' => time(),
            'email' => $user->email
        ];
        $redisKey = 'user_pass_restore_' . $restoreData['code'];

        if (!$redis->set($redisKey, json_encode($restoreData), 60 * 30)) {
            Logger::log(__CLASS__ . 'Redis error. Failed set key - ' . $redisKey);
            return '0;' . _('Error sending recovery code');
        }

        // Comment below
        $smsProvider = self::getInstance();
        if (!$smsProvider) {
            Logger::log("Failed send recovery code. Sms provider not found");
            return '0;' . _('Sms provider not found');
        }
        $message = _("Password reset code - ") . $restoreData['code'];

        try {
            $result = $smsProvider->SendOne($user->phone2, $smsProvider->getDefaultSender(), $message, str_replace('+', '', $user->phone1));
        } catch (Exception $ex) {
            $result['status'] = 0;
            $result['message'] = $ex->getMessage();
        }

        if (!$result['status']) {
            $errorMsg = "Failed send recovery code. Msg: " . implode(",", $result);
            Logger::log($errorMsg);
            return '0;' . implode(",", $result);
        }

        return '1;' . _('Sms sent, recovery code will be available for 30 minutes');
    }

    /**
     * @param string $phoneNumber
     * @param string $phoneCode
     * @param string $message
     *
     * @return void
     */
    public static function send(string $phoneNumber, string $phoneCode, string $message): void
    {
        $smsProvider = self::getInstance();
        if (!$smsProvider) {
            Logger::log('Failed send SMS. SMS provider not found');

            throw new RuntimeException('Sms provider not found');
        }

        try {
            $result = $smsProvider->SendOne(
                $phoneNumber,
                $smsProvider->getDefaultSender(),
                $message,
                self::getPhoneCode($phoneCode),
            );
        } catch (Exception $ex) {
            $result['status'] = 0;
            $result['message'] = $ex->getMessage();
        }

        if (!$result['status']) {
            Logger::log('Failed send SMS. Msg: ' . implode(',', $result));

            throw new RuntimeException('Failed send SMS message.');
        }
    }

    /**
     * @param string $phoneCode
     *
     * @return string
     */
    private static function getPhoneCode(string $phoneCode): string
    {
        return str_replace('+', '', $phoneCode);
    }
}
