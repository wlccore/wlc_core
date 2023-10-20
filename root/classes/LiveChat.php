<?php
namespace eGamings\WLC;

class LiveChat
{
    private static $_instance = false;

    private function __construct() {}

    public static function getInstance()
    {
        if (!self::$_instance) {
            $liveChatConfig = _cfg('liveChatConfig');
            if (!empty($liveChatConfig['LiveChatType'])) {
                $provider = self::getLiveChatProvider($liveChatConfig['LiveChatType']);
                self::$_instance = class_exists($provider) ? new $provider($liveChatConfig) : false;
            }
        }
        return self::$_instance;
    }

    private static function getLiveChatProvider($nameProvider)
    {
        return 'eGamings\\WLC\\LiveChats\\' . $nameProvider . 'Provider';
    }
}