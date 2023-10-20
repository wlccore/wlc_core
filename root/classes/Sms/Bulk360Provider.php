<?php
namespace eGamings\WLC\Sms;

use eGamings\WLC\Db;

class Bulk360Provider extends AbstractProvider
{
    public const SUCCES_CODE = 200;
    public const MISSING_FIELDS_CODE = 400;
    public const WRONG_CONFIG_DATA_CODE = 401;
    public const API_NOT_ENABLED_ERROR = 403;
    public const SUSPENDED_ACCOUNT_CODE = 412;
    public const PROVIDER = 'bulk360';
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
    public function SendOne($phoneNumber, $sender, $content, $phoneCode, $concatMsg = 0, $unicodeMsg = 1, $sendTime = null, $validity = null)
    {
        $data = [
            'to' => $phoneCode . $phoneNumber,
            'text' => $content,
            'user' => $this->username,
            'pass' => $this->password,
            'from' => rawurlencode($sender)
        ];

        $result = [
            'status' => false
        ];

        $response = $this->sendRequest($data);

        if ($response['code'] == self::SUCCES_CODE) {
            $result['status'] = true;
            $msgid = $response['result'];
            $tokenData = [
                'phoneNumber' => $phoneNumber,
                'phoneCode' => $phoneCode,
                'msgid' => $msgid,
                'code' => $this->getValidateCode(),
            ];
            $result['token'] = $this->encodeToken($tokenData);

            Db::query('INSERT INTO `sms_delivery_status` SET ' .
                '`provider` = "' . self::PROVIDER . '" ,' .
                '`msgid` = "' . Db::escape($msgid) . '", ' .
                '`status` = "' . $response['code'] . '", ' .
                '`updated` = NOW()'
            );
        } else {
            error_log(__CLASS__ . ' send sms error. Code: ' . $response['code'] . ' message: ' . $response['result']);
            $result['result'] = $response['result'];
        }

        return $result;
    }

    /**
     * Send request API
     * @param array $data
     * @return array
     */
    protected function SendRequest($data = [], $route = '')
    {
        $url = $this->apiUrl . '?' . http_build_query($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $sentResult = curl_exec($ch);
        curl_close($ch);

        return $this->parseResponse($sentResult);
        
    }

    /**
     * Set response
     * @param $response
     * @return mixed
     */
    protected function parseResponse($response, $errno = 0, $error = null, $http_code = 200, $batch = false)
    {
        $response = json_decode($response, true);
        if (!is_array($response)) {
            error_log(__CLASS__ . ' send error: ' . $response);
            return ['result' => false, 'code' => ''];
        }

        $result = [
            'code' => $response['code'],
        ];
        $result['result'] = $response['code'] == self::SUCCES_CODE ? $response['ref'] : $response['desc'];

        return $result;
    }

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
    public function SendMultiple($sender, Array $content, $countryCode, $concatMsg = 0, $unicodeMsg = 1, $sendTime = null, $validity = null)
    {
        return false; // not supported
    }

    /**
     * get sms status
     * @param string $token
     * @return string
     */
    public function getSmsStatus($token)
    {
        $result = false;
        $tokenData = $this->decodeToken($token);

        if (empty($tokenData) || empty($tokenData['msgid'])) {
            return false;
        }

        $msgid = explode(',', $tokenData['msgid']);
        $row = Db::fetchRow('SELECT `status` FROM `sms_delivery_status` WHERE `msgid` = "' . Db::escape($msgid[0]) . '" AND `provider` = "' . self::PROVIDER . '"');

        if ($row && isset($row->status)) {
            $result = $this->getStatusByCode($row->status);
        }

        return $result;
    }

    /**
     * get status by code
     * @param int @status
     * @return string
     */
    private function getStatusByCode(int $status) : string
    {
        $statusList = [
            self::SUCCES_CODE => self::$STATE_DELIVERED,
            self::WRONG_CONFIG_DATA_CODE => self::$STATE_Error,
            self::MISSING_FIELDS_CODE => self::$STATE_Error,
            self::API_NOT_ENABLED_ERROR => self::$STATE_FAILED,
            self::SUSPENDED_ACCOUNT_CODE => self::$STATE_FAILED,
        ];

        return $statusList[$status] ?? self::$STATE_UNDELIVERED;
    }

}
