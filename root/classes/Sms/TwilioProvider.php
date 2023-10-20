<?php
namespace eGamings\WLC\Sms;
use eGamings\WLC\Sms\Twilio\TwilioCurlClient;
use Twilio\Rest\Client;
use eGamings\WLC\Core;

class TwilioProviderErrors {
	const INVALID_API_VERSION = 'Incorrect version API in address';
}

class TwilioProvider extends AbstractProvider
{
	protected $client = null;

    function __construct(array $config) {
        parent::__construct($config);

        $httpClient = new TwilioCurlClient();

        $this->client = new Client($this->apiKey, $this->privateKey, null, null, $httpClient);
    }

    /**
     * @param array $responce
     * @return array|mixed|object
     */
    protected function parseResponse($response, $errno = 0, $error = null, $http_code = 200, $batch = false)
    {
        return false; 
    }

    protected function SendRequest($data = [] , $route = '')
    {
    	return false;
    }
    

    /**
     * Get list of senders
     * @return array
     */
    public function GetSenders()
    {
    	// Not supported
    	return [];
    }

    /**
     * Get status sending a message
     * @param $sms_ids
     * @return array
     */
    public function GetDelivery($sms_id)
    {
    	$result = false;
    	try {
    		$result = $this->client->messages($sms_id)->fetch();
    	} catch(\Exception $ex) { }
        return $result;
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
    	// Not supported
    	return false;
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
    	// Not supported
        return false;
    }


    public function SendOne($phoneNumber, $sender, $content, $phoneCode, $concatMsg = 0, $unicodeMsg = 1, $sendTime = null, $validity = null)
    {
        $result = [
            'status' => false
        ];

        $message = false;

        $redisKey = 'phoneValidationSMS_' . $phoneCode . $phoneNumber;
        $Redis = Core::getInstance()->redisCache();

        if (!$this->userLimitPerHour || !$Redis->exists($redisKey) || $Redis->get($redisKey) < $this->userLimitPerHour)
        {
            try {
                $message = $this->client->messages->create(
                    '+' . $phoneCode . '' . $phoneNumber,
                    ['from' => $sender, 'body' => $content]
                );

            } catch (\Exception $ex) {
                // @TODO add error logging
                $message = _('Unable send message') . ': ' . $ex->getMessage();
            }
            if (is_object($message)) {
                $result['status'] = true;
                $tokenData = [
                    'phoneNumber' => $phoneNumber,
                    'phoneCode' => $phoneCode,
                    'msgid' => $message->sid,
                    'code' => $this->getValidateCode(),
                    'timestamp' => time(),
                ];
                $result['token'] = self::encodeToken($tokenData);

                if ($this->userLimitPerHour) {
                    if ($Redis->exists($redisKey)){
                        $Redis->set($redisKey, $Redis->get($redisKey)+1, 3600);

                    } else {
                        $Redis->set($redisKey, '1', 3600);
                    }
                }
            } else {
                $result['result'] = $message;
            }

        } else {
            $cooldown = round($Redis->ttl($redisKey) / 60, 0, PHP_ROUND_HALF_UP);
            $result['result'] = _('The hour SMS quota was exceeded. You can retry sending SMS in %d minutes');
            $result['result'] = sprintf($result['result'], $cooldown > 1 ? $cooldown : 1);
        }

        return $result;
    }

    /**
     * @param array $content, Format: array([phone_number1, sms_content1], [phone_number2, sms_content2], ... , [phone_numberN, sms_contentN])
     */
    public function SendMultiple($sender, Array $content, $countryCode, $concatMsg = 0, $unicodeMsg = 1, $sendTime = null, $validity = null)
    {
        return false;
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

        if (!is_object($sms_report)) {
            return false;
        }

        $status = $sms_report->status;

        switch($status) {
            case 'failed':
            case 'undelivered':
                $result = self::$STATE_FAILED;
                break;
            case 'delivered':
                $result = self::$STATE_DELIVERED;
                break;
            case 'queued':
                $result = self::$STATE_QUEUE;
                break;
            case 'accepted':
           	    $result = self::$STATE_BUFFERED;
                break;
            case 'sending':
            case 'sent':
                $result = self::$STATE_SENT;
                break;
            default:
                $result = self::$STATE_UNKNOWN;
                break;
        }

        return $result;
    }
}
