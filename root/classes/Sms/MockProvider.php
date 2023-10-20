<?php
namespace eGamings\WLC\Sms;

use eGamings\WLC\Db;

class MockProvider extends AbstractProvider
{
    private static $ThrowException = false;

    /**
     * Set response
     * @param $response
     * @return mixed
     */
    protected function parseResponse($response, $errno = 0, $error = null, $http_code = 200, $batch = false) : void {}

    /**
     * Sending same message
     * @param $phoneNumber
     * @param $sender
     * @param $content
     * @param int $countryCode
     * @param int $concatMsg
     * @param int $unicodeMsg
     * @param null $sendTime
     * @param null $validity
     * @return array
     */
    public function SendOne(
        $phoneNumber, 
        $sender, 
        $content, 
        $phoneCode, 
        $concatMsg = 0, 
        $unicodeMsg = 1, 
        $sendTime = null, 
        $validity = null
    ) : array {
        if (self::$ThrowException) {
            throw new \Swift_SwiftException('UnitTest Swift Exception');
        }

        $message = sprintf('Test message sent to: %s. Content: %s, Sender: %s', $phoneNumber, $content, $sender);
        error_log($message);

        return [
            'status' => !empty($phoneNumber)
        ];
    }

    // @codeCoverageIgnoreStart
    /**
     * Sending different messages to different recipients
     * @param $sender
     * @param array $content
     * @param int $countryCode
     * @param int $concatMsg
     * @param int $unicodeMsg
     * @param null $sendTime
     * @param null $validity
     * @return array
     */
    public function SendMultiple(
        $sender, 
        $content, 
        $countryCode, 
        $concatMsg = 0, 
        $unicodeMsg = 1, 
        $sendTime = null, 
        $validity = null
    ) : void {}
    // @codeCoverageIgnoreEnd

    /**
     * returns sms status
     * @param $token
     */
    public function getSmsStatus($token) : void {}

    /**
     * variable setter
     * 
     * @param string $name
     * @param array $arguments
     * @return void
     */
    public static function __callStatic(string $name, array $arguments) : void
    {
        $type = substr($name, 0, 3);
        $key = substr($name, 3);

        if ($type === 'set') {
            self::$$key = $arguments[0];
        }
    }

}
