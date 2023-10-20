<?php
namespace eGamings\WLC\Sms;

class SalesLvProvider extends  AbstractProvider
{
    /**
     * @param array $responce
     * @return array|mixed|object
     */
    protected function parseResponse($response, $errno = 0, $error = null, $http_code = 200, $batch = false)
    {
        $result = json_decode($response, true);
        $rv = [
            'status' => true,
            'result' => $result,
            'errno' => $errno,
            'error' => $error
        ];

        if($errno != 0 || empty($rv['result'])) {
            $rv['status'] = false;
            $rv['result'] = _('Server returned invalid response');
        } else if (!empty($result['Error'])) {
            $rv['status'] = false;
            $rv['result'] = $this->getErrorDescription($result['Error']);
        }

        if (!$rv['status']) {
        	error_log('SMS Send Failed: ' . $errno . ', ' . $error . ', ' . json_encode($response));
        }

        return $rv; 
    }

    /**
     * Get list of senders
     * @return array
     */
    public function GetSenders()
    {
        return $this->SendRequest($this->setParamComand(__FUNCTION__));
    }

    /**
     * Get status sending a message
     * @param $sms_ids
     * @return array
     */
    public function GetDelivery($sms_ids)
    {
        return $this->SendRequest($this->setParamComand(
            __FUNCTION__,
            ['MSSID' => is_array($sms_ids) ? json_encode($sms_ids) : $sms_ids]
        ));
    }

    /**
     * Creating a distribution list
     * @param $sender
     * @param $content
     * @param int $countryCode
     * @param int $concatMsg
     * @param int $unicodeMsg
     * @param null $sendTime
     * @param null $validity
     * @return array
     */
    public function SendBatch($sender, $content, $countryCode, $concatMsg = 0, $unicodeMsg = 1, $sendTime = null, $validity = null)
    {
        $req_params = [
            'Sender' => $sender,
            'Content' => $content,
            'CC' => $countryCode,
            'Concatenated' => $concatMsg,
            'Unicode' => $unicodeMsg,
            'SendTime' => $sendTime,
            'Validity' => $validity
        ];

        return $this->SendRequest($this->setParamComand(
            __FUNCTION__,
            $req_params
        ));
    }

    /**
     * Add numbers of recipients
     * in a distribution list
     * @param $batchID
     * @param array $recipients
     * @return array
     */
    public function AddBatchRecipients($batchID, Array $recipients)
    {
        return $this->SendRequest($this->setParamComand(
            __FUNCTION__,
            [
                'BatchID' => !empty($batchID) ? $batchID : null,
                'Recipients' => json_encode($recipients)
            ]
        ));
    }


    public function SendOne($phoneNumber, $sender, $content, $phoneCode, $concatMsg = 0, $unicodeMsg = 1, $sendTime = null, $validity = null)
    {
        $result = [
            'status' => false
        ];

        $req_params = [
            'Number' => $phoneNumber,
            'Sender' => $sender,
            'Content' => $content,
            'CC' => $phoneCode,
            'Concatenated' => $concatMsg,
            'Unicode' => $unicodeMsg,
            'SendTime' => $sendTime,
            'Validity' => $validity
        ];

        $response = $this->SendRequest($this->setParamComand(
            __FUNCTION__,
            $req_params
        ));

        if ($response['status']) {
            $result['status'] = true;
            //$_SESSION['userSmsID'] = $response['result']['MSSID'];
            $tokenData = [
            	'phoneNumber' => $phoneNumber,
            	'phoneCode' => $phoneCode,
                'msgid' => $response['result']['MSSID'],
                'code' => $this->getValidateCode()
            ];
            $result['token'] = self::encodeToken($tokenData);
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
        $req_params = [
            'Sender' => $sender,
            'Content' => json_encode($content, JSON_UNESCAPED_UNICODE),
            'CC' => $countryCode,
            'Concatenated' => $concatMsg,
            'Unicode' => $unicodeMsg,
            'SendTime' => $sendTime,
            'Validity' => $validity
        ];

        return $this->SendRequest($this->setParamComand(
            __FUNCTION__,
            $req_params
        ));
    }

    public function getSmsStatus($token)
    {
        $result = false;
        $tokenData = self::decodeToken($token);

        if (empty($tokenData) || empty($tokenData['msgid'])) {
            return false;
        }

        $sms_id = $tokenData['msgid'];
        $sms_report = $this->GetDelivery($sms_id);

        if (!$sms_report['status']) {
            return false;
        }

        $state = $sms_report['result'][$sms_id];

        switch($state) {
            case 'Error':
            case 'Other':
                $result = self::$STATE_FAILED;
                break;
            case 'Delivered':
                $result = self::$STATE_DELIVERED;
                break;
            case 'Queue':
                $result = self::$STATE_QUEUE;
                break;
            case 'Buffered':
           	    $result = self::$STATE_BUFFERED;
                break;
            case 'Sent':
                $result = self::$STATE_SENT;
                break;
            case 'Canceled':
                $result = self::$STATE_CANCELED;
                break;
            default:
                $result = self::$STATE_UNKNOWN;
                break;
        }

        return $result;
    }

    public function ValidateSms($code, $token)
    {
        $tokenData = self::decodeToken($token);

        if (empty($tokenData) || empty($tokenData['msgid']) || empty($tokenData['code'])) {
            return false;
        }

        if ($code != $tokenData['code']) {
            return false;
        }

        return true;
    }

    /**
     * Get report message
     * @param $sms_ids
     * @return array
     */
    public function GetReport($sms_ids)
    {
        return $this->SendRequest($this->setParamComand(
            __FUNCTION__,
            ['MSSID' => is_array($sms_ids) ? json_encode($sms_ids) : $sms_ids]
        ));
    }

    /**
     * Set request params
     * @param $comandName
     * @param array $req_params
     * @return array
     */
    private function setParamComand($comandName, $req_params = [])
    {
        $req_params = array_filter($req_params,
            function ($el) {
                return isset($el);
            }
        );

        return array_merge($req_params, [
            'Command' => $comandName,
            'APIKey' => $this->apiKey
        ]);
    }

    /**
     * Obtain a description
     * of the error code
     * @param $error_code
     * @return array
     */
    private function getErrorDescription($error_code)
    {
    	static $response_errors = null;
    	
    	if ($response_errors === null) $response_errors = [
   			'InvalidAPIVersion'                 => _('Incorrect version API in address'),
   			'NoAPIKey'                          => _('Not specified API key'),
   			'InvalidAPIKey'                     => _('Incorrect API key'),
   			'UnauthorizedIP'                    => _('Incorrect IP address client'),
   			'CommandNotSpecified'               => _('Invalid command'),
   			'CommandNotImplemented'             => _('Specified command does not exist or is not available'),
   			'SystemError'                       => _('System error'),
   			'InvalidMSSID'                      => _('Not specified the SMS identifier'),
   			'InvalidSender'                     => _('Sender address does not exist'),
   			'NoContent'                         => _('Not specified content'),
   			'InvalidCC'                         => _('Faulty or unsupported country code'),
   			'ContentTooLong'                    => _('Content is too long'),
   			'ContentContainsInvalidCharacters'  => _('Incorrect encoding of the content'),
   			'MonthlyQuotaExceeded'              => _('Exceeded monthly limit'),
   			'InvalidBatchID'                    => _('Batch ID does not exist'),
   			'InvalidRecipients'                 => _('List recipients does not exist'),
   			'InvalidNumber'                     => _('Invalid recipient number'),
   			'NoContent'                         => _('No content'),
   			'InvalidContent'                    => _('Wrong content'),
    	];
        return !empty($response_errors[$error_code]) ? $response_errors[$error_code] : _('Unknown error');
    }

}