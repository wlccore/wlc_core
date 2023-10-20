<?php
namespace eGamings\WLC\Sms;

use Firebase\JWT\JWT;
use eGamings\WLC\User;

abstract class AbstractProvider
{
    protected $apiUrl;
    protected $apiKey;
    protected $username;
    protected $password;
    protected $platformId;
    protected $platformPartnerId;
    protected $deliveryReportGates;
    protected $sender;
    protected $message;
    protected $privateKey;
    protected $codeLength;
    protected $messageCode;
    protected $codeTTL;
    protected $userLimitPerHour;

    protected $encAlgo = 'AES256';
    protected $signAlgo = 'HS256';

    static $STATE_BUFFERED = 'Buffered';
    static $STATE_QUEUE = 'Queue';
    static $STATE_SENT = 'Sent';
    static $STATE_DELIVERED = 'Delivered';
    static $STATE_UNDELIVERED = 'Undelivered';
    static $STATE_Error = 'Error';
    static $STATE_FAILED = 'Failed';
    static $STATE_CANCELED = 'Canceled';
    static $STATE_OTHER = 'Other';
    static $STATE_UNKNOWN = 'Unknown';

    function __construct($config)
    {
        $this->apiUrl = !empty($config['apiUrl']) ? $config['apiUrl'] : '';
        $this->apiKey = !empty($config['apiKey']) ? $config['apiKey'] : '';
        $this->username = !empty($config['username']) ? $config['username'] : '';
        $this->password = !empty($config['password']) ? $config['password'] : '';
        $this->platformId = !empty($config['platformId']) ? $config['platformId'] : '';
        $this->platformPartnerId = !empty($config['platformPartnerId']) ? $config['platformPartnerId'] : '';
        $this->deliveryReportGates = !empty($config['deliveryReportGates']) ? $config['deliveryReportGates'] : '';
        $this->sender = !empty($config['sender']) ? $config['sender'] : '';
        $this->privateKey = !empty($config['privateKey']) ? $config['privateKey'] : '';
        $this->message = !empty($config['message']) ? $config['message'] : '';
        $this->codeLength = !empty($config['codeLength']) ? $config['codeLength'] : 5;
        $this->messageCode = '';
        $this->codeTTL = !empty($config['codeTTL']) ? $config['codeTTL'] : '';
        $this->userLimitPerHour = !empty($config['userLimitPerHour']) ? $config['userLimitPerHour'] : '';
    }

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
    abstract public function SendOne($phoneNumber, $sender, $content, $phoneCode, $concatMsg = 0, $unicodeMsg = 1, $sendTime = null, $validity = null);

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
    abstract public function SendMultiple($sender, Array $content, $countryCode, $concatMsg = 0, $unicodeMsg = 1, $sendTime = null, $validity = null);

    /**
     * @return bool
     */
    public function CheckConfig() {
        // privateKey required for php-jwt
        return !empty($this->privateKey);
    }

    /**
     * Validate SMS
     * @param $sms_id
     * @return mixed
     */
    public function ValidateSms($code, $token)
    {
    	$tokenData = self::decodeToken($token);
        if ($this->codeTTL) {
            if (empty($tokenData['timestamp']) || time() - $tokenData['timestamp'] > $this->codeTTL){
                return false;
            }
        }

    	if (empty($tokenData) || empty($tokenData['msgid']) || empty($tokenData['code'])) {
    		return false;
    	}
    	
    	if ($code != $tokenData['code']) {
    		return false;
    	}
    	
    	return true;
    }

    /**
     * Set response
     * @param $response
     * @return mixed
     */
    abstract protected function parseResponse($response, $errno = 0, $error = null, $http_code = 200, $batch = false);

    /**
     * Get sender
     * @return string
     */
    public function getDefaultSender() {
        return $this->sender;
    }

    /**
     * @param $code
     * @return mixed|string
     */
    public function getMessage($code = null) {
        $code = isset($code) ? $code : $this->genValidateCode();
        $message = empty($this->message) ? null : _($this->message);
        return empty($message) ? $code : str_replace('%code%', $code, $message);
    }

    /**
     * Send request API
     * @param array $data
     * @return array
     */
    protected function SendRequest($data = [], $route = '')
    {
        $ch = curl_init();

        $curlOptions = [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => array(),
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
        curl_close($ch);

        return $this->parseResponse($response, $responseErrNo, $responseError);
    }

    /**
     * Generate SMS code
     * @return string
     */
    public function genValidateCode()
    {
    	$code = '';
    	for ($i = 0; $i < $this->codeLength; $i++) {
    		$code .= '' . mt_rand(0,9);
    	}
        return $this->messageCode = $code; 
    }

    /**
     * Get message code
     * @return string
     */
    public function getValidateCode()
    {
        return $this->messageCode;
    }

    abstract public function getSmsStatus($token);

    /**
     * Encode token using private key
     */
    public function encodeToken($tokenData)
    {
        if (is_array($tokenData) && User::isAuthenticated()) {
            $tokenData['uid'] = $_SESSION['user']['id'];
        }

        if (_cfg('smsUseEncryption')) {
            $ivlen = openssl_cipher_iv_length($this->encAlgo);
            $iv = bin2hex(openssl_random_pseudo_bytes($ivlen / 2));
            $enc = openssl_encrypt(json_encode($tokenData), $this->encAlgo, $this->privateKey, 0, $iv);

            $tokenData = ['a'=>$this->encAlgo, 'd'=>$enc, 'iv'=>$iv];
        }

        return JWT::encode($tokenData, $this->privateKey, $this->signAlgo);
    }

    /**
     * Decode token using private key
     */
    public function decodeToken($token)
    {
        $tokenData = false;

        try {
            $tokenData = JWT::decode($token, $this->privateKey, [$this->signAlgo]);
            $tokenData = json_decode(json_encode($tokenData), true);

            if (!empty($tokenData['a']) && !empty($tokenData['d']) && !empty(!empty($tokenData['iv']))) {
                $tokenData = @openssl_decrypt($tokenData['d'], $tokenData['a'], $this->privateKey, 0, $tokenData['iv']);
                if ($tokenData) {
                    $tokenData = json_decode($tokenData, true);
                }
            }
        } catch(\Exception $ex) {}

        return $tokenData;
    }
}
