<?php
namespace eGamings\WLC\Sms;

use eGamings\WLC\Db;
use phpDocumentor\Reflection\Types\String_;

class LinkMobilityProvider extends  AbstractProvider
{

    private function prepareParams(array $data) :array
    {
        $requaredParans = [
            'platformId'          => $this->platformId,
            'platformPartnerId'   => $this->platformPartnerId,
            'deliveryReportGates' => $this->deliveryReportGates,
            'ignoreResponse'      => false,
            'useDeliveryReport'   => true,
            'sourceTON'           => 'ALPHANUMERIC',
            'destinationTON'      => 'MSISDN',
            'dcs'                 => 'TEXT',
        ];

        return array_merge($requaredParans, $data);
    }

    private function prepareParamsBatch(string $sender, array $data) : array
    {
        $params = [
            'platformId'          => $this->platformId,
            'platformPartnerId'   => $this->platformPartnerId,
            'deliveryReportGates' => $this->deliveryReportGates,
            'ignoreResponse'      => false,
            'useDeliveryReport'   => true,
        ];
        foreach ($data as $item) {
            $params['sendRequestMessages'][] = [
                'source'      => $sender,
                'sourceTON'   => 'ALPHANUMERIC',
                'destination' => $item[0],
                'userData'    => $item[1],
            ];
        }

        return $params;
    }

    public function SendOne($phoneNumber, $sender, $content, $phoneCode, $concatMsg = 0, $unicodeMsg = 1, $sendTime = null, $validity = null)
    {
        $result = [
            'status' => false
        ];

        $data = [
            'source' => $sender,
            'destination' => '+' . $phoneCode . $phoneNumber,
            'userData' => $content,
        ];

        $response = $this->SendRequest($this->prepareParams($data), 'send');

        if ($response['status']) {
            $result['status'] = true;
            $tokenData = [
                'phoneNumber' => $phoneNumber,
                'phoneCode' => $phoneCode,
                'msgid' => $response['result']['messageId'],
                'code' => $this->getValidateCode(),
            ];
            $result['token'] = self::encodeToken($tokenData);

            Db::query('INSERT INTO `sms_delivery_status` SET ' .
                '`provider` = "linkmobility" ,' .
                '`msgid` = "' . Db::escape($response['result']['messageId']) . '", ' .
                '`status` = "' . Db::escape($response['result']['resultCode']) . '", ' .
                '`updated` = NOW()'
            );

        } else {
            $result['result'] = $response['result'];
        }

        return $result;
    }

    /**
     * @param array $content, Format: array([phone_number1, sms_content1], [phone_number2, sms_content2], ... , [phone_numberN, sms_contentN])
     */
    public function SendMultiple($sender, Array $content, $countryCode, $concatMsg = 0, $unicodeMsg = 1, $sendTime = null, $validity = null)
    {
        $response = $this->SendRequest($this->prepareParamsBatch($sender, $content), 'sendbatch');

        return $response;
    }

    public function getSmsStatus($token)
    {
        $result = false;
        $tokenData = self::decodeToken($token);

        if (empty($tokenData) || empty($tokenData['msgid'])) {
            return false;
        }

        $msgid = explode(',', $tokenData['msgid']);
        $row = Db::fetchRow('SELECT `status` FROM `sms_delivery_status` WHERE `msgid` = "' . Db::escape($msgid[0]) . '" AND `provider` = "linkmobility"');

        if ($row && isset($row->status)) {
            $result = $this->getStatusByCode($row->status);
        }

        return $result;
    }

    private function getStatusByCode(int $code) :string
    {
        if (in_array($code, [1000,1005,1008,1011,1012])) {
            return self::$STATE_QUEUE;
        } elseif (in_array($code, [1001,1007])) {
            return self::$STATE_DELIVERED;
        } elseif (in_array($code, [1006,1009])) {
            return self::$STATE_UNDELIVERED;
        } elseif (in_array($code, [1002,1004,2103,2104,2105,2106,2107,2200,2201,2202,2203,2204,2205,2206,2207,4000,4001,4002,4003,4004,4005,4006,4007])) {
            return self::$STATE_FAILED;
        } elseif (in_array($code, [1,2,3,4,5,6,104,105,3000,3001])) {
            return self::$STATE_Error;
        } elseif (in_array($code, [0,1010])) {
            return self::$STATE_UNKNOWN;
        } else {
            return self::$STATE_UNKNOWN;
        }
    }


    protected function SendRequest($data = [], $route = '')
    {
        $url = $this->apiUrl . '/' . $route;
        $headers = [];
        $headers[] = 'Content-Type: application/json; charset=utf-8';
        $data = json_encode($data);

        $ch = curl_init();

        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERPWD => $this->username . ":" . $this->password,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => true,
        ];

        curl_setopt_array($ch, $curlOptions);
        $response = curl_exec($ch);
        $responseErrNo = curl_errno($ch);
        $responseError = curl_error($ch);
        $http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close($ch);

        return $this->parseResponse($response, $responseErrNo, $responseError, $http_code, $route == 'sendbatch');
    }

    protected function parseResponse($response, $errno = 0, $error = null, $http_code = 200, $batch = false)
    {
        $result = json_decode($response, true);

        $rv = [
            'status' => true,
            'result' => $result,
        ];

        if($errno != 0 || empty($rv['result'])) {
            $rv['status'] = false;
            $rv['result'] = _('Server returned invalid response');
        } elseif ($http_code != 200) {
            $rv['status'] = false;
            if ($batch) {
                unset($rv['result']);
                foreach ($result as $item)
                {
                    $rv['result'][$item['messageId']] = $this->getCodeDescription($item['resultCode'] ?? 0);
                }
            } else {
                $rv['result'] = $this->getCodeDescription($result['resultCode'] ?? 0);
            }
        }

        if (!$rv['status']) {
            error_log('SMS Send Failed: '. (is_array($rv['result']) ? json_encode($rv['result']) : $rv['result']) . '. Response: '  . json_encode($response));
        }

        return $rv;
    }

    public static function hanldeCallback($data) {

        if (!empty($data['id']) && isset($data['resultCode'])) {
            $row = Db::fetchRow('SELECT `id` FROM `sms_delivery_status` WHERE `msgid` = "' . Db::escape($data['id']) . '" AND `provider` = "linkmobility"');

            if ($row && isset($row->id) && $row->id > 0) {
                Db::query('UPDATE `sms_delivery_status` SET ' .
                    '`status` = "' . Db::escape($data['resultCode']) . '", ' .
                    '`updated` = NOW()' .
                    'WHERE `id` = ' . $row->id
                );
            } else {
                Db::query('INSERT INTO `sms_delivery_status` SET ' .
                    '`provider` = "linkmobility" ,' .
                    '`msgid` = "' . Db::escape($data['id']) . '", ' .
                    '`status` = "' . Db::escape($data['resultCode']) . '", ' .
                    '`updated` = NOW()'
                );
            }
        }
    }

    protected function getCodeDescription(int $code = 0) :string
    {
        $resultCodes =
            [
                0      => _('Unknown error'),
                1      => _('Temporary routing error'),
                2      => _('Permanent routing error'),
                3      => _('Maximum throttling exceeded'),
                4      => _('Timeout'),
                5      => _('Operator unknown error'),
                6      => _('Operator error'),
                104    => _('Configuration error'),
                105    => _('Internal error (internal LinkMobilityerror)'),
                1000   => _('Sent (to operator)'),
                1001   => _('Billed and delivered'),
                1002   => _('Expired'),
                1004   => _('Mobile full'),
                1005   => _('Queued'),
                1006   => _('Not delivered'),
                1007   => _('Delivered,Billed delayed'),
                1008   => _('Billed OK (charged OK before sending message)'),
                1009   => _('Billed OK and NOT Delivered'),
                1010   => _('Expired, absence of operator delivery report'),
                1011   => _('Billed OK and sent (to operator)'),
                1012   => _('Delayed (temporary billing error, system will try to resend)'),
                1013   => _('Message sent to operator, Billdelayed'),
                2103   => _('Service rejected by subscriber'),
                2104   => _('Unknown subscriber'),
                2105   => _('Destination blocked (subscriber permanently barred)'),
                2016   => _('Number error'),
                2107   => _('Destination temporarily blocked (subscriber temporarily barred)'),
                2200   => _('Charging error'),
                2201   => _('Subscriber has low balance'),
                2202   => _('Subscriber barred for overcharged (premium) messages'),
                2203   => _('Subscriber too young (for this particular content)'),
                2204   => _('Prepaid subscriber not allowed'),
                2205   => _('Service rejected by subscriber'),
                2206   => _('Subscriber not registered in payment system'),
                2207   => _('Subscriber has reached max balance'),
                3000   => _('GSM encoding is not supported'),
                3001   => _('UCS2 encoding is not supported'),
                3002   => _('Binary encoding is not supported'),
                4000   => _('Delivery report is not supported'),
                4001   => _('Invalid message content'),
                4002   => _('Invalid tariff'),
                4003   => _('Invalid user data'),
                4004   => _('Invalid user data header'),
                4005   => _('Invalid data coding'),
                4006   => _('Invalid VAT'),
                4007   => _('Unsupported content for destination'),
                106000 => _('Unknown Error. Please contact Support and include your whole transaction'),
                106100 => _('Invalid authentication. Please check your username and password'),
                106101 => _('Access denied. Please check your username and password'),
                106200 => _('Invalid or missing platformId. Please check your platformId'),
                106201 => _('Invalid or missing platformPartnerId. Please check your platformPartnerId'),
                106202 => _('Invalid or missing currency for premium message. Please check your price and currency'),
                106300 => _('No gates available. Please contact Support and include your whole transaction'),
                106301 => _('Specified gate unavailable. Please check your gateId.'),

            ];
        return $resultCodes[$code] ?? $resultCodes[0] ;
    }

}
