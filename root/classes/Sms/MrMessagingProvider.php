<?php
namespace eGamings\WLC\Sms;

use eGamings\WLC\Db;

class MrMessagingProvider extends  AbstractProvider
{

    private function setParams(array $data) :array
    {
        $requaredParans = [
            'username' => $this->username,
            'password' => $this->password,
            'srcton'   => 1,  // Alphanumeric
        ];

        return array_merge($requaredParans, $data);
    }

    protected function parseResponse($response, $errno = 0, $error = null, $http_code = 200, $batch = false)
    {
        $rv = [
            'status' => true,
            'result' => $response,
        ];

        if ($errno != 0 || empty($response)) {
            $rv['status'] = false;
            $rv['result'] = _('Server returned invalid response');
        } elseif ($http_code != 200) {
            $rv['status'] = false;
            $rv['result'] = $response;
        }

        if (!$rv['status']) {
            error_log('SMS Send Failed: ' . $rv['result'] . '. Response: ' . json_encode($response));
        }

        return $rv;
    }

    public function SendOne($phoneNumber, $sender, $content, $phoneCode, $concatMsg = 0, $unicodeMsg = 1, $sendTime = null, $validity = null)
    {
        $result = [
            'status' => false
        ];

        $data = [
            'sender' => $sender,
            'receiver' => $phoneCode . $phoneNumber,
            'message' => $content,
        ];

        if ($unicodeMsg == 1) {
            $data['coding'] = 2;
            $data['charset'] = 'UCS2';
            if (strlen($content) > 70 || $concatMsg == 1) {
                $data['type'] = 'longsms';
            }
        } else {
            if (strlen($content) > 160 || $concatMsg == 1) {
                $data['type'] = 'longsms';
            }
        }

        $response = $this->SendRequest($this->setParams($data));

        if ($response['status']) {
            $result['status'] = true;
            $msgid = $response['result'];
            $tokenData = [
                'phoneNumber' => $phoneNumber,
                'phoneCode' => $phoneCode,
                'msgid' => $msgid,
                'code' => $this->getValidateCode(),
            ];
            $result['token'] = self::encodeToken($tokenData);

            Db::query('INSERT INTO `sms_delivery_status` SET ' .
                '`provider` = "mrmessaging" ,' .
                '`msgid` = "' . Db::escape($msgid) . '", ' .
                '`status` = "' . self::$STATE_QUEUE . '", ' .
                '`updated` = NOW()'
            );
        } else {
            $result['result'] = $response['result'];
        }

        return $result;
    }

    public function SendMultiple($sender, Array $content, $countryCode, $concatMsg = 0, $unicodeMsg = 1, $sendTime = null, $validity = null)
    {
        return false; // not supported
    }

    public function getSmsStatus($token)
    {
        $result = false;
        $tokenData = self::decodeToken($token);

        if (empty($tokenData) || empty($tokenData['msgid'])) {
            return false;
        }

        $msgid = explode(',', $tokenData['msgid']);
        $row = Db::fetchRow('SELECT `status` FROM `sms_delivery_status` WHERE `msgid` = "' . Db::escape($msgid[0]) . '" AND `provider` = "mrmessaging"');

        if ($row && !empty($row->status)) {
            switch ($row->status){
                case 'DELIVRD':
                    $result = self::$STATE_DELIVERED;
                    break;
                case 'ACCEPTD':
                    $result = self::$STATE_BUFFERED;
                    break;
                case 'EXPIRED':
                case 'DELETED':
                case 'UNDELIV':
                case 'REJECTD':
                    $result = self::$STATE_FAILED;
                    break;
                case 'UNKNOWN':
                    $result = self::$STATE_UNKNOWN;
                    break;
                case 'Queue':
                    $result = self::$STATE_QUEUE;
                    break;
                default:
                    $result = self::$STATE_UNKNOWN;
                    break;
            }
        }
        return $result;
    }

    protected function SendRequest($data = [], $route = '')
    {
        $ch = curl_init();
        $url = $this->apiUrl . '?' . http_build_query($data);
        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
        ];

        curl_setopt_array($ch, $curlOptions);
        $response = curl_exec($ch);
        $responseErrNo = curl_errno($ch);
        $responseError = curl_error($ch);
        $http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close($ch);

        return $this->parseResponse($response, $responseErrNo, $responseError, $http_code);
    }

    public static function hanldeCallback($data) {

        if (!empty($data['id']) && !empty($data['status'])) {
            $row = Db::fetchRow('SELECT `id` FROM `sms_delivery_status` WHERE `msgid` = "' . Db::escape($data['id']) . '" AND `provider` = "mrmessaging"');

            if ($row && isset($row->id) && $row->id > 0) {
                Db::query('UPDATE `sms_delivery_status` SET ' .
                    '`status` = "' . Db::escape($data['status']) . '", ' .
                    '`updated` = NOW()' .
                    'WHERE `id` = ' . $row->id
                );
            } else {
                Db::query('INSERT INTO `sms_delivery_status` SET ' .
                    '`provider` = "mrmessaging" ,' .
                    '`msgid` = "' . Db::escape($data['id']) . '", ' .
                    '`status` = "' . Db::escape($data['status']) . '", ' .
                    '`updated` = NOW()'
                );
            }
        }
    }

}
