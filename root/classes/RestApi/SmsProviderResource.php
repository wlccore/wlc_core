<?php
namespace eGamings\WLC\RestApi;

use eGamings\WLC\Cache;
use eGamings\WLC\Sms;
use eGamings\WLC\System;
use eGamings\WLC\User;
use eGamings\WLC\Storage\SessionStorage;

/**
 * @SWG\Tag(
 *     name="sms",
 *     description="Sms"
 * )
 */

/**
 * Class SmsProviderResource
 * @namespace eGamings\WLC\RestApi
 * @extends AbstractResource
 * @uses eGamings\WLC\Sms
 */
class SmsProviderResource extends AbstractResource
{
    /**
     * Sms provider
     *
     * @property $SmsProvider
     * @type object
     * @private
     */
    private $SmsProvider;
    private $sessionKey = 'sms_verification_phone';
    private $_cache;
    public const SMS_VERIFICATION_CODE = 'sms_verification_code';
    public const SMS_VERIFICATION_CODE_TTL = 60*10;

    /**
     * Constructor of class
     *
     * @public
     * @constructor
     * @method __construct
     * @throws {ApiException}
     */
    public function __construct()
    {
        $this->SmsProvider = Sms::getInstance();
        $this->_cache = new Cache();

        if (!$this->SmsProvider) {
            throw new ApiException(_('Not supported exception'), 400);
        }
    }

    /**
     * @SWG\Patch(
     *     path="/sms",
     *     description="Returns sms status by token",
     *     tags={"sms"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"token"},
     *             @SWG\Property(
     *                 property="token",
     *                 type="string"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns sms status",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="status",
     *                 type="boolean",
     *                 example="true"
     *             ),
     *             @SWG\Property(
     *                 property="state",
     *                 type="string",
     *                 enum={"Buffered", "Queue", "Sent", "Delivered", "Undelivered", "Error", "Failed", "Canceled", "Other", "Unknown"}
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * Get sms status by token
     */
    public function patch($request, $query, $params = []) {
    	if (empty($request['token'])) {
    		throw new ApiException(_('Token not found'), 400);
    	}

    	$state = $this->SmsProvider->getSmsStatus($request['token']);
    	if ($state === false) {
    		throw new ApiException(_('Invalid token provided'), 400);
    	}

    	return ['status' => true, 'state' => $state];
    }

    /**
     * @SWG\Post(
     *     path="/sms",
     *     description="Send sms",
     *     tags={"sms"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"phoneCode", "phoneNumber"},
     *             @SWG\Property(
     *                 property="phoneCode",
     *                 type="string",
     *                 example="+1"
     *             ),
     *             @SWG\Property(
     *                 property="phoneNumber",
     *                 type="string",
     *                 example="9876543210"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns sms status",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="status",
     *                 type="boolean"
     *             ),
     *             @SWG\Property(
     *                 property="result",
     *                 type="string",
     *                 description="Sms provider response"
     *             ),
     *             @SWG\Property(
     *                 property="token",
     *                 type="string",
     *                 description="Unique token of sms message"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * Send SMS
     *
     * @public
     * @method post
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @throws {ApiException}
     */
    public function post($request, $query, $params = [])
    {
        $smsLogin  = isset($query['smsLogin']) && $query['smsLogin'] == 1;

        if (empty($request['phoneCode'])) {
            throw new ApiException(_('Empty required parameter') . ': phoneCode', 400);
        }

        if (empty($request['phoneNumber'])) {
            throw new ApiException(_('Empty required parameter') . ': phoneNumber', 400);
        }

        if(User::isAuthenticated()) {
            $user = User::getInstance();
            if ($user->userData->phone_verified && $user->userData->phone1 == $request['phoneCode'] && $user->userData->phone2 == $request['phoneNumber']) {
                throw new ApiException(_('Phone already verified'), 400);
            }
        }

        $phoneCode = (int)trim($request['phoneCode'], '+- ');
        $phoneNumber = (int)trim($request['phoneNumber'], '+- ');
        $sender = $this->SmsProvider->getDefaultSender();
        $message = !empty($query['action']) ? $query['message'] : $this->SmsProvider->getMessage();

        $response = $this->SmsProvider->SendOne($phoneNumber, $sender, $message, $phoneCode);

        if (!$response['status']) {
            $this->logSMSError($phoneCode, $phoneNumber, $message, $response['result']);
            throw new ApiException($response['result'], 400);
        }

        if ($smsLogin) {
            $code = $this->SmsProvider->decodeToken($response['token'])['code'] ?? '';
            $this->_cache->set(self::SMS_VERIFICATION_CODE, $code, self::SMS_VERIFICATION_CODE_TTL, ['phoneCode' => $phoneCode, 'phoneNumber' => $phoneNumber]);

            return ['status' => true];
        }

        if(User::isAuthenticated()) {
            SessionStorage::getInstance()->set($this->sessionKey, '+' . $phoneCode . '-' . $phoneNumber);
        }

        return $response;
    }

    /**
     * @SWG\Put(
     *     path="/sms",
     *     description="Validate sms",
     *     tags={"sms"},
     *     @SWG\Parameter(
     *         ref="#/parameters/lang"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @SWG\Schema(
     *             type="object",
     *             required={"smsCode", "token"},
     *             @SWG\Property(
     *                 property="smsCode",
     *                 type="string",
     *                 description="Sms code"
     *             ),
     *             @SWG\Property(
     *                 property="token",
     *                 type="string",
     *                 description="Sms token"
     *             ),
     *             @SWG\Property(
     *                 property="phoneCode",
     *                 type="string",
     *                 description="Phone code (authorized only)"
     *             ),
     *             @SWG\Property(
     *                 property="phoneNumber",
     *                 type="string",
     *                 description="Phone number (authorized only)"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returns validation result",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(
     *                 property="status",
     *                 type="boolean"
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Error",
     *         @SWG\Schema(
     *             ref="#/definitions/ApiException"
     *         )
     *     )
     * )
     */

    /**
     * Validate SMS
     *
     * @public
     * @method put
     * @param {array} $request
     * @param {array} $query
     * @params {array} [$params=[]]
     * @throws {ApiException}
     */
    public function put($request, $query, $params = [])
    {
        if (empty($request['smsCode']) || empty($request['token'])) {
        	throw new ApiException(_('Insufficient parameters'), 400);
        }

        if (!$this->SmsProvider->CheckConfig()) {
            throw new ApiException(_('Configuration error'), 400);
        }

        $status = $this->SmsProvider->ValidateSms($request['smsCode'], $request['token']);
        if (!$status) {
            throw new ApiException(_('Invalid verification code'), 400);
        }

        $tokenData = $this->SmsProvider->decodeToken($request['token']);
        if (is_object($tokenData)) {
            $tokenData = (array) $tokenData;
        }

        $user = null;
        $savedPhone = '';
        $reqPhoneCode = (!empty($request['phoneCode'])) ? intval(trim($request['phoneCode'], '+- ')) : '';
        $reqPhoneNumber = (!empty($request['phoneNumber'])) ? intval(trim($request['phoneNumber'], '+- ')) : '';

        if (User::isAuthenticated() || !empty($tokenData['uid'])) {
            if (!empty($tokenData['uid'])) {
                $user = new User($tokenData['uid']);
                $savedPhone = $tokenData['phoneCode'] . '-' . $tokenData['phoneNumber'];
            } else {
                $user = new User();
                $savedPhone = SessionStorage::getInstance()->get($this->sessionKey);
                if (empty($savedPhone)) {
                    throw new ApiException(_('Empty phone number'), 400);
                }
            }

            $phone = explode('-', trim($savedPhone, '+- '), 2);
            $phoneCode = intval($phone[0]);
            $phoneNumber = !empty($phone[1]) ? intval($phone[1]) : '';

            if (empty($reqPhoneCode) || empty($reqPhoneNumber) ||
                empty($phoneNumber) || empty($phoneCode) ||
                $reqPhoneCode !== $phoneCode || $reqPhoneNumber !== $phoneNumber) {
                throw new ApiException(_('Invalid phone number'), 400);
            }

            $res = $user->verifyUser($phoneCode, $phoneNumber);
            if ($res[0] != 1) {
                throw new ApiException($res[1], 400, null, [], $res[0]);
            }
        }

        return ['status' => $status];
    }

    public function logSMSError($phoneCode, $phoneNumber, $message, $response)
    {
        $S = new System();
        $url = '/WLCRegistrationError/SMS?';
        $transactionId = $S->getApiTID($url);
        $hash = md5('WLCRegistrationError/SMS/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
        $params = [
            'TID' => $transactionId,
            'Hash' => $hash,
            'SiteName' => _cfg('websiteName'),
            'Site' => _cfg('site'),
            'Number' => '+' . $phoneCode . '-' . $phoneNumber,
            'Message' => $message,
            'Response' => $response,

        ];
        $url .= '&' . http_build_query($params);
        return $S->runFundistAPI($url);
    }
}
