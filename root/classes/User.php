<?php
namespace eGamings\WLC;

use eGamings\WLC\Core;
use eGamings\WLC\Domain\TrustDeviceConfiguration\TrustDeviceConfiguration;
use eGamings\WLC\Fundist;
use eGamings\WLC\Cache;
use eGamings\WLC\Provider\IUser;
use eGamings\WLC\RestApi\ApiException;
use eGamings\WLC\RestApi\AuthResource;
use eGamings\WLC\RestApi\SmsProviderResource;
use eGamings\WLC\Service\Common;
use eGamings\WLC\Service\CookieProtection;
use eGamings\WLC\Service\TrustDevice;
use eGamings\WLC\Validators\Rules\EtheriumSignatureValidatorRules;
use eGamings\WLC\Validators\Rules\UserProfilePartialValidatorRules;
use eGamings\WLC\Validators\UniquephoneValidator;
use eGamings\WLC\Storage\CookieStorage;
use eGamings\WLC\FundistEmailTemplate;
use eGamings\WLC\Validators\Rules\UserProfileIbanValidatorRules;
use eGamings\WLC\PrometheusKeys;
use eGamings\WLC\RestApi\ApiExceptionWithData;
use eGamings\WLC\Storage\SessionStorage;
use stdClass;

class User extends System implements IUser
{
    private static $salt = 'IUJ(*@U(D98192812ij(*(!@((!@(';

    public static $NONE              = 0;
    public static $CONFIRMATION_CODE = 1 << 0;
    public static $REGISTRATION      = 1 << 1;
    public static $userState = 0;

    private $_cache;
    static public $isTest = false;

    public $userData = false;

    const LOYALTY_LEVEL_INITIAL = 1;

    static private $userCheckData = false;

    static private $_instance = null;

    private const SKIP_AFFILIATE_CHECK_SITE = 'MaxCazino';

    public const LOGIN_TYPE_DEFAULT = '';
    public const LOGIN_TYPE_METAMASK = 'metamask';
    public const LOGIN_TYPE_SMS = 'sms';

    private const USER_INFO_CACHE_PREFIX = "user_info_";

    public function __construct($id = null)
    {
        $this->_cache = new Cache();

        if ($id === null) {
            // handle current user (from $_SESSION)
            $this->checkAffiliate();

            if (!self::$userCheckData) {
                $user = $this->checkUser();

                if (isset($user->id) && $user->id) {
                    $this->userData = $user;
                    self::$userCheckData = $user;
                }
            } else {
                $this->userData = self::$userCheckData;
            }
        } else {
            // fetch from database
            $this->userData = $this->getUserById($id);
        }
    }

    static function getInstance(): User {
    	if (!self::$_instance) {
    		self::$_instance = new User();
    	}
    	return self::$_instance;
    }

    public function fundist_uid($Login = false, $check_status = false)
    {

        if (!$Login && !$Login = Front::User('id')) {
            return 0;
        }

        if (!isset($_SESSION['FundistIDUser']) || !$_SESSION['FundistIDUser']) {
            $url = '/WLCInfo/ID?';
            $transactionId = $this->getApiTID($url);

            $hash = md5('WLCInfo/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $Login . '/' . _cfg('fundistApiPass'));
            $params = Array(
                'Login' => $Login,
                'TID' => $transactionId,
                'Hash' => $hash,
                'UserIP' => System::getUserIP(),
            );

            $url .= '&' . http_build_query($params);

            $response = $this->runFundistAPI($url);
            $data = explode(',', $response);

            if ($data[0] === '1' && isset($data[1]) && isset($data[2])) {
                if ($check_status) {
                    if ($data[2] < 0) {
                        return 0;
                    }
                }

                $_SESSION['FundistIDUser'] = $data[1];

                return $_SESSION['FundistIDUser'];
            }
            else if ($data[0] === '17' && $check_status) {
                return false;
            }
            else {
                //next line should not be called ever
                die(_('must_login'));
            }
        }

        return $_SESSION['FundistIDUser'];
    }

    /**
     * hash is old-style, need to upgrade (returned from verifyPassword)
     */
    const OLD_HASH = '777';

    /**
     * @var \PasswordLib\PasswordLib
     */
    protected static $_lib = null;

    public static function passwordLib()
    {
        if (self::$_lib === null) {
            self::$_lib = new \PasswordLib\PasswordLib();
        }
        return self::$_lib;
    }

    /**
     * compute new variant of password hash
     *
     * @param $password
     * @return string
     */
    public static function passwordHash($password)
    {
        return self::passwordLib()->createPasswordHash($password, '$2y$', ['cost' => 11]);
    }

    /**
     * check if password matches hash
     *
     * @param $password
     * @param $hash
     * @return bool|string self::OLD_HASH if old hash is detected
     */
    public static function verifyPassword($password, $hash)
    {
        if (isset($_SERVER['TEST_RUN'])) {
            return true;
        }
        // crypt-style hash (new)
        if (substr($hash, 0, 1) == '$') {
            return self::passwordLib()->verifyPasswordHash($password, $hash) ? true : false;
        }

        // old salted hash
        if (sha1(md5(base64_encode($password) . self::$salt)) === $hash) {
            return self::OLD_HASH;
        }

        return false;
    }

    /**
     * Generate strong password
     *
     * @param integer $length
     * @return string
     */
    public function generatePassword($length = 8, $useSpecialChars = true)
    {
        $chars = [];
        $chars[0] = "abcdefghijklmnopqrstuvwxyz";
        $chars[1] = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $chars[2] = "0123456789";

        if ($useSpecialChars) {
            $chars[3] = "!@#$%^&*()_-=+;:,.?";
        }

        $charsArrayCount = count($chars);

        $pass = '';
        for ($i = 0; $i < $length; $i++) {
            $pass .= substr(str_shuffle($chars[$i % $charsArrayCount]), 0, 1);
        }

        return str_shuffle($pass);
    }

    public function tryConnectSocial($data)
    {
        foreach ($_SESSION['social'] as $k => $v) {
            $socialUid = isset($v['uid']) ? $v['uid'] : $v['id'];
            $social = $k;
        }
        $email = $data['email'];
        $code = sha1($email . '/' . $social . '/' . $socialUid . '/' . microtime());

        Db::query(
            'INSERT INTO `social_connect` SET ' .
            '`email` = "' . Db::escape($email) . '", ' .
            '`code` = "' . $code . '", ' .
            '`social` = "' . $social . '", ' .
            '`social_uid` = ' . $socialUid . ' '
        );

        $templateName = 'connect-social';
        $templateContext = [
            'code' => $code,
            'url' => _cfg('site') . '/run/social-code',
            'site_url' => _cfg('site')
        ];

        $template = new Template();
        $msg = $template->getMailTemplate($templateName, $templateContext);

        $msgReplaceKeys = [];
        $msgReplaceVals = [];
        foreach($templateContext as $k => $v) {
            $msgReplaceKeys[] = '%'.$k.'%';
            $msgReplaceVals[] = $v;
        }

        $msg = str_replace( $msgReplaceKeys, $msgReplaceVals, $msg );
        $mailMsg = Email::send($email, _('connect_social_account_email_theme'), $msg);
        if (!$mailMsg) {
            return '0;Can\'t send email';
        }

        return '1;' . _('email_to_connect_social_sent');
    }

    public function sendSocialRegistrationCompletedEmail($data) {
        $templateName = 'registration-social';
        $templateContext = [
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'email' => $data['email'],
            'social_network' => Social::getName($data['socialKey']),
            'site_url' => _cfg('site')
        ];

        $template = new Template();
        $msg = $template->getMailTemplate($templateName, $templateContext);

        if ($msg === false) {
            return false;
        }

        $msgReplaceKeys = [];
        $msgReplaceVals = [];
        foreach($templateContext as $k => $v) {
            $msgReplaceKeys[] = '%'.$k.'%';
            $msgReplaceVals[] = $v;
        }

        $msg = str_replace( $msgReplaceKeys, $msgReplaceVals, $msg );
        $mailMsg = Email::send($data['email'], _('Registration complete'), $msg);
        // @codeCoverageIgnoreStart
        PrometheusKeys::getInstance()->REG_COMPLETED->store();
        // @codeCoverageIgnoreEnd
        if ($mailMsg !== true) {
            return $mailMsg;
        }

        return true;
    }

    /**
     * Use User.completeSocialRegistration
     *
     * @deprecated
     * @param $data
     * @return bool|mixed|string|void
     */
    public function registerFinishSocial($data)
    {
        $check_errors = System::hook('user:befor:register', $data);
        if(!empty($check_errors['error'])){
            return json_encode(array('error' => $check_errors['error']), JSON_UNESCAPED_UNICODE);
        }

        if(!empty($check_errors['data'])) {
            $data = array_merge($data,$check_errors['data']);
        }

        if (($fraud = $this->checkIP()) != 1) {
            return isset($fraud['ip']) ? strtr(_('_ip_fraud_msg'), $fraud) : $fraud;
        }

        $data['socialKey'] = '';

        CookieStorage::getInstance()->set('social_user_info', $data, 0, true);

        foreach ($_SESSION['social'] as $k => $v) {
            $social = $v;
            $data['socialKey'] = $k;
        }

        //Adding blank code
        $data['code'] = '';

        //Checking if password is even given, if it is, then it probably must be set by WLC idea
        //if not, adding social_*
        if (!isset($data['password'])) {
            $data['password'] = $this->generatePassword();
        }

        $error = $this->userDataCheck($data);

        //If at least 1 error exist, return errors json
        if (!empty($error)) {
            return json_encode(array('error' => $error), JSON_UNESCAPED_UNICODE);
        }
        $data['uhash'] = sha1(md5(base64_encode($data['password']) . self::$salt));
        $data['password'] = self::passwordHash($data['password']);

        //Registering user in local WLC table
        $uid = $this->registerDB($data);

        $answer = $this->finishRegistration($uid, array(
            'social' => $data['socialKey'],
            'social_uid' => (isset($social['uid']) ? $social['uid'] : $social['id'])
        ));

        if (_cfg('dontSendSocialEmail') === false) {
            $mailMsg = $this->sendSocialRegistrationCompletedEmail($data);
            if ($mailMsg !== true) {
                return $mailMsg;
            }
        }

        $user = [];
        $user['social'] = $data['socialKey'];
        $user['social_uid'] = $social['id'];

        //self::socialLogin($user);

        unset($_SESSION['social']);

        return true;
    }

    /**
     * Completes social registration for user.
     *
     * @param $userInfo
     * @return bool
     * @throws \Exception
     */
    public function completeSocialRegistration($userInfo)
    {
        $userInfo = User::transformProfileData($userInfo);

        $check_errors = System::hook('user:befor:register', $userInfo);
        if(!empty($check_errors['error'])){
            return false;
        }

        if(!empty($check_errors['data'])) {
            $userInfo = array_merge($userInfo,$check_errors['data']);
        }

        $socialParams = CookieStorage::getInstance()->get('social_user_info');
        $userInfo['social'] = $socialParams['social'];
        $userInfo['social_uid'] = $socialParams['social_uid'];

        //Adding blank code
        $userInfo['code'] = '';

        //Checking if password is even given, if it is, then it probably must be set by WLC idea
        //if not, adding social_*
        if (!isset($userInfo['password'])) {
            $userInfo['password'] = $this->generatePassword();
        }

        $error = $this->userDataCheck($userInfo);

        //If at least 1 error exist, return errors json
        if (!empty($error)) {
            //TODO: use ApiException, fill it with errors
            throw new \Exception(json_encode($error), 400);
        }

        $userInfo['uhash'] = sha1(md5(base64_encode($userInfo['password']) . self::$salt));
        $userInfo['password'] = self::passwordHash($userInfo['password']);



        //Registering user in local WLC table
        $uid = $this->registerDB($userInfo);

        if (!$uid) {
            return false;
        }

        $answer = $this->finishRegistration($uid, $userInfo);

        //return false;

        if (!$answer) {
            return false;
        }

        $mailMsg = $this->sendSocialRegistrationCompletedEmail(array(
            'firstName' => $userInfo['firstName'],
            'lastName' => $userInfo['lastName'],
            'email' => $userInfo['email'],
            'socialKey' => $socialParams['social']
        ));

        if ($mailMsg !== true) {
            return $mailMsg;
        }

        //self::socialLogin($user);

        CookieStorage::getInstance()->remove('social_user_info');

        return true;
    }

    /**
     * Transform compatible user data from/to request/response
     *
     * @private
     * @method transformProfileData
     * @param {array} $data
     * @param {boolean} [$isRequest=true]
     * @return {array}
     */
    public static function transformProfileData($data, $isRequest = true) {
        $result = [];

        $fieldsMap = [
            'idUser' => ['id' => 'idUser', 'restrict' => 'response'],
            'firstName' => ['id' => 'firstName', 'restrict' => 'request'],
            'lastName' => ['id' => 'lastName', 'restrict' => 'request'],
            'first_name' => ['id' => 'firstName', 'restrict' => 'response'],
            'last_name' => ['id' => 'lastName', 'restrict' => 'response'],
            'email' => ['id' => 'email'],
            'login' => ['id' => 'login', 'required' => false],
            'password' => ['id' => 'password', 'required' => false],
            'repeatPassword' => ['id' => 'newPasswordRepeat', 'required' => false],
            'repeat_password' => ['id' => 'passwordRepeat', 'required' => false],
            'country' => ['id' => 'countryCode'],
            'sendSMS' => ['id' => 'sendSMS', 'default' => true],
            'sendEmail' => ['id' => 'sendEmail', 'default' => true],
            'currency' => ['id' => 'currency'],
            'sex' => ['id' => 'gender', 'required' => false],
            'IDNumber' => ['id' => 'idNumber', 'required' => false],
            'postal_code' => ['id' => 'postalCode', 'required' => false],
            'city' => ['id' => 'city', 'required' => false],
            'address' => ['id' => 'address', 'required' => false],
            'birth_day' => ['id' => 'birthDay', 'required' => false],
            'birth_month' => ['id' => 'birthMonth', 'required' => false],
            'birth_year' => ['id' => 'birthYear', 'required' => false],
            'pre_phone' => ['id' => 'phoneCode', 'restrict' => 'request', 'required' => false],
            'main_phone' => ['id' => 'phoneNumber', 'restrict' => 'request', 'required' => false],
            'pre_alternate_phone' => ['id' => 'phoneAltCode', 'required' => false],
            'main_alternate_phone' => ['id' => 'phoneAltNumber', 'required' => false],
            'ext_profile' => ['id' => 'extProfile', 'required' => false, 'default' => []],
            'sms_code' => ['id' => 'smsCode', 'required' => false],
            'reg_promo' => ['id' => 'registrationPromoCode', 'required' => false],
            'reg_bonus' => ['id' => 'registrationBonus', 'required' => false],
            'phone1' => ['id' => 'phoneCode', 'restrict' => 'response', 'required' => false],
            'phone2' => ['id' => 'phoneNumber', 'restrict' => 'response', 'required' => false],
            'currentPassword' => ['id' => 'currentPassword', 'required' => false],
            'new_email' => ['id' => 'newEmail'],
            'phone_verified' => ['id' => 'phoneVerified'],
            'email_verified' => ['id' => 'emailVerified'],
            'Iban' => ['id' => 'ibanNumber', 'required' => false],
            'BranchCode' => ['id' => 'branchCode', 'required' => false],
            'Swift' => ['id' => 'swift', 'required' => false],
            'BankName' => ['id' => 'bankName', 'required' => false],
            'BankNameText' => ['id' => 'bankNameText', 'required' => false],
            'DateOfBirth' => ['id' => 'dateOfBirth', 'required' => false],
            'affiliateSystem' => ['id' => 'affiliateSystem', 'required' => false],
            'affiliateId' => ['id' => 'affiliateId', 'required' => false],
            'affiliateClickId' => ['id' => 'affiliateClickId', 'required' => false],
            'RestrictCasinoBonuses' => ['id' => 'RestrictCasinoBonuses', 'required' => false],
            'RestrictSportBonuses' => ['id' => 'RestrictSportBonuses', 'required' => false],
            'state' => ['id' => 'stateCode'],
            'socketsData' => ['id' => 'socketsData', 'required' => false],
            'reg_ip' => ['id' => 'registrationIP'],
            'fromPublicAccount' => ['id' => 'fromPublicAccount'],
            'bonusIdPublicAccount' => ['id' => 'bonusIdPublicAccount'],
            'IDIssueDate' => ['id' => 'IDIssueDate', 'required' => false],
            'IDIssuer' => ['id' => 'IDIssuer', 'required' => false],
            'BankAddress' => ['id' => 'BankAddress', 'required' => false], # left for #246136
            'ValidationLevel' => ['id' => 'validationLevel'],
            'message' => ['id' => 'message'],
            'walletAddress' => ['id' => 'walletAddress'],
            'signature' => ['id' => 'signature'],
            'type' => ['id' => 'type', 'restrict' => 'response'],
            'cpf' => ['id' => 'cpf'],
        ];

        if (_cfg('requiredRegisterCheckbox')) {
            foreach (_cfg('requiredRegisterCheckbox') as $checkbox) {
                $fieldsMap[$checkbox] = ['id' => $checkbox];
                $fieldsMap[$checkbox . 'Date'] = ['id' => $checkbox . 'Date'];
            }
        }

        if ($isRequest) {
            foreach($fieldsMap as $fieldId => $fieldInfo) {
                if (!empty($fieldInfo['restrict']) && $fieldInfo['restrict'] != 'request' ) {
                    continue;
                }

                if (isset($data[$fieldInfo['id']])) {
                    if ( (!isset($fieldInfo['required']) || $fieldInfo['required']) && empty($data[$fieldInfo['id']])) {
                    //temporarily disabled due to errors in the registration
                    //return _('Field not found: ') . $fieldId;
                    } else {
                        $result[$fieldId] = $data[$fieldInfo['id']];
                    }
                } else if (isset($fieldInfo['default'])) {
                    $result[$fieldId] = $fieldInfo['default'];
                } else {
                    $result[$fieldId] = '';
                }
            }
        } else {
            foreach($fieldsMap as $fieldId => $fieldInfo) {
                if (!empty($fieldInfo['restrict']) && $fieldInfo['restrict'] != 'response' ) {
                    continue;
                }
                if (!empty($data[$fieldId])) {
                    $result[$fieldInfo['id']] = $data[$fieldId];
                } else if (isset($fieldInfo['default'])) {
                    $result[$fieldInfo['id']] = $fieldInfo['default'];
                } else {
                    $result[$fieldInfo['id']] = '';
                }
            }

            unset($result['password']);
            unset($result['passwordRepeat']);
            unset($result['newPasswordRepeat']);
            unset($result['currentPassword']);
            unset($result['smsCode']);
            unset($result['registrationPromoCode']);
        }

        return array_merge($result, User::getUserAgreement($data));
    }

    /**
     * @codeCoverageIgnore
     */
    public static function getUserAgreement(array $data, ?User $user = null): array
    {
        $user = ($user ?? new User());

        $additional_fields = json_decode(
            (!$user->userData || !isset($user->userData->additional_fields))
            ? '{}'
            : $user->userData->additional_fields);

        return [
            'sendSMS' => (bool) (isset($data['sendSMS']) && $data['sendSMS'] !== ''
                ? $data['sendSMS']
                : $additional_fields->sendSMS ?? true),
            'sendEmail' => (bool) (isset($data['sendEmail']) && $data['sendEmail'] !== ''
                ? $data['sendEmail']
                : $additional_fields->sendEmail ?? true)
        ];
    }

    public function checkUserWithdrawalStatus()
    {
        $return = array();
        $activeQueries = 0;
        $widthdrawAmount = 0;

        $answer = $this->fetchTransactions(array('status' => '0,50,55'));

        $breakdown = explode(',', $answer);
        if ($breakdown[0] == 1) {
            $answer = json_decode(substr($answer, 2));

            foreach ($answer as $f) {
                if (substr($f->Amount, 0,
                        1) == '-'
                ) { //Checking only statuses with negative amount as -***
                    ++$activeQueries;
                    $widthdrawAmount += number_format(substr($f->Amount, 1), 2, '.', '');
                }

                if ($activeQueries >= 3) {
                    //break;
                }
            }
        }

        $return['fundistWidthdrawQueries'] = $activeQueries;
        $return['fundistWidthdrawAmount'] = $widthdrawAmount;

        return (object)$return;
    }

    public function getWithdrawData($System)
    {
        if (!$this->isUser()) {
            return '0,' . _('Session is expired');
        }

        $url = '/WLCAccount/Withdraw/Check/?&Login=' . (int)$this->userData->id;// .'&Status=0,50';


        $transactionId = $this->getApiTID($url);

        $hash = md5('WLCAccount/Withdraw/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $this->userData->id . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'Password' => $this->userData->api_password,
            'TID' => $transactionId,
            'Hash' => $hash,
            'System' => $System,
            'UserIP' => System::getUserIP(),
        );

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);

        return $response;
    }
    /**
     * @codeCoverageIgnore
     */
    public function fetchTransactions($data = [])
    {
        if (!$this->isUser()) {
            return '0,' . _('Session is expired');
        }

        $url = '/WLCAccount/Payments/?&Login=' . (int)$this->userData->id;// .'&Status=0,50';

        if (isset($data['status'])) {
            $url .= '&Status=' . $data['status'];
        }

        $transactionId = $this->getApiTID($url);

        $hash = md5('WLCAccount/Payments/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $this->userData->id . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'Password' => $this->userData->api_password,
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
        );

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);

        return $response;
    }

    public function fetchLastSuccessfulPayment(string $endpoint = 'Deposit') {
        if (!$this->isUser()) {
            return '0,' . _('Session is expired');
        }

        $url = "/WLCAccount/LastSuccessful{$endpoint}/?&Login=" . (int)$this->userData->id;

        $transactionId = $this->getApiTID($url);

        $hash = md5("WLCAccount/LastSuccessful{$endpoint}/0.0.0.0/" . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $this->userData->id . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'Password' => $this->userData->api_password,
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
        );

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);

        return $response;
    }

    /**
     * @return array
     */
    public function getRomanianTax(): array
    {
        if (!$this->isUser()) {
            return '0,' . _('Session is expired');
        }

        $url = "/WLCAccount/Commissions/Romanian/getTax?&Login=" . (int)$this->userData->id;

        $transactionId = $this->getApiTID($url);

        $hash = md5("WLCAccount/Commissions/0.0.0.0/" . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $this->userData->id . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'Password' => $this->userData->api_password,
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
        );

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);
        $response = json_decode($response, true);

        return is_array($response) ? $response : [];
    }

    public function fetchSumDeposits() {
        if (!$this->isUser()) {
            return '0,' . _('Session is expired');
        }

        $url = '/WLCAccount/SumDeposits/?&Login=' . (int)$this->userData->id;

        $transactionId = $this->getApiTID($url);

        $hash = md5('WLCAccount/SumDeposits/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $this->userData->id . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'Password' => $this->userData->api_password,
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
        );

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);

        return $response;
    }

    /**
     * @codeCoverageIgnore
     */
    public function fetchBetsHistory(array $data = [])
    {
        if (!$this->isUser()) {
            return '0,' . _('Session is expired');
        }

        $url = '/WLCAccount/Bets/?&Login=' . (int)$this->userData->id;

        $betsHistoryId = $this->getApiTID($url);

        $hash = md5('WLCAccount/Bets/0.0.0.0/' . $betsHistoryId . '/' . _cfg('fundistApiKey') . '/' . $this->userData->id . '/' . _cfg('fundistApiPass'));
        $params = [
            'Password' => $this->userData->api_password,
            'TID' => $betsHistoryId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
            'startDate' => trim($data['startDate'] ?? ''),
            'endDate' => trim($data['endDate'] ?? ''),
            'openRounds' => $data['openRounds'],
        ];

        $url .= '&' . http_build_query($params);

        return $this->runFundistAPI($url);
    }

    /**
     * @param array $data
     * @return string
     * @throws \Exception
     */
    public function cancelDebet(array $data): string
    {
        return $this->makeDebetAction('Cancel', (int)($data['withdraw_id'] ?? 0));
    }

    /**
     * @param array $data
     * @return string
     * @throws \Exception
     */
    public function completeDebet(array $data): string
    {
        return $this->makeDebetAction('WithdrawComplete', (int)($data['withdraw_id'] ?? 0));
    }

    /**
     * @param array $data
     * @return string
     * @throws \Exception
     */
    public function cancelInvoice(array $data): string
    {
        return $this->handleInvoiceAction('InvoiceCancel', (int)($data['system_id'] ?? 0));
    }

    /**
     * @param string $action
     * @param int $IDWithdraw
     * @return string
     * @throws \Exception
     */
    private function makeDebetAction(string $action, int $IDWithdraw): string
    {
        if (!$this->isUser()) {
            return '0,' . _('Session is expired');
        }

        if (!$IDWithdraw) {
            return '0,' . _('Withdrawal ID not specified');
        }

        $url = '/WLCAccount/Payments/?&Login=' . (int)$this->userData->id;
        $url .= '&Action=' . $action . '&ID=' . $IDWithdraw . '&';

        $transactionId = $this->getApiTID($url);

        $hash = md5('WLCAccount/Payments/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $this->userData->id . '/' . _cfg('fundistApiPass'));

        $params = [
            'Password' => $this->userData->api_password,
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
        ];

        $url .= '&' . http_build_query($params);

        return $this->runFundistAPI($url);
    }

    /**
     * @param string $action
     * @param int $IDInvoice
     * @return string
     * @throws \Exception
     */
    private function handleInvoiceAction(string $action, int $IDSystem): string
    {
        if (!$this->isUser()) {
            return '0,' . _('Session is expired');
        }

        if (!$IDSystem) {
            return '0,' . _('Invoice ID not specified');
        }

        $url = '/WLCAccount/Invoices/?&Login=' . (int)$this->userData->id;
        $url .= '&Action=' . $action . '&IDSystem=' . $IDSystem . '&';

        $transactionId = $this->getApiTID($url);

        $hash = md5('WLCAccount/Invoices/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey')
                    . '/' . $this->userData->id . '/' . $IDSystem . '/' . _cfg('fundistApiPass'));

        $params = [
            'Password' => $this->userData->api_password,
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
        ];

        $url .= '&' . http_build_query($params);

        return $this->runFundistAPI($url);
    }

    /**
     * @codeCoverageIgnore
     */
    public function debet($data)
    {
        if (!$this->isUser()) {
            return '0,' . _('Session is expired');
        }

        if (!isset($data['system']) && !$data['system']) {
            return '0,' . _('System ID not specified');
        }

        $data['amount'] = number_format($data['amount'], 2, '.', '');

        if ($data['amount'] <= 0) {
            return '0,' . _('set_amount');
        }

        $url = '/WLCAccount/Withdraw/?&Login=' . (int)$this->userData->id;

        if (!isset($data['paymentTypeID']) || !$data['paymentTypeID']) {
            $data['paymentTypeID'] = 0;
        }

		if (isset($data['additional']) && isset($data['additional']['purse']) ) {
			$data['purse'] = $data['additional']['purse'];
		}

        //Hotfix for 15,Wrong hash authorization error. Remove "+" sign for Purse because it's breaking everything
        $data['purse'] = str_replace('+', '', $data['purse'] ?? '');

        $url .=
            '&Amount=' . $data['amount'] .
            '&Currency=' . $this->userData->currency .
            '&System=' . $data['system'] .
            '&PaymentTypeID=' . $data['paymentTypeID'] .
            '&Purse=' . $data['purse'] .
            '&Wallet=' . $data['wallet'] .
            '&WalletCurrency=' . $data['walletCurrency'] .
            (_cfg('spotOptionId') && _cfg('spotOptionDirectPayment') ? '&Merchant=' . _cfg('spotOptionId') : null);

        if (!empty($data['additional']['payer'])) {
            $url .= '&Payer=' . urlencode($data['additional']['payer']);
        }

        $add = [];
        if (isset($data['additional'])) {
            $skippedKeys = ['payer', 'purse'];

            foreach ($data['additional'] as $k => $v) {
                if (!in_array($k, $skippedKeys)) {
                    if ($k == "cardHolder") {
                        $v = urlencode($v);
                    }
                    $add['Additional[' . $k . ']'] = $v;
                }
            }
        }

        $transactionId = $this->getApiTID($url);
        $hash = md5('WLCAccount/Withdraw/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $data['amount'] . '/' . $this->userData->id . '/' . $this->userData->currency . '/' . $data['system'] . '/' . $data['paymentTypeID'] . '/' . $data['purse'] . '/' . _cfg('fundistApiPass'));
        $params = [
            'Password' => $this->userData->api_password,
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
            'GetID' => $data['GetID'],
        ];
        $params = array_merge($params, $add);

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);

        return $response;
    }

    /**
     * @codeCoverageIgnore
     */
    public function credit($data)
    {
        if (!$this->isUser()) {
            return '0,' . _('Session is expired');
        }

        if (!isset($data['system']) && !$data['system']) {
            return '0,' . _('System ID not specified');
        }

        if (!empty($data['amount'])) {
            $data['amount'] = number_format($data['amount'], 2, '.', '');
            if ($data['amount'] <= 0) {
                return '0,' . _('set_amount');
            }
        }

        $url = '/WLCAccount/Credit/?&Login=' . (int)$this->userData->id;

        $add = [];
        if (isset($data['additional'])) {
            foreach ($data['additional'] as $k => $v) {
                $add[] = 'Additional[' . $k . ']=' . urlencode($v);
            }
        }

        $bonus = !empty($data['BonusID']) ? '&BonusID=' . $data['BonusID'] : '';
        $bonus .= !empty($data['BonusCode']) ? '&BonusCode=' . $data['BonusCode'] : '';

        $redirect= '';
        if (_cfg('disableRedirect') !== true) {
            $redirectDomain = _cfg('site');
            if (!empty($_SERVER['HTTP_HOST'])) {
                if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']) {
                    $redirectDomain= 'https';
                } else {
                    $redirectDomain= 'http';
                }

                $redirectDomain.= '://' . $_SERVER['HTTP_HOST'];
            }

            Db::query('INSERT INTO `redirects` SET ' .
                '`user_id` = "' . Db::escape($this->userData->id) . '", ' .
                '`domain` = "' . Db::escape($redirectDomain) . '"'
            );

            $redirect = '&RedirectId=' . Db::lastId();
        }

        $url .=
            '&Amount=' . $data['amount'] .
            '&Currency=' . $this->userData->currency .
            '&System=' . $data['system'] .
            '&Wallet=' . $data['wallet'] .
            '&WalletCurrency=' . $data['walletCurrency'] .
            '&Version=' . $data['version']
            . (_cfg('spotOptionId') && _cfg('spotOptionDirectPayment') ? '&Merchant=' . _cfg('spotOptionId') : null)
            . $bonus . $redirect . '&'
            . implode('&',$add);

        $transactionId = $this->getApiTID($url);

        $hash = md5('WLCAccount/Credit/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $data['amount'] . '/' . $this->userData->id . '/' . $this->userData->currency . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'Password' => $this->userData->api_password,
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
        );

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);

        return $response;
    }

    public function fetchFundistUserBalance($user)
    {
        if (!$this->isUser()) {
            return 0;
        }

        if (isset($_SESSION['just_registered']) && $_SESSION['just_registered'] == 1) {
            unset($_SESSION['just_registered']);

            return '0.00';
        }

        if (_cfg('spotOptionId') != 0) {
            //Getting user balance specially for spotoption
            $url = '/Balance/Get/?&Login=' . (int)$user->id . '&System=' . _cfg('spotOptionId');

            $transactionId = $this->getApiTID($url);

            $hash = md5('Balance/Get/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('spotOptionId') . '/' . $user->id . '/' . _cfg('fundistApiPass'));
        } else {
            //Getting user balance in fundist table, required only on payments page
            $url = '/WLCAccount/Get/?&Login=' . (int)$user->id;

            $transactionId = $this->getApiTID($url);

            $hash = md5('WLCAccount/Get/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $user->id . '/' . _cfg('fundistApiPass'));
        }

        $params = Array(
            'Password' => $user->api_password,
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
        );

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);

        $brakedown = explode(',', $response);

        if ($brakedown[0] != 1) {
            //Showing error
            return $brakedown;
        }

        return $brakedown[1]; //Returning only float number of balance amount
    }

    /**
     * @param $amount
     * @param $currency
     * @param $systemId
     * @param $operation string 'Credit' or 'Withdraw'
     * @return bool
     * @throws \Exception
     */
    protected function changeMerchantBalance($amount, $currency, $systemId, $operation)
    {
        if ($amount <= 0) {
            throw new \Exception('Amount should be positive');
        }

        $login = (int)$this->userData->id;

        $params = [
            'Login' => $login,
            'System' => $systemId,
            'Amount' => $amount,
            'Currency' => $currency,
        ];

        $url = '/WLCAccount/Merchant/' . $operation . '/?&' . http_build_query($params);

        $transactionId = $this->getApiTID($url);

        $hash = md5(
            'WLCAccount/Merchant/' . $operation . '/0.0.0.0/' .
            $transactionId . '/' .
            _cfg('fundistApiKey') . '/' .
            $systemId . '/' .
            $amount . '/' .
            $this->userData->id . '/' .
            $currency . '/' .
            _cfg('fundistApiPass')
        );

        $params = [
            'TID' => $transactionId,
            'Hash' => $hash,
        ];

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);
        $response = explode(',', $response, 2);

        if ($response[0] !== '1') {
            throw new \Exception($response[1], 400);
        }

        return true;
    }

    public function merchantCredit($amount, $currency, $systemId)
    {
        return $this->changeMerchantBalance($amount, $currency, $systemId, 'Credit');
    }

    public function merchantWithdraw($amount, $currency, $systemId)
    {
        return $this->changeMerchantBalance($amount, $currency, $systemId, 'Withdraw');
    }

    //Check if user is entered website with affiliate ID parameter
    public function checkAffiliate()
    {
        $cooldownCoef =  empty(_cfg('referralCookieSaveDays')) ? 1 : _cfg('referralCookieSaveDays');
        if (isset($_GET['ref']) && (int)$_GET['ref'] != 0) {
            $cookieCooldown = $cooldownCoef * 86400; //86400 = 1 day (24*60*60) * cookie ref config, for example 30 days = 2592000
            setcookie('ref', $_GET['ref'], time() + $cookieCooldown, '/', _cfg('cookieDomain'));
        }
        if (!empty($_GET['pid'])) { // Goldtime affiliate data
            $cookieCooldown = $cooldownCoef * 86400; //86400 = 1 day (24*60*60) * cookie ref config, for example 30 days = 2592000
            setcookie('pid', $_GET['pid'], time() + $cookieCooldown, '/', _cfg('cookieDomain'));
            if (!empty($_GET['uid'])) {
                setcookie('uid', $_GET['uid'], time() + $cookieCooldown, '/', _cfg('cookieDomain'));
            }
            if (!empty($_GET['cid'])) {
                setcookie('cid', $_GET['cid'], time() + $cookieCooldown, '/', _cfg('cookieDomain'));
            }
            if (!empty($_GET['lid'])) {
                setcookie('lid', $_GET['lid'], time() + $cookieCooldown, '/', _cfg('cookieDomain'));
            }
        }

        return true;
    }

    //Function to restore user password.
    //Checking email and code from input form if those are correct, setting up new password.
    public function restorePassword($data, $password, $code = null, $relogin = true, $fastPhoneRegistration = false, $loginByPhone = false)
    {
        if ($loginByPhone) {
            if (substr($data['phoneCode'], 0, 1) !== '+') {
                $data['phoneCode'] = '+' . $data['phoneCode'];
            }

            $where = 'WHERE `phone2` = "' . Db::escape($data['phoneNumber']) . '" AND `phone1` = "' . Db::escape($data['phoneCode']) . '" ';
        } else {
            $where = $fastPhoneRegistration ? 'WHERE `phone2` = "' . Db::escape($data['phoneNumber']) . '" AND `phone1` = "' . Db::escape($data['phoneCode']) . '" '
                : 'WHERE `email` = "' . Db::escape($data) . '" ';
        }

        $row = Db::fetchRow(
            'SELECT `id`, `email` ' .
            'FROM `users` ' .
            $where .
            'LIMIT 1'
        );

        if ($row === false && $fastPhoneRegistration) {
            return '0;<p>' . _('Invalid phone number') . '</p>';
        }

        if ($row === false) {
            return '0;<p>' . _('Incorrect email, please try again from the start') . '</p>';
        }

        Db::query(
            'UPDATE `users` SET ' .
            '`password` = "' . Db::Escape(self::passwordHash($password)) . '",' .
            '`additional_fields` = REPLACE(`additional_fields`,\'"change_pass":1,\',"") ' .
            'WHERE `id` = ' . (int)$row->id
        );

        if ($code) {
            $this->deleteRestoreCode($code);
        }

        if ($loginByPhone) {
	        $loginData = array(
	            'phoneCode' => $data['phoneCode'],
                'phoneNumber' => $data['phoneNumber'],
	            'relogin' => 1, // ignore password check, it is just changed
	        );

	        return $this->login($loginData, true, self::LOGIN_TYPE_SMS) . ';1'; // returns 1 if success
        }

        if ($relogin && !$fastPhoneRegistration) {
	        $loginData = array(
	            'login' => $row->email,
	            'relogin' => 1, // ignore password check, it is just changed
	        );

	        return $this->login($loginData, true, self::LOGIN_TYPE_DEFAULT) . ';1'; // returns 1 if success
        }

        if (!User::isAuthenticated() && $fastPhoneRegistration) {
            $query = [];
            $query['action'] = 'reset';
            $query['message'] = _('new_password') . $password;
            $resource = new SmsProviderResource();
            $resource->post($data, $query);
        }
        return '1;1';
    }

    /**
     * Change password using email reset
     * @param $data
     * @return int
     */
    public function resetPassword($data)
    {
        $res = Db::query(
            'UPDATE `users` SET ' .
            '`password` = "' . Db::Escape(self::passwordHash($data['password'])) . '",' .
            '`additional_fields` = REPLACE(`additional_fields`,\'"change_pass":1,\',"") ' .
            'WHERE `id` = ' . $data['id']
        );
        return $res ? 1 : 0;
    }

    public function checkFieldExists($field, $value)
    {
        if(empty($field) || empty($value)) {
            return false;
        }

        $user = Db::fetchRow('SELECT email, first_name, last_name FROM users WHERE ' . Db::escape($field) . ' = "' . Db::escape($value) . '"');
        if ($user === false) {
            return true;
        }

        return false;
    }

    /**
     * Reset password via email
     * @param $data
     * @return int|string
     */
    public function emailResetPassword($data)
    {
        if (empty($data['email'])) {
            return '0;<p>' . _('Email is empty') . '</p>';
        }
        $user = Db::fetchRow('SELECT email, first_name, last_name, id, api_password FROM users WHERE email = "' . Db::escape($data['email']) . '"');
        $url = '/WLCAccount/SendMail/PasswordReset?&Login=' . (int)$user->id;
        $transactionId = $this->getApiTID($url);

        $hash = md5('WLCAccount/SendMail/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $user->id . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'Password' => $user->api_password,
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
        );

        $url .= '&' . http_build_query($params);
        $response = $this->runFundistAPI($url);

        $brakedown = explode(',', $response);
        if ($brakedown[0] != 1) {
            return '0;'. $brakedown[1];
        }

        return 1;
    }


    //Function required to restore password.
    //Checking if email does exist in the database
    public function checkIfEmailExist($data)
    {
        if (empty($data['email'])) {
            return '0;<p>' . _('Email is empty') . '</p>';
        }

        $user = Db::fetchRow('SELECT email, first_name, last_name, id, api_password FROM users WHERE email = "' . Db::escape($data['email']) . '"');
        if ($user === false) {
            return '0;<p>' . _('Error, account with this email does not exist.') . '</p>';
        }

        $redis = System::redis();
        $restoreData = [
            'email' => $user->email,
            'code' => sha1(time().'-'.rand(0, 9999999)),
            'time' => time()
        ];


        $restoreCodeStatus = $redis->set('user_pass_restore_'.$restoreData['code'], json_encode($restoreData), 60 * 30);
        if (!$restoreCodeStatus) {
            return '0;' . _('Unable generate temporary restore user code');
        }


        $redirectUrl = !empty($data['redirectUrl']) ? $data['redirectUrl'] . '&code=' . $restoreData['code'] : _cfg('href') . '/?restoreCode=' . $restoreData['code'];
        $supportLink = !empty($data['supportLink']) ? _cfg('site') . '/' . $data['supportLink'] : _cfg('site');

        if(_cfg('useFundistTemplate') == 1) {
            $url = '/WLCAccount/SendMail/PasswordRestore?&Login=' . (int)$user->id;

            $transactionId = $this->getApiTID($url);

            $hash = md5('WLCAccount/SendMail/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $user->id . '/' . _cfg('fundistApiPass'));
            $params = Array(
                'Password' => $user->api_password,
                'Url' => $redirectUrl,
                'TID' => $transactionId,
                'Hash' => $hash,
                'UserIP' => System::getUserIP(),
            );

            $url .= '&' . http_build_query($params);

            $response = $this->runFundistAPI($url);

            $brakedown = explode(',', $response);

            if ($brakedown[0] != 1) {
                return '0;'. $brakedown[1];
            }
            return '1;' . _('Email sent, recovery link will be available for 30 minutes');
        }

        $code = $restoreData['code'];
        if (_cfg('resetPassByLink') == 1) {
            $code = '<a href="' . $redirectUrl . '">' . $redirectUrl . '</a>';
        }

        $templateName = 'password-restore';
        $templateContext = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'code' => $code,
            'site_url' => _cfg('site'),
            'site_name' => _cfg('websiteName'),
            'site_support_link' => $supportLink
        ];

        $template = new Template();
        $msg = $template->getMailTemplate($templateName, $templateContext);

        $msgReplaceKeys = [];
        $msgReplaceVals = [];
        foreach($templateContext as $k => $v) {
            $msgReplaceKeys[] = '%'.$k.'%';
            $msgReplaceVals[] = $v;
        }

        $msg = str_replace( $msgReplaceKeys, $msgReplaceVals, $msg );
        if (Email::send($user->email, _('Password recovery'), $msg) !== true) {
            return '0;<p>' . _('Impossible to send email') . '</p>';
        }
        return '1;' . _('Email sent, recovery link will be available for 30 minutes');
    }

    public function checkRestoreCode($code) {
        $redis = System::redis();
        $restoreData = $redis->get('user_pass_restore_'.$code);
        if (!$restoreData) {
            return false;
        }
        return json_decode($restoreData, true);
    }

    public function deleteRestoreCode($code) {
        $redis = System::redis();
        return $restoreData = $redis->delete('user_pass_restore_'.$code);
    }

    public function assertAuth(array $data, bool $isApiCall = false, string $key = 'currentPassword')
    {
        if ($isApiCall) {
            return true;
        }

        if (!$this->isUser() || !$this->userData) {
            return '0;' . json_encode(['sessionExpired' => _('Session expired')], JSON_UNESCAPED_UNICODE);
        }

        if (empty($data['currentPassword'])) {
            return '0;' . json_encode([$key => _('current_password_empty')], JSON_UNESCAPED_UNICODE);
        }

        $passwordCheck = self::verifyPassword($data['currentPassword'], $this->userData->password);

        if ($passwordCheck === false) {
            PrometheusKeys::getInstance()->AUTH_INCORRECT_PASSWORD->store();
            return '0;' . json_encode([$key => _('Current password is incorrect')], JSON_UNESCAPED_UNICODE);
        }

        return '1';
    }

    /**
     * Updates additional fields
     *
     * @param array $data
     * @param array $additionalParams
     * @return string|bool
     * @throws \Exception
     */
    public function profileAdditionalUpdate(array $data, array $additionalParams = [])
    {
        $error = [];

        if (!$this->isUser() || !$this->userData) { // TODO: Remove this legacy check
            return '0;' . json_encode(['sessionExpired' => _('Session expired')], JSON_UNESCAPED_UNICODE);
        }

        $row = Db::fetchRow(
            'SELECT * FROM `users` AS `u` ' .
            'LEFT JOIN `users_data` AS `ud` ON `u`.`id` = `ud`.`user_id` ' .
            'WHERE `u`.`id` = ' . (int)$this->userData->id . ' ' .
            'LIMIT 1 '
        );

        foreach (
            ['first_name', 'last_name', 'sex', 'birth_day', 'birth_month', 'birth_year', 'login', 'email'] as $field
        ) {
            if (empty($data[$field]) && is_object($row) && !empty($row->$field)) {
                $data[$field] = $row->$field;
            } else {
                $data[$field] = !empty($data[$field]) ? $data[$field] : '';
            }
        }

        if (!_cfg('allowPartialUpdate')) {
            if (empty($data['birth_day']) || !trim($data['birth_day'])) {
                $error['day'] = _('day_not_set');
            }
            if (empty($data['birth_month']) || !trim($data['birth_month'])) {
                $error['month'] = _('month_not_set');
            }
            if (empty($data['birth_year']) || !trim($data['birth_year'])) {
                $error['year'] = _('year_not_set');
            }
            if (empty($data['country'])) {
                $error['country'] = _('Country is not picked');
            }
            if (empty($data['sex']) || !in_array($data['sex'], ['m', 'f'])) {
                $error['sex'] = _('Gender is not picked');
            }
        }

        $this->checkLogin($data['login'], $error);

        if (_cfg('updateUniquePhone') && (isset($data['phone1']) && isset($data['phone2'])) &&
            ($data['phone1'] != $this->userData->phone1 || $data['phone2'] != $this->userData->phone2)) {
            $phone = [];
            $phone['pre_phone'] = $data['phone1'];
            $phone['main_phone'] = $data['phone2'];
            $result = $this->checkUniquePhone($phone);
            if (!$result) {
                $error['uniquePhone'] = _('Phone is already registered');
            }
        }

        if (!empty(trim($data['email']))) {
            $this->checkEmailOnUpdate($data['email'], $error);
        }

        if (!empty($data['currentPassword']) && ($errors = $this->assertAuth($data)) != 1) { // TODO: Redirect the update of the payment system fields to another routing
            return $errors;
        } else if (!empty($error)) {
            return '0;' . json_encode($error, JSON_UNESCAPED_UNICODE);
        }

        $additionalFields = $this->userData !== false
            ? json_decode($this->userData->additional_fields, true) ?? []
            : [];
        if (isset($additionalFields['type']) && $additionalFields['type'] === self::LOGIN_TYPE_METAMASK) {
            $validator = new EtheriumSignatureValidatorRules();
            $validatorResult = $validator->validate($data);

            if (!$validatorResult['result']) {
                return '0;' . json_encode($validatorResult['errors'], JSON_UNESCAPED_UNICODE);
            }
        }

        $fieldsToUpdate = [];
        foreach (['first_name', 'last_name', 'country', 'phone1', 'phone2', 'login', 'email'] as $key) {
            if (!empty($data[$key])) {
                $fieldsToUpdate[] = sprintf("`%s` = '%s'", $key, Db::escape($data[$key]));
            }
        }
        if (!empty($fieldsToUpdate)) {
            Db::query(sprintf("UPDATE `users` SET %s WHERE `id` = %d", implode(', ', $fieldsToUpdate), (int)$this->userData->id));
        }

        Db::query('UPDATE `users_data` SET ' .
            '`sex` = "' . (empty($data['sex']) ? '' : Db::escape($data['sex'])) . '", ' .
            '`birth_day` = ' . (int)$data['birth_day'] . ', ' .
            '`birth_month` = ' . (int)$data['birth_month'] . ', ' .
            '`birth_year` = ' . (int)$data['birth_year'] . ' ' .
            'WHERE `user_id` = ' . (int)$this->userData->id
        );

        $data = (new FixAdditionalFieldsUnicode($data))->run();

        $newAdditionalFields = [];
        if (!empty($data['ext_profile'])) {
            $newAdditionalFields['ext_profile'] = $data['ext_profile'];
        }

        if (!empty($data['middle_name'])) {
            $newAdditionalFields['ext_profile']['middleName']  = $data['middle_name'];
        }

        foreach (['postal_code', 'city', 'address', 'BankName', 'BranchCode', 'Iban', 'Swift', 'IDNumber', 'cpf'] as $field) {
            if (!empty($data[$field])) {
                $newAdditionalFields[$field] = $data[$field];
            }
        }

        foreach (['state'] as $field) {
            if (array_key_exists($field, $data)) {
                $newAdditionalFields[$field] = $data[$field];
            }
        }

        foreach (['sendSMS', 'sendEmail'] as $field) {
            if (isset($data[$field])) {
                $newAdditionalFields[$field] = (bool) $data[$field];
            }
        }

        if (_cfg('requiredRegisterCheckbox')) {
            foreach (_cfg('requiredRegisterCheckbox') as $checkbox) {
                $newAdditionalFields[$checkbox] = $data[$checkbox] ?? $additionalFields[$checkbox];
                $newAdditionalFields[$checkbox . 'Date'] = $data[$checkbox] ? date('Y-m-d H:i:s') : $additionalFields[$checkbox . 'Date'];
            }
        }

        if (isset($data['avatar_id'])) {
            $newAdditionalFields['avatar_id'] = $data['avatar_id'];
        }

        $additionalFields = $this->profileAdditionalFieldsUpdate($newAdditionalFields, $this->userData->id);

        //Updating user in Fundist
        $url = '/User/Update/?&Login=' . (int)$this->userData->id;

        $system = System::getInstance();
        $transactionId = $system->getApiTID($url);

        $hash = md5('User/Update/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $this->userData->id . '/' . $this->userData->api_password . '/' . $this->userData->currency . '/' . _cfg('fundistApiPass'));
        $params = [
            'Password' => $this->userData->api_password,
            'Currency' => $this->userData->currency,
            'Name' => Db::escape($data['first_name']),
            'LastName' => Db::escape($data['last_name']),
            'MiddleName' => !empty($data['middle_name']) ? $data['middle_name'] : null,
            'Phone' => (!empty($data['phone1']) && !empty($data['phone2']) ? Db::escape($data['phone1'] . '-' . $data['phone2']) : null),
            'Country' => empty($data['country']) ? null : Db::escape($data['country']),
            'City' => (!empty($data['city']) ? Db::escape($data['city']) : null),
            'Address' => (!empty($data['address']) ? Db::escape($data['address']) : null),
            'PostalCode' => (!empty($data['postal_code']) ? Db::escape($data['postal_code']) : null),
            'Email' => !empty($data['email']) ? Db::escape($data['email']) : $row->email,
            'Gender' => !array_key_exists('sex', $data) ? null : $this->getFundistValue('Gender', Db::escape($data['sex'])),
            'AlternativePhone' => (isset($row->pre_alternate_phone) && isset($data->main_alternate_phone) ? $row->pre_alternate_phone . '-' . $row->main_alternate_phone : null),
            'DateOfBirth' =>  empty($data['birth_year']) ? null : sprintf("%04d-%02d-%02d", (int)$data['birth_year'],
                (int)$data['birth_month'], (int)$data['birth_day']),
            'Language' => _cfg('language'),
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
            'AdditionalUserIP' => json_encode(System::getUserIP(true)),
            'CustomParams' => !empty($additionalFields['ext_profile']['customParams']) ? substr((string) $additionalFields['ext_profile']['customParams'], 0, 250) : '',
            'EmailAgree' => (int) $additionalFields['sendEmail'],
            'SmsAgree' => (int) $additionalFields['sendSMS'],
            'BranchCode' => (!empty($data['BranchCode']) ? Db::escape($data['BranchCode']) : null),
            'BankName' => (!empty($data['BankName']) ? Db::escape($data['BankName']) : null),
            'Iban' => (!empty($data['Iban']) ? Db::escape($data['Iban']) : null),
            'Swift' => (!empty($data['Swift']) ? Db::escape($data['Swift']) : null),
            'IDNumber' => (!empty($data['IDNumber']) ? Db::escape($data['IDNumber']) : null),
            'RestrictCasinoBonuses' => (int)!empty($data['RestrictCasinoBonuses']),
            'RestrictSportBonuses' => (int)!empty($data['RestrictSportBonuses']),
            'State' => empty($data['state']) ? null : Db::escape($data['state']),
            'VerificationSessionID' => (!empty($data['VerificationSessionID']) ? Db::escape($data['VerificationSessionID']) : null),
            'IDIssueDate' => !empty($data['IDIssueDate']) ? Db::escape($data['IDIssueDate']) : '',
            'IDIssuer' => !empty($data['IDIssuer']) ? Db::escape($data['IDIssuer']) : '',
            'BankAddress' => !empty($data['BankAddress']) ? Db::escape($data['BankAddress']) : '', # left for #246136
            'ExtLogin' => !empty($data['login']) ? Db::escape($data['login']) : '',
            'PEP' => isset($data['ext_profile']['pep'])
            && $data['ext_profile']['pep'] == 'true' ? 1 : 0,
            'CPF' => !empty($additionalFields['cpf']) ? str_replace(['.', '-', '/'], '', $additionalFields['cpf']) : null,
        ];

        $url .= '&' . http_build_query($params);

        $response = $system->runFundistAPI($url);

        $isAfterDeposit = (isset($_GET['isAfterDepositWithdraw']) && $_GET['isAfterDepositWithdraw'])
                || (isset($additionalParams['isAfterDepositWithdraw']) && $additionalParams['isAfterDepositWithdraw']);
        if (_cfg('useFundistTemplate') == 1 && !$isAfterDeposit && $this->isNeedProfileChangeMail($data, (array)$this->userData)) {
            $this->sendMailAfterUserUpdate($this->userData->id, $this->userData->api_password, $data, ($this->userData->email != $row->email));
        }

        $brakedown = explode(',', $response, 2);
        $brakedown[1] = $brakedown[1] ?? $response;

        $translates = [
            'Wrong currency' => _('Wrong currency'),
            'Invalid Country' => _('Country code is invalid'),
            'Invalid country state' => _('Invalid country state'),
            'Restricted country' => _('Not allowed player country'),
            'Country required' => _('Country code is empty'),
            'User currency could not be changed' => _('User currency could not be changed'),
            'Date of birth must not be earlier than 1900-01-01 and user must be at least 18 years old'
                => _('Date of birth must not be earlier than 1900-01-01 and user must be at least 18 years old'),
        ];

        if (
            $brakedown[0] != 1
            && array_key_exists($brakedown[1], $translates)
        ) {
            return '0;' . $translates[$brakedown[1]];
        }

        return true;
    }

    /**
     * @param array $new
     * @param array $old
     * @return bool
     */
    public function isNeedProfileChangeMail(array $new, array $old): bool
    {
        $mailTriggeredFields = _cfg('extProfileFieldsChangeMail') ?: ['pep', 'nick',];
        $fieldsMap = [
            'firstName' => 'first_name',
            'lastName' => 'last_name',
            'pre_phone' => 'phone1',
            'main_phone' => 'phone2',
        ];

        $fieldsSkip = [
            'repeatPassword',
            'repeat_password',
            'currentPassword',
            'affiliateClickId',
            'sendSMS',
            'sendEmail',
        ];

        $old += isset($old['additional_fields'])
            ? json_decode($old['additional_fields'], true) ?? []
            : [];
        unset($old['additional_fields']);

        if (isset($new['ext_profile'])) {
            foreach ($new['ext_profile'] as $k => $v) {
                if (in_array($k, $mailTriggeredFields) && (!isset($old['ext_profile'][$k]) || $v != $old['ext_profile'][$k])) {
                    return true;
                }
            }
            unset($new['ext_profile']);
        }

        foreach ($new as $k => $v) {
            if (in_array($k, $fieldsSkip)) {
                continue;
            }

            $k = $fieldsMap[$k] ?? $k;
            if (isset($old[$k])) {
                $oldVal = !in_array($k, ['birth_day', 'birth_month', 'birth_year']) ? $old[$k] : (int)$old[$k];
                if ($v != $oldVal) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Updates user language in fundist
     */
    public function profileUpdateLanguage($language) {
        $url = '/User/Update/?&Login=' . (int)$this->userData->id;

        $transactionId = $this->getApiTID($url);

        $hash = md5('User/Update/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $this->userData->id . '/' . $this->userData->api_password . '/' . $this->userData->currency . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'Password' => $this->userData->api_password,
            'Currency' => $this->userData->currency,
            'Country' => $this->userData->country,
            'Language' => $language,
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
            'AdditionalUserIP' => json_encode(System::getUserIP(true)),
        );

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);

        $brakedown = explode(',', $response);

        if ($brakedown[0] != 1) {
            return $response;
        }

        return true;
    }

    /**
     * Updates user 2FAGoogle in fundist
     */
    public function profileUpdateEnable2FAGoogle(int $status, int $statusConfigured) {
        $url = '/User/Update/?&Login=' . (int)$this->userData->id;

        $transactionId = $this->getApiTID($url);

        $hash = md5('User/Update/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $this->userData->id . '/' . $this->userData->api_password . '/' . $this->userData->currency . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'Password' => $this->userData->api_password,
            'Currency' => $this->userData->currency,
            'Country' => $this->userData->country,
            'TID' => $transactionId,
            'Hash' => $hash,
            'Enable2FAGoogle' => $status,
            'Configured2FAGoogle' => $statusConfigured
        );

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);

        $brakedown = explode(',', $response);

        if ($brakedown[0] != 1) {
            return $response;
        }

        return true;
    }

    /**
     * Updates additional_fields in users table with some of the provided params
     *
     * @param array $data Received data
     * @param integer $uid User id
     */
    public function profileAdditionalFieldsUpdate($data, $uid) {
        $row = Db::fetchRow('SELECT `additional_fields` FROM `users` WHERE `id` = '.(int)$uid);
        if (!$row) {
            return;
        }

        $additional_fields = json_decode($row->additional_fields,true);
        if (!$additional_fields || !is_array($additional_fields)) {
            $additional_fields = [];
        }

        if (!empty($data['ext_profile'])) {
            if (empty($additional_fields['ext_profile'])) {
                $additional_fields['ext_profile'] = [];
            }
            $additional_fields['ext_profile'] = array_merge($additional_fields['ext_profile'], $data['ext_profile']);
            unset($data['ext_profile']);
        }

        $additional_fields = array_merge($additional_fields, $data);
        $additional_fields = $this->clearAdditionalFields($additional_fields);

        // @codeCoverageIgnoreStart
        if (isset($additional_fields['sendEmail'])) {
            $additional_fields['dontSendEmail'] = !$additional_fields['sendEmail'];
        }

        if (isset($additional_fields['sendSMS'])) {
            $additional_fields['dontSendSms'] = !$additional_fields['sendSMS'];
        }
        // @codeCoverageIgnoreEnd

        Db::query('UPDATE `users` SET ' .
            'additional_fields = "' . Db::escape(json_encode($additional_fields)) . '"' .
            'WHERE `id` = '.(int)$uid
        );
        return $additional_fields;
    }

    /**
     * Unset some keys from additional fields
     *
     * @param array $additional_fields
     * @return array
     */
    private function clearAdditionalFields($additional_fields, $event = '')
    {
        $userDataFields = [
            'id','first_name', 'last_name', 'firstName','lastName','email','login',
            'password','userApiPass', 'pre_phone','main_phone',
            'sex','birth_day','birth_month','birth_year','sms_code',
            'country','control',
            'repeatEmail','currentPassword','repeatPassword','repeat_password', 'IDIssueDate', 'IDIssuer', 'PlaceOfBirth', 'CityOfRegistration', 'AddressOfRegistration', 'IndexOfRegistration',
            'i_agree', 'i_agree_2', 'pincode','original_password'
        ];

        switch ($event) {
            case 'registration':
                $userDataFields = [
                    'IDNumber', 'IDIssueDate', 'IDIssuer', 'PlaceOfBirth',
                    'CityOfRegistration', 'AddressOfRegistration', 'IndexOfRegistration',
                    'i_agree', 'i_agree_2', 'original_password'
                ];
                break;
        }

        if (_cfg('requiredRegisterCheckbox')) {
            foreach (_cfg('requiredRegisterCheckbox') as $checkbox) {
                unset($userDataFields[$checkbox], $userDataFields[$checkbox . 'Date']);
            }
        }

        foreach($additional_fields as $fieldId => $fieldValue) {
            if (in_array($fieldId, $userDataFields)) {
                unset($additional_fields[$fieldId]);
            }
        }
        return $additional_fields;
    }

    /**
     * @throws ApiException
     */
    public function turnYourselfOff(?string $dateTo = null): bool
    {
        $timeTo = strtotime($dateTo);

        if ($timeTo !== false && $timeTo <= time()) {
            throw new ApiException('Wrong dateTo param', 400, null, [], 400);
        }

        Db::query('START TRANSACTION');

        Db::query('UPDATE `users` SET `status` = 0 WHERE email = "' . Db::escape($this->userData->email) . '"');

        $url = sprintf('/User/Disable/?&Login=%d&timeTo=%s', (int) $this->userData->id, $timeTo);

        $transactionId = $this->getApiTID($url);

        $hash = md5(implode('/', [
            'User',
            'Disable',
            '0.0.0.0',
            $transactionId,
            _cfg('fundistApiKey'),
            (int) $this->userData->id,
            _cfg('fundistApiPass')
        ]));
        $params = [
            'Status' => 'Disable',
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP()
        ];

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);
        list($id, $message) = explode(',', $response);

        if ($id == '1') {
            Db::query('COMMIT');

            return true;
        } else {
            // @codeCoverageIgnoreStart
            Logger::log(sprintf('Cannot disable the user #%u, funcore response [%s]: %s', $this->userData->id, $id, $message));

            Db::query('ROLLBACK');
            throw new ApiException($message, 404, null, [], $id);
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Method to update profile on website, fundist and spotoption
     * $isApiCall true if request is comming from Api (can have additional fields like AffiliateId for update)
     *
     * @param array $data
     * @param bool $isApiCall
     *
     * @return bool|string
     */
    public function profileUpdate($data, $isApiCall = false)
    {
        $error = [];
        $checkOnUpdatePassword = ($this->checkPassOnUpdate() || (_cfg('checkPassOnUpdate') != 1 && isset($data['password']) && trim($data['password']))) && substr($this->userData->password, 0, 6) != 'social' && !$isApiCall;

        $validator = new UserProfilePartialValidatorRules();
        $validatorResult = $validator->validate($data, ['swift']);
        if (!$validatorResult['result']) {
            $error = array_merge($error, $validatorResult['errors']);
        }

        if (_cfg('updateUniquePhone') && (isset($data['pre_phone']) && isset($data['main_phone'])) &&
            ($data['pre_phone'] != $this->userData->phone1 || $data['main_phone'] != $this->userData->phone2)) {
            $result = $this->checkUniquePhone($data);
            if (!$result) {
                $error['uniquePhone'] = _('Phone is already registered');
            }
        }

        if (_cfg('requiredRegisterCheckbox')) {
            foreach (_cfg('requiredRegisterCheckbox') as $checkbox) {
                unset($data[$checkbox], $data[$checkbox . 'Date']);
            }
        }

        $data['oldIban'] = json_decode($this->userData->additional_fields ?? '' ,true)['Iban'] ?? '';
        $validator = new UserProfileIbanValidatorRules();
        $validatorResult = $validator->validate($data);
        if (!$validatorResult['result']) {
            $error = array_merge($error, $validatorResult['errors']);
            throw new ApiException('', 400, null, $error);
        }

        // Add phone global + for phone number codes
        foreach(['phoneCode', 'pre_phone', 'phone1'] as $phoneCodeField) {
            if (empty($data[$phoneCodeField]) || $data[$phoneCodeField][0] == '+') {
                continue;
            }
            $data[$phoneCodeField] = '+' . $data[$phoneCodeField];
        }

        if ($isApiCall) {
            $result = null;
            $additionalFields = [];

            // @codeCoverageIgnoreStart
            if (isset($data['emailVerify'])) {
                $result = !self::setEmailVerified($this->userData->id, (int)$data['emailVerify'], true) ?
                    'User E-mail verification failed' : true;

            } elseif (isset($data['emailAgree'])) {
                $additionalFields['sendEmail'] = (bool) $data['emailAgree'];

                $result = !$this->profileAdditionalFieldsUpdate($additionalFields, $this->userData->id)
                    ? 'Failed update E-mail agree' : true;
            } elseif (isset($data['smsAgree'])) {
                $additionalFields['sendSMS'] = (bool) $data['smsAgree'];

                $result = !$this->profileAdditionalFieldsUpdate($additionalFields, $this->userData->id)
                    ? 'Failed update Sms agree' : true;
            } elseif (isset($data['phoneVerify'])) {
                $result = !self::setPhoneVerified($this->userData->id, (int)$data['phoneVerify']) ?
                    'User phone verification failed' : true;
            }

            if (isset($data['additionalFields'])) {
                $data['additionalFields'] = json_decode($data['additionalFields'], true);
                $data['additionalFields']['ext_profile']['pep'] = (bool)$data['additionalFields']['ext_profile']['pep'];
                $this->profileAdditionalFieldsUpdate($data['additionalFields'], $this->userData->id);
                unset($data['additionalFields']);
            }

            // @codeCoverageIgnoreEnd
            if ($result !== null) {
                return $result;
            }
        }


        //First do the fields checking
        if ($checkOnUpdatePassword && isset($data['currentPassword'])) {
            if (!trim($data['currentPassword'])) {
                $error['currentPassword'] = _('current_password_empty');
            } else {
                $ok = self::verifyPassword($data['currentPassword'], $this->userData->password);
                if (!$ok) {
                    // @codeCoverageIgnoreStart
                    PrometheusKeys::getInstance()->AUTH_INCORRECT_PASSWORD->store();
                    // @codeCoverageIgnoreEnd
                    $error['currentPassword'] = _('Current password is incorrect');
                } elseif ($ok === self::OLD_HASH) {
                    if (empty($data['password'])) {
                        $data['repeatPassword'] = $data['password'] = $data['currentPassword'];
                    }
                }
            }
        }

        if (isset($data['password']) && trim($data['password'])) {
            if (!trim($data['repeatPassword'])) {
                $error['repeatPassword'] = _('input_repeated_password');
            } else {
                if ($data['repeatPassword'] != $data['password']) {
                    $error['repeatPassword'] = _('Password does not match');
                }
            }
        }

        $isLoginOrAll = in_array(_cfg('loginBy'), ['login', 'all']);
        $isSMSLogin = $isApiCall && !empty($data['pre_phone']) && !empty($data['main_phone']);

        if (!$isLoginOrAll && !trim($data['email'])) {
            $error['email'] = _('Email field is empty');
        } else if (!empty(trim($data['email']))) {
            $this->checkEmailOnUpdate($data['email'], $error);
        }

        if ($isLoginOrAll && !$isSMSLogin) {
            if(_cfg('loginBy') == 'all' && (!empty(trim($data['email'])) && !filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL)))
                $error['email'] = _('Email field is incorrect');
            else if (empty(trim($data['email'])) && empty(trim($data['login']))) {
                $error['login'] = _('Login field is empty');
                $error['email'] = _('Email field is empty');
            }
        }

        $this->checkLogin($data['login'], $error);

        if ((!isset($data['country']) || !$data['country']) && !$isApiCall) {
            $error['country'] = _('Country is not picked');
        }

        if (!$isApiCall) {
            $fieldsErrors = $this->checkProtectedFields($data);
            $error = array_merge($error, $fieldsErrors);
        }

        $resetPhoneVerify = false;

        if ((_cfg('userProfilePhoneIsRequired') || !empty($data['main_phone'])) && !$isApiCall) {
            $real_number = preg_replace('/[\s]/', '', $data['main_phone']);
            if (!$real_number) {
                $error['main_phone'] = _('phone_empty');
            } else {
                if (substr($data['pre_phone'], 0, 1) != '+' || strlen($data['pre_phone']) < 2 ||
                    !is_numeric(substr($data['pre_phone'], 1, strlen($data['pre_phone'])))) {
                    $error['pre_phone'] = _('Phone number is incorrect. Must be') . ' +888 88888888';
                }
                if (is_numeric($real_number)) {
                    if (strlen($real_number) > 20) {
                        $error['main_phone'] = _('Phone number too long');
                    } else {
                        $data['main_phone'] = $real_number;
                    }
                } else {
                    $error['main_phone'] = _('Phone number is incorrect: some unallowed symbols used.');
                }
            }

            if ($this->userData->phone_verified == 1
                && ($this->userData->phone1 != $data['pre_phone'] || $this->userData->phone2 != $data['main_phone'])
                || $data['phone_verified'] != 1
            ) {
                $resetPhoneVerify = true;
            }
        }

        if (!empty($data['password'])) {

            if (_cfg('PasswordSecureLevel') === 'custom:lowest') {
                if (strlen(trim($data['password'])) < 5) {
                    $error['password'] = _('new_pass_less_5_symbols');
                }
            } elseif (strlen(trim($data['password'])) < 6) {
                    $error['password'] = _('new_pass_less_6_symbols'); //New password is empty or contains less then 6 symbols
            } elseif (!$this->testPassword($data['password'])) {
                $error['password'] = _('New password may contain only latin letters, numbers and special symbols');
            }

            if (empty($error['password'])) {
                $this->logUserData('change password', json_encode(Utils::obfuscatePassword($data)));
                if (!isset($_SERVER['TEST_RUN'])) {
                    //@codeCoverageIgnoreStart
                    $data['password'] = self::passwordHash($data['password']);
                    //@codeCoverageIgnoreEnd
                }
            }
        } else {
            $data['password'] = $this->userData->password;
        }

        //WLC defined hook to check profile data for update
        //return errors array or null
        $data['isApiCall'] = $isApiCall;
        $check_errors = System::hook('user:profile:update:datacheck', $data);
        if(!empty($check_errors)){
            if(!empty($error))
                $error = array_merge($error, $check_errors);
            else $error = $check_errors;
        }

        //TODO: Remove that condition after move all userland functions to hook
        if (function_exists('userDataCheckOnUpdate')) {
            $user_error = userDataCheckOnUpdate($data);

            if (!is_array($error)) {
                $error['main'] = strval($error);
            }

            $error = array_merge($error, $user_error);
        } else {
            if (!trim($data['firstName']) && !$isApiCall) {
                $error['firstName'] = _('first_name_empty');
            }
            if (!trim($data['lastName']) && !$isApiCall) {
                $error['lastName'] = _('last_name_empty');
            }
        }

        //If at least 1 error exist, return error text
        if ($checkOnUpdatePassword && (($errors = $this->assertAuth($data, $isApiCall)) != 1)) {
            return $errors;
        } else if ($error) {
            return "0;" . json_encode($error, JSON_UNESCAPED_UNICODE);
        }

        //update session if auth data were changed
        $_SESSION['user']['login'] = $data['login'];
        $_SESSION['user']['email'] = $data['email'];
        $_SESSION['user']['password'] = $data['password'];

        //Updating mandatory fields for table users
        Db::query('UPDATE `users` SET ' .
            '`password` = "' . Db::escape($data['password']) . '", ' .
            '`first_name` = "' . Db::escape($data['firstName']) . '", ' .
            '`last_name` = "' . Db::escape($data['lastName']) . '", ' .
            '`login` = "' . Db::escape($data['login']) . '", ' .
            '`email` = "' . Db::escape($data['email']) . '", ' .
            '`phone1` = "' . Db::escape($data['pre_phone']) . '", ' .
            '`phone2` = "' . Db::escape($data['main_phone']) . '", ' .
            '`country` = "' . Db::escape($data['country']) . '" ' .
            ($resetPhoneVerify ? ', `phone_verified` = "0"' : '') .
            'WHERE `id` = ' . (int)$this->userData->id
        );

        if (isset($data['nick'])) {
            $data['ext_profile'] = ['nick' => $data['nick']];
            unset($data['nick']);
        }

        $data = (new FixAdditionalFieldsUnicode($data))->run();
        
        $additionalFields = $this->profileAdditionalFieldsUpdate($data, $this->userData->id);

        //updating WLC-specific field for user
        $recordSaved = System::hook('user:profile:update:save', $data, $this->userData->id);

        //TODO: Remove after move user userland functions to hook
        if (function_exists('userProfileUpdate')) {
            userProfileUpdate($data, $this->userData->id);
        } else {

            if(!$recordSaved) {
                $user_id = (int)$this->userData->id;
                $row = Db::fetchRow('SELECT `user_id` FROM `users_data` WHERE `user_id` = ' . $user_id);

                if (isset($data['sex'])) {
                    switch ($data['sex']) {
                        case 'm':
                        case 'f':
                            break;
                        default:
                            $data['sex'] = '';
                            break;
                    }
                }

                if ($row) {
                    Db::query('UPDATE `users_data` SET ' .
                        '`birth_day` = ' . (int)$data['birth_day'] . ', ' .
                        '`birth_month` = ' . (int)$data['birth_month'] . ', ' .
                        '`birth_year` = ' . (int)$data['birth_year'] . ', ' .
                        '`sex` = "' . $data['sex'] . '" ' .
                        'WHERE `user_id` = ' . $user_id
                    );

                } else {
                    Db::query('INSERT INTO `users_data` SET ' .
                        '`user_id` = ' . $user_id . ', ' .
                        '`birth_day` = ' . (int)$data['birth_day'] . ', ' .
                        '`birth_month` = ' . (int)$data['birth_month'] . ', ' .
                        '`birth_year` = ' . (int)$data['birth_year'] . ', ' .
                        '`sex` = "' . $data['sex'] . '" '
                    );
                }
            }
        }

        //Updating user in Fundist
        $url = '/User/Update/?&Login=' . (int)$this->userData->id;

        $transactionId = $this->getApiTID($url);
        $hash = md5('User/Update/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $this->userData->id . '/' . $this->userData->api_password . '/' . $this->userData->currency . '/' . _cfg('fundistApiPass'));
        $params = [
            'Password' => $this->userData->api_password,
            'Currency' => $this->userData->currency,
            'Name' => $data['firstName'],
            'LastName' => $data['lastName'],
            'MiddleName' => isset($data['middleName']) ? Db::escape($data['middleName']) : null,
            'Phone' => $data['pre_phone'] . '-' . $data['main_phone'],
            'Country' => !array_key_exists('country', $data) ? null : ($data['country']),
            'City' => (isset($data['city']) ? Db::escape($data['city']) : null),
            'Address' => (isset($data['address']) ? Db::escape($data['address']) : null),
            'IDNumber' => isset($data['IDNumber']) ? Db::escape($data['IDNumber']) : null,
            'IDIssueDate' => isset($data['IDIssueDate']) ? Db::escape($data['IDIssueDate']) : null,
            'IDIssuer' => isset($data['IDIssuer']) ? Db::escape($data['IDIssuer']) : null,
            'PlaceOfBirth' => isset($data['PlaceOfBirth']) ? Db::escape($data['PlaceOfBirth']) : null,
            'CityOfRegistration' => isset($data['CityOfRegistration']) ? Db::escape($data['CityOfRegistration']) : null,
            'AddressOfRegistration' => isset($data['AddressOfRegistration']) ? Db::escape($data['AddressOfRegistration']) : null,
            'IndexOfRegistration' => isset($data['IndexOfRegistration']) ? Db::escape($data['IndexOfRegistration']) : null,
            'Iban' => isset($data['Iban']) ? Db::escape($data['Iban']) : null,
            'BranchCode' => isset($data['BranchCode']) ? Db::escape($data['BranchCode']) : null,
            'BankName' => isset($data['BankName']) ? Db::escape($data['BankName']) : null,
            'BankNameText' => isset($data['BankNameText']) ? Db::escape($data['BankNameText']) : null,
            'Swift' => isset($data['Swift']) ? Db::escape($data['Swift']) : null,
            'BankAddress' => isset($data['BankAddress']) ? Db::escape($data['BankAddress']) : null,
            'PostalCode' => (isset($data['postal_code']) ? Db::escape($data['postal_code']) : null),
            'Email' => $data['email'],
            'ExtLogin' => $data['login'],
            'Gender' => !array_key_exists('sex', $data) ? null : $this->getFundistValue('Gender', ($data['sex'])),
            'AlternativePhone' => (isset($data['pre_alternate_phone']) ? Db::escape($data['pre_alternate_phone']) : null) . '-' . (isset($data['main_alternate_phone']) ? Db::escape($data['main_alternate_phone']) : null),
            'DateOfBirth' => empty($data['birth_year']) ? null : sprintf("%04d-%02d-%02d", (int)$data['birth_year'],
                (int)$data['birth_month'], (int)$data['birth_day']),
            'Language' => !empty($data['language']) ? $data['language'] : _cfg('language'),
            'Timezone' => (isset($data['timezone']) ? $data['timezone'] : null),
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
            'AdditionalUserIP' => json_encode(System::getUserIP(true)),
            'EmailAgree' => (int) ($additionalFields['sendEmail'] ?? 0),
            'SmsAgree' => (int) ($additionalFields['sendSMS'] ?? 0),
            'CustomParams' => !empty($additionalFields['ext_profile']['customParams']) ? substr((string) $additionalFields['ext_profile']['customParams'], 0, 250): '',
            'RestrictCasinoBonuses' => (int)!empty($additionalFields['RestrictCasinoBonuses']),
            'RestrictSportBonuses' => (int)!empty($additionalFields['RestrictSportBonuses']),
            'State' => empty($data['state']) ? null : Db::escape($data['state']),
            'IsApiCall' => $isApiCall,
            'PEP' => isset($additionalFields['ext_profile']['pep'])
                     && $additionalFields['ext_profile']['pep'] == 'true' ? 1 : 0,
            'CPF' => isset($additionalFields['cpf']) ? str_replace(['.', '-'], '', $additionalFields['cpf']) : null,
        ];

        if($resetPhoneVerify) {
            $params['PhoneVerified'] = 0;
        }

        if (isset($data['ext_profile']['nick'])) $params['Nick'] = $data['ext_profile']['nick'];

        if ($isApiCall) {
            if (isset($data['affiliateId'])) {
                $params['AffiliateID'] = $data['affiliateId'];
            }
            if (isset($data['affiliateSystem'])) {
                $params['AffiliateSystem'] = $data['affiliateSystem'];
            }
            $params['Pincode'] = !empty($data['pincode']) ? $data['pincode'] : '';
        }

        if (isset($data['affiliateClickId'])) {
            if (isset($data['affiliateClickIdOld']) && $data['affiliateClickIdOld'] != $data['affiliateClickId']) {
                $data['affiliateClickId'] = preg_replace('/[^a-zA-Z\d\=\&\?\_\.-]/', '', $data['affiliateClickId']);
            }

            $params['affiliateClickId'] = $data['affiliateClickId'];
        } else {
            $params['affiliateClickId'] = '';
        }

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);

        if (_cfg('useFundistTemplate') == 1) {
            $sendEmail = isset($data['sendMailAfterUserUpdate']) ? $data['sendMailAfterUserUpdate'] : true;
            if ($sendEmail &&  $this->isNeedProfileChangeMail($data, (array)$this->userData)) {
                $this->sendMailAfterUserUpdate($this->userData->id, $this->userData->api_password, $data, ($this->userData->email != $data['email']));
            }
        }

        System::hook('user:profile:update:save:after', $response, $data);

        $brakedown = explode(',', $response);

        if (isset($_SERVER['TEST_RUN'])) {
            return true;
        }

        if ($brakedown[0] != 1) {
            return $response;
        }

        if ((!empty($data['pre_phone']) && !empty($data['main_phone'])) &&
            ($data['pre_phone'] != $this->userData->phone1 || $data['main_phone'] != $this->userData->phone2)
        ) {
            $_SESSION['user']['phone1'] = $data['pre_phone'];
            $_SESSION['user']['phone2'] = $data['main_phone'];
        }


        return true;
    }

    public function profileUpdatePartial($fields, $data)
    {
        $errors = [];
        $phoneChange = false;

        if (!$this->isUser() && !$this->userData) {
            $errors[] = _('Session expired');
        }

        // @codeCoverageIgnoreStart
        if (in_array('verification', $fields, true)) {
            $response = $this->updateFundistUser(['VerificationReset' => true]);
            $response = explode(',', $response, 2);

            return (int)$response[0] === 1 ? true : $response[1];
        }
        // @codeCoverageIgnoreEnd

        foreach ($data as $k => $v) {
            if (!is_array($v)) {
                $data[$k] = trim($v);
            }
        }

        if (in_array('email', $fields)) {
            if (_cfg('checkPassOnUpdateEmail') && (!array_key_exists('currentPassword', $data) || empty($data['currentPassword']) || !self::verifyPassword($data['currentPassword'], $this->userData->password))) {
                // @codeCoverageIgnoreStart
                PrometheusKeys::getInstance()->AUTH_INCORRECT_PASSWORD->store();
                // @codeCoverageIgnoreEnd
                $errors[] = _('Current password is incorrect');
            }
        }

        if (in_array('phone', $fields)) {
            $phoneChange = true;
            $fields[] = 'pre_phone';
            $fields[] = 'main_phone';
        }

        $validator = new UserProfilePartialValidatorRules();
        $validatorResult = $validator->validate($data, $fields);

        if (!$validatorResult['result']) {
            $errors = array_merge($errors, array_values($validatorResult['errors']));
        }

        if (!empty($errors)) {
            return $errors;
        }

        $emailChange = false;
        $queryFields = [];
        $fundistFields = [];
        $emailFields = [];
        $sessionFields = [];
        $fieldToQuery = [
            'email' => 'new_email',
            'phone' => null,
            'pre_phone' => 'phone1',
            'main_phone' => 'phone2'
        ];

        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = Db::escape($data[$field]);

            if ($field === 'email') {
                $emailCode = sha1($this->userData->first_name . '/' . $this->userData->last_name . '/' . $data['email'] . '/' . microtime() . '/email-verification');
                $emailChange = true;

                $queryFields[] = "`email_verification_code` = '{$emailCode}'";
            }

            if (array_key_exists($field, $fieldToQuery)) {
                if ($fieldToQuery[$field] === null) {
                    continue;
                }

                $field = $fieldToQuery[$field];
            }

            $queryFields[] = "`{$field}` = '{$value}'";
            $sessionFields[$field] = $value;
        }

        if ($phoneChange) {
            $fundistFields['Phone'] = $data['pre_phone'] . '-' . $data['main_phone'];
            $emailFields['pre_phone'] = $data['pre_phone'];
            $emailFields['main_phone'] = $data['main_phone'];
        }

        if ($emailChange) {
            $completeMailUrl = _cfg('completeMailUrl') ?: '';
            $completeMailUrl = str_replace('%language%', _cfg('language'), $completeMailUrl);
            $completeMailDomain = _cfg('mailLinkDomain') ?: _cfg('site');
            $completeMailUrl = $completeMailDomain . '/' . $completeMailUrl . '?message=COMPLETE_CHANGE_EMAIL&code=' . $emailCode;

            $data = $this->sendFundistMail($this->userData->id, $this->userData->api_password, 'ConfirmationChangeEmail', [
                'confirmUrl' => $completeMailUrl,
                'email' => $data['email']
            ]);

            $data = explode(',', $data, 2);
            if ($data[0] !== '1') {
                $errors[] = $data[1];
            }
        }

        if (!empty($errors)) {
            return $errors;
        }

        Db::query('START TRANSACTION');

        Db::query('UPDATE `users` SET ' . implode(', ', $queryFields) . ' WHERE `id` = ' . (int)$this->userData->id);

        if (!empty($fundistFields)) {
            $response = $this->updateFundistUser($fundistFields);
            $response = explode(',', $response);

            if ($response[0] != 1) {
                Db::query('ROLLBACK');

                return [
                    $response[1]
                ];
            }
        }

        $this->profileAdditionalFieldsUpdate(['emailChangeVerifyAttempts' => 0], (int)$this->userData->id);

        Db::query('COMMIT');

        if (!empty($sessionFields)) {
            foreach ($sessionFields as $field => $value) {
                $_SESSION['user'][$field] = $value;
            }
        }

        return true;
    }

    public function profileConfirmEmail()
    {

        $emailCode = sha1($this->userData->first_name . '/' . $this->userData->last_name . '/' . $this->userData->email . '/' . microtime() . '/email-verification');

        $completeMailUrl = _cfg('completeMailUrl') ?: '';
        $completeMailUrl = str_replace('%language%', _cfg('language'), $completeMailUrl);
        $completeMailUrl = _cfg('site') . '/'.$completeMailUrl.'?message=CONFIRMATION_EMAIL&code='. $emailCode;
        $errors = [];
        $queryFields = [];

        $queryFields[] = "`email_verification_code` = '{$emailCode}'";

        $data = $this->sendFundistMail($this->userData->id, $this->userData->api_password, 'ConfirmationEmail', [
            'confirmUrl' => $completeMailUrl,
            'email' => $this->userData->email
        ]);

        [$mailStatus, $mailError] = explode(',', $data, 2);

        if ($mailStatus !== '1') {
            $errors[] = $mailError;
        }

        if (!empty($errors)) {
            return $errors;
        }

        Db::query('START TRANSACTION');

        Db::query('UPDATE `users` SET ' . implode(', ', $queryFields) . ' WHERE `id` = ' . (int)$this->userData->id);

        Db::query('COMMIT');

        return true;
    }

    public function profileUpdateEmail($email, $code)
    {
        $errors = [];

        if (!$this->isUser() && !$this->userData) {
            $errors[] = _('Session expired');
        }

        if (!empty($email) && $this->userData->new_email !== $email) {
            $errors[] = _('Email field is incorrect');
        }

        if (empty($this->userData->new_email)) {
        	$errors[] = _('New email field is incorrect');
        }

        if (empty($code) || $this->userData->email_verification_code != $code) {
            $errors[] = _('Code is incorrect');
        }

        if (!empty($errors)) {
            return $errors;
        }

        $email = $this->userData->new_email;

        Db::query('START TRANSACTION');

        Db::query("
            UPDATE `users`
            SET
              `new_email` = '',
              `email_verification_code` = '',
              `email` = '".Db::escape($email)."'
            WHERE `id` = " . (int)$this->userData->id);

        $response = $this->updateFundistUser([
            'Email' => $email,
            'email' => $email
        ]);

        $response = explode(',', $response, 2);

        if ($response[0] != 1) {
            Db::query('ROLLBACK');
            return [$response[1]];
        }

        $response = $this->setEmailVerified((int)$this->userData->id, true);

        if ($response !== true) {
        	Db::query('ROLLBACK');
            return [_('User verification failed')];
        }

        Db::query('COMMIT');

        $_SESSION['user']['email'] = $email;

        return true;
    }

    /**
     * Update user status field
     * @param array $data Array of fields (keys: id, status)
     * @return mixed true|json error
     */
    public function updateStatus($data)
    {
        if (!isset($data['status'])) {
            return json_encode(array('error' => array('status' => 'Status key not exists')));
        }

        Db::query('UPDATE `users` SET ' .
            '`status` = ' . (int)$data['status'] . ' ' .
            'WHERE `id` = ' . (int)$this->userData->id
        );

        return true;
    }

    /**
     * @return bool|string
     */
    public static function updateTemporaryLocks()
    {
        if (empty($_POST['params'])) {
            Logger::log('Empty params');
            return json_encode(['error' => ['Error' => 'Empty params']]);
        }

        $data = json_decode($_POST['params'], JSON_OBJECT_AS_ARRAY);
        if (json_last_error() != JSON_ERROR_NONE || !is_array($data) || (empty($data['enabled']) && empty($data['disabled']))) {
            Logger::log('Wrong data');
            return json_encode(['error' => ['Error' => 'Wrong data']]);
        } else {
            $data['enabled'] = $data['enabled'] ? array_filter($data['enabled']) : [];
            $data['disabled'] = $data['disabled'] ? array_filter($data['disabled']) : [];
            $enabledCount = count($data['enabled']);
            $disabledCount = count($data['disabled']);

            if ($enabledCount > 0) {
                Logger::log('Enable ' . implode(',', $data['enabled']));
                Db::query('UPDATE `users` SET ' .
                    '`status` = 1 ' .
                    'WHERE `id` IN (' . implode(',', $data['enabled']) . ') ' .
                    'LIMIT ' . $enabledCount
                );
            }
            if ($disabledCount > 0) {
                Logger::log('Disable ' . implode(',', $data['disabled']));
                Db::query('UPDATE `users` SET ' .
                    '`status` = 0 ' .
                    'WHERE `id` IN (' . implode(',', $data['disabled']) . ') ' .
                    'LIMIT ' . $disabledCount
                );
            }
        }

        return true;
    }

    //Main function to get user information for all WLCs
    public function getUserByEmail($customerEmail)
    {
        if (!$customerEmail) {
            return false;
        }

        $userData = Db::fetchRow('SELECT `u`.*, `ud`.*, `u`.`currency` AS `currencySign` ' .
            'FROM `users` AS `u` ' .
            'LEFT JOIN `users_data` AS `ud` ON `u`.`id` = `ud`.`user_id` ' .
            'WHERE `u`.`email` = "' . Db::escape($customerEmail) . '" '
        );

        if ($userData === false) {
            return false;
        }

        return $this->checkUserData($userData);
    }

    public function getUserByLogin($customerLogin)
    {
        if (!$customerLogin) {
            return false;
        }

        $userData = Db::fetchRow('SELECT `u`.*, `ud`.*, `u`.`currency` AS `currencySign` ' .
            'FROM `users` AS `u` ' .
            'LEFT JOIN `users_data` AS `ud` ON `u`.`id` = `ud`.`user_id` ' .
            'WHERE `u`.`login` = "' . Db::escape($customerLogin) . '" '
        );

        if ($userData === false) {
            return false;
        }

        return $this->checkUserData($userData);
    }
    // @codeCoverageIgnoreStart
    public function getUserByPhone($phone1, $phone2)
    {
        if (!$phone1 && !$phone2) {
            return false;
        }

        $userData = Db::fetchRow('SELECT `u`.*, `ud`.*, `u`.`currency` AS `currencySign` ' .
            'FROM `users` AS `u` ' .
            'LEFT JOIN `users_data` AS `ud` ON `u`.`id` = `ud`.`user_id` ' .
            'WHERE `u`.`phone1` = "' . Db::escape($phone1) . '" AND  `u`.`phone2` = "' . Db::escape($phone2) .'"'
        );

        if ($userData === false) {
            return false;
        }

        return $this->checkUserData($userData);
    }
    // @codeCoverageIgnoreEnd

    private function checkUserData($userData) {
        //Old numeric currency, must be changed to signs.
        if (is_numeric($userData->currency)) {
            $userData->currency = $this->fetchCurrency($userData->currency);
            $userData->currencySign = $userData->currency;
        }

        if (Db::fetchRow('SHOW TABLES LIKE "users_favorites"')) {
            //Fetching favoriteGames data only if table exists, not required for some WLCs
            $userFavoriteGames = Db::fetchRows('SELECT * ' .
                'FROM `users_favorites` ' .
                'WHERE `user_id` = ' . (int)$userData->id . ' ' .
                'GROUP BY `game_id` '
            );

            $userData->favoriteGames = $userFavoriteGames;
            $userData->favoriteGamesIds = array();
            if ($userData->favoriteGames) {
                foreach ($userData->favoriteGames as $v) {
                    if (isset($v->game_id) && $v->game_id) {
                        $userData->favoriteGamesIds[] = $v->game_id;
                    }
                }
            }
            $userData->favoriteGames = (array)$userData->favoriteGames;
        }


        //Checking if tradingURL is required/already registered, required only for Spot option
        //None the less, registering variable to leave out PHP Notices
        $userData->tradingURL = false;
        if (isset($_SESSION['user']['tradingURL'])) {
            $userData->tradingURL = $_SESSION['user']['tradingURL'];
        }

        return $userData;
    }

    //Function to check currenct logged in user, no parameters required.
    //Function check email and password of user
    public function checkUser()
    {
        if (!$this->isUser($user_object_created = false)) {
            return '0,' . _('Session is expired');
        }

        $where = [];
        if (!empty($_SESSION['user']['email'])) {
            $where[] = '`email` = "' . Db::escape($_SESSION['user']['email']) . '"';
        }

        if (!empty($_SESSION['user']['login'])) {
            $where[] = '`login` = "' . Db::escape($_SESSION['user']['login']) . '"';
        }

        if (!empty($_SESSION['user']['phone1']) && !empty($_SESSION['user']['phone2'])) {
            $where[] = '`phone1`= "' . Db::escape($_SESSION['user']['phone1']) . '" AND `phone2`= "' . Db::escape($_SESSION['user']['phone2']) . '"';
        }

        if (empty($where)) {
            return false;
        } else {
            $where = implode(' AND ',$where);
        }

        $row = Db::fetchRow('SELECT `email`, `login`, `id` ' .
            'FROM `users` ' .
            'WHERE ' . $where .
            ' AND `password` = "' . Db::escape($_SESSION['user']['password']) . '" '
        );

        if ($row === false) {
            return false;
        }

        if (!empty($row->email)) {
            $user = $this->getUserByEmail($row->email);
        } else if (!empty($row->login)) {
            $user = $this->getUserByLogin($row->login);
        } else if (!empty($row->id)) {
            $user = $this->getUserById($row->id);
        }

        $Login = $user->id;

        $useUserInfoCache = true;
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 7) as $f) {
            if (
                ($f['function'] == 'put' && $f['class'] == 'eGamings\WLC\RestApi\AuthResource')
                || ($f['function'] == 'get' && $f['class'] == 'eGamings\WLC\RestApi\UserInfoResource')
            ) {
                $useUserInfoCache = false;
                break;
            }
        }
        $userInfo = User::getInfo($Login, $useUserInfoCache);

        $user->fundistBalance = isset($userInfo['balance']) ? $userInfo['balance'] : 0;
        $user->openPositions = isset($userInfo['openPositions']) ? $userInfo['openPositions'] : 0;
        $user->loyalty = isset($userInfo['loyalty']) && is_array($userInfo['loyalty']) ? $userInfo['loyalty'] : [];

        // get user data (with passport) from Fundist
        $data = $this->getFundistUser($user);
        $data = explode(',', $data, 2);
        if ($data[0] === '1') {
            $data = json_decode($data[1], true);
            foreach (array_keys($data) as $k) {
                $user->$k = $data[$k];
            }
        }

        return $user;
    }

    //Function to get information about any user in WLC by ID
    //Not used anywhere for now
    public function getUserById($customerId = 0)
    {
        if ($customerId == 0) {
            return 'User ID can not be 0';
        }

        $row = Db::fetchRow('SELECT `email`, `login`, `phone1`, `phone2` FROM `users` WHERE `id` = ' . (int)$customerId);

        if (empty($row->email) && empty($row->login) && empty($row->phone1) && empty($row->phone2)) {
            return 'User not found';
        }
// @codeCoverageIgnoreStart
        if (!empty($row->email)) {
            $user = $this->getUserByEmail($row->email);
        } else if (!empty($row->login)) {
            $user = $this->getUserByLogin($row->login);
        } elseif (!empty($row->phone1) && !empty($row->phone2)) {
            $user = $this->getUserByPhone($row->phone1, $row->phone2);
        }
        // @codeCoverageIgnoreEnd

        if (!$user) {
            return false;
        }

        return $user;
    }

    public function getUserIdByCode($code)
    {
        $userData = Db::fetchRow('SELECT `id` FROM `users_temp` WHERE ' .
            '`code` = "' . Db::escape($code) . '" ' .
            'LIMIT 1'
        );

        if ($userData === false) {
            return false;
        }

        return $userData->id;
    }

    public function getUsersTempByCode(string $code) {
        $userData = Db::fetchRow('SELECT * FROM `users_temp` WHERE ' .
            '`code` = "' . Db::escape($code) . '" ' .
            'LIMIT 1'
        );

        return $userData === false ? new \stdClass() : $userData;
    }

    public function getUsersTempByEmail(string $email) {
        $userData = Db::fetchRow('SELECT * FROM `users_temp` WHERE ' .
            '`email` = "' . Db::escape($email) . '" ' .
            'LIMIT 1'
        );

        return $userData === false ? new \stdClass() : $userData;
    }

    public static function getIdByEmailCode($code)
    {
        $userData = Db::fetchRow('SELECT `id` FROM `users` WHERE ' .
            '`email_verification_code` = "' . Db::escape($code) . '" ' .
            'LIMIT 1'
        );

        if ($userData === false) {
            return false;
        }

        return $userData->id;
    }

    /**
     * @param string $code
     *
     * @return object|null
     */
    public static function getDataByEmailCode(string $code): ?object
    {
        $userData = Db::fetchRow(
            'SELECT * FROM `users` WHERE ' .
            '`email_verification_code` = "' . Db::escape($code) . '" ' .
            'LIMIT 1'
        );

        if ($userData === false) {
            return null;
        }

        return $userData;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public static function removeEmailCode(int $id): bool
    {
        return Db::query(
            "UPDATE `users`
            SET
              `new_email` = '',
              `email_verification_code` = ''
            WHERE `id` = '{$id}'"
        );
    }


    /**
     * Set/reset email verification
     *
     * @param int $user_id
     * @param int $status
     * @param bool $isApiCall
     *
     * @return bool|string
     */
    public static function setEmailVerified($user_id, $status = 1, $isApiCall = false)
    {
        $verify = !empty($status) ? 1 : 0;
        $time   = !empty($status) ? 'NOW()' : 'NULL';

        $row = Db::fetchRow('SELECT `additional_fields` FROM `users` WHERE `id` = '.(int)$user_id);
        if ($row) {
            $additional_fields = json_decode($row->additional_fields, true);
            if (!$additional_fields || !is_array($additional_fields)) {
                $additional_fields = [];
            }
            $additional_fields['email_verified'] = $verify;
            Db::query('UPDATE `users` SET ' .
                'additional_fields = "' . Db::escape(json_encode($additional_fields)) . '"' .
                'WHERE `id` = '.(int)$user_id
            );
        }

        $result = Db::query('UPDATE `users` SET `email_verified` = "' . $verify . '", `email_verified_datetime` = ' . $time . '
                WHERE `id` = ' . (int)$user_id);

        if (!$isApiCall) {
            $base = (Array)Db::fetchRow('SELECT * FROM `users` WHERE `id`=' . (int)$user_id);
            $extra = (Array)Db::fetchRow('SELECT * FROM `users_data` WHERE `user_id`=' . (int)$user_id);

            $data = $base + $extra;

            $result = Fundist::userUpdate($data);
        }

        return json_encode([
            'result' => $result,
            'additional_fields' => $additional_fields
        ]);
    }

    /**
     * Set/reset phone verification
     *
     * @param int $status
     *
     * @return bool|string
     */
    public static function setPhoneVerified($user_id, $status = 1, $isApiCall = false)
    {
        $verify = !empty($status) ? 1 : 0;

        $row = Db::fetchRow('SELECT `additional_fields` FROM `users` WHERE `id` = '.(int)$user_id);
        if ($row) {
            $additional_fields = json_decode($row->additional_fields, true);
            if (!$additional_fields || !is_array($additional_fields)) {
                $additional_fields = [];
            }
            $additional_fields['phone_verified'] = $verify;
            Db::query('UPDATE `users` SET ' .
                'additional_fields = "' . Db::escape(json_encode($additional_fields)) . '"' .
                'WHERE `id` = '.(int)$user_id
            );
        }

        $result = Db::query('UPDATE `users` SET `phone_verified` = "' . $verify . '" WHERE `id` = ' . (int) $user_id);
        if (!$isApiCall) {
            $base = (Array)Db::fetchRow('SELECT * FROM `users` WHERE `id`=' . (int)$user_id);
            $extra = (Array)Db::fetchRow('SELECT * FROM `users_data` WHERE `user_id`=' . (int)$user_id);
            $data = $base + $extra;
            $result = Fundist::userUpdate($data);
        }

        return $result;
    }

    public function newSocialConnect($code)
    {
        $connectRow = Db::fetchRow('SELECT * FROM `social_connect` WHERE ' .
            '`code` = "' . Db::escape($code) . '" ' .
            'LIMIT 1'
        );

        if (!$connectRow) {
            return false;
        }

        $userData = $this->getUserByEmail($connectRow->email);
        if (!$userData) {
            return false;
        }

        if (empty($userData->first_name) || empty($userData->last_name)) {
            //Add user info to users
            $userDataCache = $this->_cache->get(Db::escape($userData->email));

            if ($userDataCache && (!empty($userDataCache['firstName']) || !empty($userDataCache['lastName']))) {

                $firstName = $userData->first_name ? $userData->first_name : $userDataCache['firstName'];
                $lastName = $userData->last_name ? $userData->last_name : $userDataCache['lastName'];

                DB::query(
                    'UPDATE `users` SET ' .
                    '`first_name` = "' . Db::escape($firstName) . '", ' .
                    '`last_name` = "' . Db::escape($lastName) . '" ' .
                    'WHERE `id` = ' . (int)$userData->id
                );
            }
        }

        //Adding to user that this social is now available
        Db::query(
            'UPDATE `users_data` SET ' .
            '`social_' . Db::escape($connectRow->social) . '` = 1 ' .
            'WHERE `user_id` = ' . (int)$userData->id
        );

        //Registering in social table
        Db::query(
            'INSERT INTO `social` SET ' .
            '`social` = "' . Db::escape($connectRow->social) . '", ' .
            '`social_uid` = ' . Db::escape($connectRow->social_uid) . ', ' .
            '`user_id` = ' . (int)$userData->id . ' '
        );

        //Deleting code
        Db::query(
            'DELETE FROM `social_connect` ' .
            'WHERE `code` = "' . Db::escape($code) . '" ' .
            'LIMIT 1 '
        );
        //Also cleaning up if many codes were created
        Db::query(
            'DELETE FROM `social_connect` ' .
            'WHERE `email` = "' . Db::escape($connectRow->email) . '" AND ' .
            '`social` = "' . Db::escape($connectRow->social) . '" ' .
            'LIMIT 1 '
        );

        $loginData = array(
            'login' => $userData->email,
            'relogin' => 1, // ignore password check - no need for this in social logins
        );

        $this->login($loginData, true);

        return true;
    }

    public function login(
        $data,
        $relogin = false,
        $loginType = self::LOGIN_TYPE_DEFAULT,
        $skipCheckIsFirstSession = false,
        $authorizedBy2FA = false,
        $fast_registration = false
    ) {
        $fastPhoneRegistration = _cfg('fastPhoneRegistration') && empty($data['email']) && empty($data['login']);
        $loginByPhone = false;
        // @codeCoverageIgnoreStart
        switch ($loginType) {
            case self::LOGIN_TYPE_SMS:
                if ($fastPhoneRegistration && !$relogin && (empty($data['phoneCode']) || empty($data['phoneNumber']) || empty($data['pass']))) {
                    $this->logUserData('login', 'sms authorization failed: ' . json_encode($data));
                    // @codeCoverageIgnoreStart
                    PrometheusKeys::getInstance()->AUTH_SMS_AUTH_FAILED->store();
                    // @codeCoverageIgnoreEnd
                    return '0;' . _('authorization_error_sms');
                } elseif (empty($data['phoneCode']) || empty($data['phoneNumber']) || (empty($data['code']) && !$relogin && !$fastPhoneRegistration)) {
                    $this->logUserData('login', 'sms authorization failed: ' . json_encode($data));
                    // @codeCoverageIgnoreStart
                    PrometheusKeys::getInstance()->AUTH_SMS_AUTH_FAILED->store();
                    // @codeCoverageIgnoreEnd
                    return '0;' . _('authorization_error_sms');
                }

                $phoneCode = (int)trim($data['phoneCode'], '+- ');
                $phoneNumber = (int)trim($data['phoneNumber'], '+- ');
                if (!$relogin) {
                    $storedCode = $this->_cache->get(
                        SmsProviderResource::SMS_VERIFICATION_CODE,
                        [
                            'phoneCode' => $phoneCode,
                            'phoneNumber' => $phoneNumber
                        ]
                    );
                    if ($data['code'] != $storedCode) {
                        $this->logUserData('login', 'wrong sms code: ' . json_encode($data));
                        // @codeCoverageIgnoreStart
                        PrometheusKeys::getInstance()->AUTH_INVALID_SMS_CODE->store();
                        // @codeCoverageIgnoreEnd
                        return '0;' . _('Invalid validation code');
                    }
                }
                $where = 'WHERE `phone1`= "+' . Db::escape($phoneCode) . '" AND `phone2`= "' . Db::escape($phoneNumber) . '"';

                break;
            case self::LOGIN_TYPE_METAMASK:
                $validator = new EtheriumSignatureValidatorRules();
                $validatorResult = $validator->validate($data);
                if (!$relogin && !$validatorResult['result']) {
                    $this->logUserData('login', 'wrong signature: ' . json_encode($data));
                    // @codeCoverageIgnoreStart
                    PrometheusKeys::getInstance()->AUTH_INVALID_SIGNATURE_METAMASK->store();
                    // @codeCoverageIgnoreEnd
                    return '0;' . _('Invalid signature');
                }
                $where = 'WHERE `login` = "' . Db::escape($data['walletAddress']) . '"';
                break;
            default:
                $data['login'] = trim($data['login']);

                $loginByPhone = _cfg('loginBy') === 'all' && (int)_cfg('registerUniquePhone') === 1
                    && !empty($data['phoneCode']) && !empty($data['phoneNumber'])
                    && empty($data['login']);

                if (!$loginByPhone && empty($data['login'])) {
                    $this->logUserData('login', 'empty login: ' . json_encode($data));
                    // @codeCoverageIgnoreStart
                    PrometheusKeys::getInstance()->AUTH_FAIL->store();
                    // @codeCoverageIgnoreEnd
                    return '0;' . _('authorization_error');
                }

                $where = 'WHERE `email` = "' . Db::escape($data['login']) . '"';
                if (!$relogin && (!isset($data['relogin']) || ($data['relogin'] != 1)) && !empty(_cfg('loginBy'))) {
                    switch (_cfg('loginBy')) {
                        case 'email':
                            break;
                        case 'login':
                            $where = 'WHERE `login` = "' . Db::escape($data['login']) . '"';
                            break;
                        case 'all':
                            $where .= ' or `login` = "' . Db::escape($data['login']) . '"';
                            if ($loginByPhone) {
                                $phoneCode = (int)trim($data['phoneCode'], '+- ');
                                $phoneNumber = (int)trim($data['phoneNumber'], '+- ');
                                $where = ' WHERE `phone1`= "+' . Db::escape($phoneCode) . '" AND `phone2`= "' . Db::escape($phoneNumber) . '"';
                            }
                    }
                }

                if ($fast_registration === true) {
                    $where = 'WHERE `login` = "' . Db::escape($data['login']) . '"';
                }

                break;
        }
        $log_data = json_encode(Utils::obfuscatePassword($data));
        $user = Db::fetchRows(
            'SELECT * ' .
            'FROM `users` ' . $where
        );

        if ($user === false) {
            // @codeCoverageIgnoreStart
            PrometheusKeys::getInstance()->HOOK_USER_LOGIN_FAIL->store();
            // @codeCoverageIgnoreEnd
            $user = Db::fetchRow(
                'SELECT * ' .
                'FROM `users_temp` ' . $where
            );
            if ($user === false) {
                $user = System::hook('user:login:fail',$data);

                if (!$user) {
                    $this->logUserData('login','user not found: ' .$log_data);
                    // @codeCoverageIgnoreStart
                    PrometheusKeys::getInstance()->AUTH_FAIL->store();
                    // @codeCoverageIgnoreEnd
                    return '0;' . _('authorization_error');
                }
            } else {
                $this->logUserData('login','email not verified: ' .$log_data);
                return '0;' . _('You have to verify your email first');
            }
        }

        $user = (array)$user;
        if (count($user) > 1) {
            return '0;' . _('Login error. Kindly try to log in using another method');
        }
        $user = $user[0];

        // @codeCoverageIgnoreEnd

        $additionalFields = json_decode($user->additional_fields, true);

        $google2fa = new Auth2FAGoogle();

        if (isset($additionalFields['secret_2fa']) && !$authorizedBy2FA && !$relogin) {
            if ($google2fa->checkEnable2FAGoogle()) {
                $okPassword = self::verifyPassword($data['pass'], $user->password);

                if (!$okPassword) {
                    return '0;' . _('authorization_error');
                }

                $resultEnable2FAOnUser = $google2fa->checkEnable2FAOnUserOrSendFail($additionalFields, $user->email);

                if ($resultEnable2FAOnUser) {
                    throw new ApiExceptionWithData($resultEnable2FAOnUser, 231);
                }

            }
        } 

        if (!$relogin && (empty($data['relogin']) || ($data['relogin'] != 1)) && ($loginType === self::LOGIN_TYPE_DEFAULT || $fastPhoneRegistration)) {
            $ok = self::verifyPassword($data['pass'], $user->password);

            if (!$ok) {
                $ok = System::hook('user:login:password:verify',$data['pass'], $user->password);
                // @codeCoverageIgnoreStart
                PrometheusKeys::getInstance()->HOOK_AUTH_PASSWORD_VERIFY->store();
                // @codeCoverageIgnoreEnd
            }

            // update password hash if old hash is detected
            if ($ok === self::OLD_HASH) {
                $user->password = $password = self::passwordHash($data['pass']);
                Db::query('UPDATE `users` SET ' .
                    '`password` = "' . Db::escape($password) . '" ' .
                    'WHERE `id` = ' . (int)$user->id
                );
            } elseif (!$ok) {
                if (isset($additionalFields['change_pass']) && $additionalFields['change_pass'] == 1) {
                    $this->logUserData('login','password change required: ' .$log_data);
                    return '0;' . _('password_expired');
                }

                $this->logUserData('login','incorrect password: ' .$log_data);
                // @codeCoverageIgnoreStart
                PrometheusKeys::getInstance()->AUTH_INCORRECT_PASSWORD->store();
                // @codeCoverageIgnoreEnd
                return '0;' . _('authorization_error');
            }
        }
        if (isset($user->status)) {
            switch ($user->status) {
                case -1:
                    if ($this->fundist_uid($user->id, 'check_status')) {
                        Db::query('UPDATE users SET '.
                            '`status` = 1 '.
                            'WHERE id = ' . $user->id
                        );
                    }
                    else {
                        $this->logUserData('login', 'registration in progress: ' . $log_data);
                        // @codeCoverageIgnoreStart
                        PrometheusKeys::getInstance()->REGISTRATION_IN_PROGRESS->store();
                        // @codeCoverageIgnoreEnd
                        return '0;' . _('registration_in_progress');
                    }
                    break;

                case 0:
                    $this->logUserData('login','user disabled: ' .$log_data);
                    // @codeCoverageIgnoreStart
                    PrometheusKeys::getInstance()->USER_DISABLED->store();
                    // @codeCoverageIgnoreEnd
                    return '0;' . (_cfg('enableAmlCheck') ? 'user_disabled_contact_support' : _('user_disabled'));
                    break;
            }
        } elseif (!$this->fundist_uid($user->id, 'check_status')) {
            $this->logUserData('login','user disabled: ' .$log_data);
            // @codeCoverageIgnoreStart
            PrometheusKeys::getInstance()->USER_DISABLED->store();
            // @codeCoverageIgnoreEnd
            return '0;' . _('user_disabled');
        }

        //Checking if user have numeric currency, we must change it to string type as numeric not used anywhere
        if (is_numeric($user->currency)) {
            $user->currency = $this->fetchCurrency($user->currency);
            Db::query('UPDATE `users` SET ' .
                '`currency` = "' . Db::escape($user->currency) . '" ' .
                'WHERE `id` = ' . (int)$user->id
            );
        }

        $login = (int)$user->id;
        $url = '/User/Login/?&Login=' . $login;
        $transactionId = $this->getApiTID($url);
        $hash = md5('User/Login/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'Password' => $user->api_password,
            'TID' => $transactionId,
            'Hash' => $hash,
            'Login' => $login,
            'UserIP' => System::getUserIP(),
            'AdditionalUserIP' => json_encode(System::getUserIP(true)),
            'UserAgent' => $_SERVER['HTTP_USER_AGENT'],
            'FingerPrint' => !empty($data['FingerPrint']) ? $data['FingerPrint'] : '',
        );
        $url .= '&' . http_build_query($params);
        $response = $this->runFundistAPI($url);
        $result = explode(',', $response, 2);

        if ($result[0] !== '1') {
            $this->logUserData('login', 'fundist update last login: ' . $result[1]);
            $result[0] = '2';
            return implode(';', $result);
        }

        if (!empty($result[1]) && $userInfo = json_decode($result[1], true)) {
            $hookResult = System::hook('user:login:after', $userInfo);
            PrometheusKeys::getInstance()->HOOK_AUTH_AFTER_LOGIN->store();

            if ($hookResult === false || (is_array($hookResult) && array_search(false, $hookResult) !== false)) {
                PrometheusKeys::getInstance()->AUTH_ACCESS_FORBIDDEN->store();
                return '0;' . _('Access forbidden');
            }

            $user->UserTags = !empty($userInfo['UserTags']) ? $userInfo['UserTags'] : '';
            $user->IsPublicAccount = !empty($userInfo['IsPublicAccount']);
        }

        if (!Core::getInstance()->sessionStarted()) {
            Core::getInstance()->sessionStart(true);
            PrometheusKeys::getInstance()->SESSION_START->store();
        }

        $_SESSION['user'] = (array)$user;

        $user->tradingURL = false;
        if (_cfg('enableSpotOption') == 1) {
            $user->tradingURL = $this->generateTradingUrl($user);
        }

        if (isset($data['remember']) && $data['remember'] == 1) {
            //Save for longer time - 30 days
            @setcookie('rememberUser', serialize((array)$user), time() + 30 * 24 * 3600, '/', _cfg('cookieDomain'));
        }

        $this->logUserData('login', $log_data);
        if (_cfg('enableAuthUserId')) {
            @setcookie('authUserId', $login, 0, '/'); //60 days
        }

        $loggedUser = new User();
        $loggedUser->setProfileType($loginType);

        if (!$skipCheckIsFirstSession) {
            $loggedUser->setIsFirstSession();
        }

        if (_cfg('singleSession')) {
            $SC = new SessionControl();
            $SC->checkOpenSessions($loggedUser);
        }
        $sessionId = session_id();

        if (!empty($_SERVER['HTTP_X_NONCE']) && $sessionId) {
            $nonceService = new NonceService();
            $nonceService->set($sessionId, $_SERVER['HTTP_X_NONCE']);
        }

        if (_cfg('enableCookieProtection') && (!empty($_SERVER['REMOTE_ADDR'] || !empty($_SERVER['HTTP_USER_AGENT'])))) {
            (new CookieProtection())->set(
                $sessionId,
                $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
        }

        if (_cfg('enableFastTrackAuthentication')) {
            $sid = Utils::generateSid();
            $storage = Front::Storage();
            $storage->setRecord((int)$user->id, ['key' => 'sid', 'value' => json_encode($sid)]);

            return '1;' . $user->tradingURL . ';' . $sid;
        }

        return '1;' . $user->tradingURL;
    }

    public function logout()
    {
        if (_cfg('isFuncoreLogoutRequired')) {
            $this->funcoreLogout();
        }

        Core::getInstance()->sessionDestroy();

        // @codeCoverageIgnoreStart
        if (headers_sent() === false) {
            setcookie('rememberUser', '', 0, '/', _cfg('cookieDomain')); //60 days
            if (_cfg('enableAuthUserId')) {
                setcookie('authUserId', '', 0, '/'); //60 days
            }
        }
        // @codeCoverageIgnoreEnd
        return true;
    }

    public function funcoreLogout(): bool
    {
        $login = (int) $this->userData->id;
        $url = '/User/Logout/?&Login=' . $login;
        $transactionId = $this->getApiTID($url);
        $hash = md5('User/Logout/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));

        $params = [
            'Password' => $this->userData->api_password,
            'TID' => $transactionId,
            'Hash' => $hash,
            'Login' => $login,
            'UserIP' => System::getUserIP(),
            'AdditionalUserIP' => json_encode(System::getUserIP(true)),
            'UserAgent' => $_SERVER['HTTP_USER_AGENT'],
        ];
        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);
        $result = explode(',', $response, 2);

        if ($result[0] !== '1') {
            $this->logUserData('logout','cant logout from funcore: ' . $response);
            return false;
        }

        return true;
    }

    public function checkPassword($pass, $securityLevel = "low")
    {
        if (!$this->testPassword($pass)) {
            return false;
        }

        $securityLevel = _cfg("PasswordSecureLevel") ? : $securityLevel;
        $securityLevelChecks = [];
        switch($securityLevel) {
            case "low":
            case "strong":
            case "super-strong":
                break;
            default:
                $securityLevelParams = explode(':', $securityLevel, 2);
                if (count($securityLevelParams) != 2 || empty($securityLevelParams[1])) {
                    $securityLevel = "low";
                } else {
                    $securityLevel = $securityLevelParams[0];
                    $securityLevelChecks = explode(',', $securityLevelParams[1]);
                }
                break;
        }

        $have_lower = false;
        $have_upper = false;
        $have_digit = false;
        $have_alpha = false;

        $have_special = preg_match( "/[[:punct:]]/", $pass );
        $have_digit = preg_match('/[0-9]/', $pass);
        $have_any_five = preg_match("/^[A-Za-z0-9!@#$%^&*()_+=-`~\]\\\[{}|\';:\/.,\?\>\<]{5,}$/", $pass);

        if (mb_strtolower( $pass ) !== mb_strtoupper( $pass )) {
            $have_alpha = true;
        }

        if ($have_alpha && mb_strtolower( $pass ) !== $pass) {
            $have_upper = true;
        }

        if ($have_alpha && mb_strtoupper( $pass ) !== $pass) {
            $have_lower = true;
        }

        switch($securityLevel) {
            case 'strong':
                if (!$have_lower || !$have_upper || !$have_digit || !$have_special) {
                    return false;
                }
                break;
            case "super-strong":
                $uppercase = preg_match('@[A-Z]@', $pass);
                $lowercase = preg_match('@[a-z]@', $pass);
                $number = preg_match('@[0-9]@', $pass);

                if (strlen(trim($pass)) < 8 || !$uppercase || !$lowercase || !$number) {
                    return false;
                }
                break;
            case 'custom':
                foreach($securityLevelChecks as $securityLevelCheck) {
                    switch($securityLevelCheck) {
                        case 'lower':
                            if (!$have_lower) return false;
                            break;
                        case 'lowest':
                            if (!$have_any_five) return false;
                            break;
                        case 'upper':
                            if (!$have_upper) return false;
                            break;
                        case 'digit':
                            if (!$have_digit) return false;
                            break;
                        case 'special':
                            if (!$have_special) return false;
                            break;
                        case 'alpha':
                            if (!$have_alpha) return false;
                            break;
                        default:
                            return false;
                    }
                }
                break;

            case 'low':
            default:
                if (!(($have_lower || $have_upper) && $have_digit)) {
                    return false;
                }
                break;
        }

        return true;

    }

    public function testPassword($password) {
        return preg_match('/^[A-z0-9[:punct:]]+$/i', $password) ? true : false;
    }

    public function userDataCheck($data, $loginType = self::LOGIN_TYPE_DEFAULT)
    {
        $error = [];

        $fieldIsEmpty = function($fieldName) use ($data) {
            return empty(trim(isset($data[$fieldName]) ? $data[$fieldName] : ''));
        };

        // @codeCoverageIgnoreStart
        if ($loginType === self::LOGIN_TYPE_DEFAULT) {
            $isEmailIncorrect = !$fieldIsEmpty('email') && !filter_var($data['email'], FILTER_VALIDATE_EMAIL);
            //default data check
            switch (_cfg('loginBy')) {
                case 'all':
                    if ($isEmailIncorrect) {
                        $error['email'] = _('Email field incorrect');
                    }
                    if ($fieldIsEmpty('login') && $fieldIsEmpty('email')) {
                        $error['email'] = _('Email field is empty');
                        $error['login'] = _('Login field is empty');
                    }

                    break;

                case 'login':
                    if ($fieldIsEmpty('login'))
                        $error['login'] = _('Login field is empty');
                    break;

                case 'email':
                default:
                    if ($fieldIsEmpty('email') || $isEmailIncorrect)
                        $error['email'] = _('email_empty_incorrect'); //Email field is empty or incorrect
                    break;
            }
        }
        // @codeCoverageIgnoreEnd

        if (_cfg('PasswordSecureLevel') !== 'unsecure') {
            if (_cfg('PasswordSecureLevel') === 'custom:lowest' &&
                (!isset($data['password']) || !$data['password'] || strlen($data['password']) < 5)) {
                $error['password'] = _('pass_less_5_symbols');//Password is empty or contains less then 5 symbols
            }

            if (!$this->testPassword($data['password'])) {
                $error['password'] = _('Password may contain only latin letters, numbers and special symbols');
            }

            if (!$this->checkPassword($data['password'])) {
                if (_cfg('PasswordSecureLevel') == 'strong') {
                    $error['password'] = _('Password must contain latin letters in upper and lower case, numbers and special symbols');
                } else if (_cfg('PasswordSecureLevel') == 'super-strong') {
                    $error['password'] = _('Password must be at least 8 characters and contain latin letters and numbers');
                } else {
                    $error['password'] = _('Password must contain latin letters and numbers');
                }
            }
        }

        if (!isset($data['currency']) || !$data['currency']) {
            $error['currency'] = _('currency_not_set');//Currency not set
        } else {
            $currencies = [];
            $config = Config::getSiteConfig();
            if (is_array($config['currencies'])) {
                $currencies = array_map(function ($currency) {
                    return $currency['Name'];
                }, $config['currencies']);

                if (Api::isApiCall()) {
                	$exclude_currencies = _cfg('exclude_currencies');
                	if (is_array($exclude_currencies)) foreach($exclude_currencies as $exclude_currency) {
                		if (!in_array($exclude_currency, $currencies)) {
                			$currencies[] = $exclude_currency;
                		}
                	}
                }

                if (!in_array($data['currency'], $currencies)) {
                    $error['currency'] = _('Currency field is invalid');

                    $this->logUserData('invalid currency', json_encode($data));
                }
            }
        }

        $data['isApiCall'] = Api::isApiCall();

        //Old user defined data check, now use hook System::hook('user:datacheck', $data);
        //TODO: Remove that condidion after move userland functions to hook
        if (function_exists('userDataCheck')) {
            $user_error = userDataCheck($data);
            if (!is_array($error)) {
                $error['main'] = strval($error);
            }

            $error = array_merge($error, $user_error);
        }

        $check_errors = System::hook('user:datacheck', $data);
        if(!empty($check_errors)) {
            if(!empty($error))
                $error = array_merge($error, $check_errors);
            else $error = $check_errors;
        }

        if (!empty($data['reg_promo'])) {
            if (!$this->isValidPromoCode(
                $data['reg_promo'], $data['currency'], _cfg('language'),
                User::LOYALTY_LEVEL_INITIAL, $data['country'])
            ) {
                $error['reg_promo'] = _('reg_promo_is_invalid');
            }
        }

        if (!empty($error)) return $error;

        $where = '';

        if (!$fieldIsEmpty('email')) {
            $where = '`email` = "' . Db::escape($data['email']) . '" ';
        }

        if (!$fieldIsEmpty('login')) {
            $separator = !empty($where) ? ' OR ' : '';
            $where .=  $separator . '`login` = "' . Db::escape($data['login']) . '" ';
        }
        // @codeCoverageIgnoreStart
        if ($loginType === self::LOGIN_TYPE_SMS && !$fieldIsEmpty('pre_phone') && !$fieldIsEmpty('main_phone')) {
            $separator = !empty($where) ? ' OR ' : '';
            $where .=  $separator . '`phone1` = "' . Db::escape($data['pre_phone']) . '" AND `phone1` = "'. Db::escape($data['main_phone']) . '" ';
        }

        if ($loginType === self::LOGIN_TYPE_METAMASK && !$fieldIsEmpty('walletAddress')) {
            $separator = !empty($where) ? ' OR ' : '';
            $where .=  $separator . '`login` = "' . Db::escape($data['walletAddress']) . '"';
        }

        if (_cfg('registerUniqueCpf') && !$fieldIsEmpty('cpf') && in_array($data['country'], ['bra', 'rou'])) {
            $separator = !empty($where) ? ' OR ' : '';
            $where .=  $separator . '(`country` = "' . $data['country'] . '" AND JSON_EXTRACT(`additional_fields`, "$.cpf") = "'. $data['cpf'] .'")';
        }
        // @codeCoverageIgnoreEnd

        $query = Db::fetchRow('SELECT * FROM `users` ' . 'WHERE ' . $where . 'LIMIT 1');

        // @codeCoverageIgnoreStart
        if ($query !== false) {
            if (!_cfg('hideEmailExistence') && !$fieldIsEmpty('email') && $query->email == $data['email']) {
                $error['email'] = _('email_already_registered'); //This email is already registered
            }
            if (!$fieldIsEmpty('login') && $query->login == $data['login']) {
                $error['login'] = _('login_already_registered'); //This login is already registered
            }

            if ($loginType === self::LOGIN_TYPE_SMS && !$fieldIsEmpty('pre_phone') && !$fieldIsEmpty('main_phone')
                && $query->phone1 == $data['pre_phone'] && $query->phone2 == $data['main_phone']) {
                $error['Phone'] = _('Phone number already registered');
            }

            if ($loginType === self::LOGIN_TYPE_METAMASK && !$fieldIsEmpty('walletAddress') && $query->login == $data['walletAddress']) {
                $error['login'] = _('login_already_registered'); //This login is already registered
            }

            if (
                !empty(_cfg('registerUniqueCpf'))
                && $data['country'] == $query->country
                && !$fieldIsEmpty('cpf') 
                && $data['cpf'] == json_decode($query->additional_fields)->cpf
                && in_array($data['country'], ['bra', 'rou'])
            ) {
                $error['cpf'] = $data['country'] == 'bra' 
                    ? _('CPF number already registered') 
                    : _('CNP number already registered');
            }
        }
        // @codeCoverageIgnoreEnd

        return $error;
    }

    /**
     * @codeCoverageIgnore
     */
    public static function checkPromoCode(array $request, array $query, array &$errors = []): ?bool {
        $requiredFields = ['promocode', 'currency'];
        $fields = ['promocode', 'currency', 'country'];

        foreach($fields as $fieldName) {
            $value = empty($request[$fieldName]) ? (empty($query[$fieldName]) ? null : $query[$fieldName]) : $request[$fieldName];

            if ($value === null && in_array($fieldName, $requiredFields)) {
                $errors[] = $fieldName;
            }

            $fields[$fieldName] = $value;
        }

        if (count($errors) > 0) {
            return null;
        }

        return (bool) User::getInstance()->isValidPromoCode(
          $fields['promocode'], $fields['currency'], _cfg('language'),
          User::LOYALTY_LEVEL_INITIAL, $fields['country']);
    }

    public function checkIP($ip = false, $max = false, $timeout = false)
    {
        $ips = $ip ? [trim($ip)] : System::getUserIP(true);
        $max = (int)$max > 0 ? (int)$max : (int)_cfg('one_ip_reg_max');
        if (!$timeout) {
            $timeout = (int)_cfg('one_ip_reg_timeout');
        }

        $reject = array_map(function ($i) {
            return trim($i);
        }, explode(',', _cfg('one_ip_reg_blacklist')));

        foreach($ips as $key => $one_ip){
            if (in_array($one_ip, $reject)) {
                return _('ip_fraud_blacklist');
            }
        }

        if ($max === 0) {
            return 1;
        }

        $allow = array_map(function ($i) {
            return trim($i);
        }, explode(',', _cfg('one_ip_reg_whitelist')));

        foreach($ips as $key => $one_ip) {
            if (in_array($one_ip, $allow)) {
                return 1;
            }
        }

        $ip = $ip ? trim($ip) : System::getUserIP();
        $check = array('users', 'users_temp');
        foreach ($check as $k => $table) {
            $query = Db::fetchRow(
                'SELECT COUNT(*) as total, MAX(reg_time) as last_reg, NOW() as now FROM `' . $table . '`
                     WHERE `reg_ip` = "' . Db::escape($ip) . '" AND `reg_time`> NOW() - INTERVAL ' . (int)$timeout . ' MINUTE'
            );

            if ($query === false) {
                return _('ip_fraud_check_error');
            }

            if ($query->total >= $max) {
                return [
                    'ip' => $ip,
                    'timeout' => round(((int)strtotime($query->last_reg) + (int)$timeout * 60 - strtotime($query->now)) / 60)
                ];
            }
        }

        return 1;
    }

    public function serviceApplyAndSendConfirmationEmail(array $request): bool {
        $data = (array) $this->userData;

        $additionalFields = json_decode($data['additional_fields'], true);

        if (\json_last_error() !== JSON_ERROR_NONE) {
            $additionalFields = [
                "email_code" => "email_code_not_found"
            ];
        }

        $data['password'] = $request['password'] || "";
        $data['code'] = $data['id'];
        $data['email_code'] = $additionalFields['email_code'];

        return $this->sendConfirmationEmail($data);
    }

    public function register($data, $skip_login = false, $fast_registration = false, $loginType = self::LOGIN_TYPE_DEFAULT, $acceptTermsOfService = false)
    {
        // @codeCoverageIgnoreStart
        if (empty($data['email']) && empty($data['login']) && !empty($data['pre_phone'] && !empty($data['main_phone']))) {
            $loginType = self::LOGIN_TYPE_SMS;
        }
        self::$userState = self::$REGISTRATION;
        switch ($loginType) {
            case self::LOGIN_TYPE_SMS:
                $where = '`phone1` = "' . Db::escape($data['pre_phone']) . '" AND `phone2` = "' . Db::escape($data['main_phone']) . '"';
                break;
            case self::LOGIN_TYPE_METAMASK:
                if ($loginType === self::LOGIN_TYPE_METAMASK) {
                    $validator = new EtheriumSignatureValidatorRules();
                    $validatorResult = $validator->validate($data);
                    if (!is_array($validatorResult)) {
                        return json_encode([
                            'error' => [
                                'login' => _('Registration data validation failed')
                            ]
                        ], JSON_UNESCAPED_UNICODE);
                    }

                    if (!$validatorResult['result']) {
                        return json_encode([
                            'error' => [
                                'login' => _('Invalid signature')
                            ]
                        ], JSON_UNESCAPED_UNICODE);
                    }
                }
                $where = '`login` = "' . Db::escape($data['walletAddress']) . '"';
                break;
            default:
                if (!empty($data['email'])) {
                    $where = '`email` = "' . Db::escape($data['email']) . '"';
                } elseif (!empty($data['login'])) {
                    $where = '`login` = "' . Db::escape($data['login']) . '"';
                }
                break;
        }
        $userReal = Db::fetchRow('SELECT `id` FROM `users` WHERE ' . $where);

        if (_cfg('registerUniquePhone') && !empty($data['main_phone'])) {
            $wherePhone = '`phone1` = "' . Db::escape($data['pre_phone']) . '" AND `phone2` = "'. Db::escape($data['main_phone']) . '"';
            $userRealWithPhone = Db::fetchRow('SELECT `id` FROM `users` WHERE ' . $wherePhone);

            if ($userRealWithPhone) {
                return json_encode(array('error' => array('email' => _('Phone is already registered'))), JSON_UNESCAPED_UNICODE);
            }
        }

        if ($userReal !== false) {
            if ($loginType === self::LOGIN_TYPE_DEFAULT && !empty($data['email'])) {
                $this->sendPasswordRecovery(intval($userReal->id), $data);
            }
            return true;
        }
        // @codeCoverageIgnoreEnd

        //auto generating password for registerGeneratePassword == true wlcs, if password field is empty
        $generatePassword = !empty(_cfg('registerGeneratePassword'));
        if (empty($data['password']) && $generatePassword) {
            $data['password'] = $data['repeat_password'] = $this->generatePassword(8, false);
        }

        if (!empty(_cfg('termsOfService')) && $acceptTermsOfService) {
            $data['termsOfService'] = _cfg('termsOfService');
        }

        $hook_data = System::hook('user:before:register', $data);

        if (!empty($hook_data['error'])) {
            return json_encode(array('error' => $hook_data['error']), JSON_UNESCAPED_UNICODE);
        }

        if (!empty($hook_data['data'])) {
            $data = array_merge($data,$hook_data['data']);
        }

        if (($fraud = $this->checkIP()) != 1) {
            return isset($fraud['ip']) ? strtr(_('_ip_fraud_msg'), $fraud) : $fraud;
        }

        if (isset($data['check_reg'])) {
            return 1;
        } //call before show reg popup

        if (isset($data['social']) && $data['social'] != '') {
            if (!isset($_SESSION['social']) || !isset($_SESSION['social'][$data['social']])) {
                return json_encode(array('error' => array('main' => _('Session expired'))), JSON_UNESCAPED_UNICODE);
            }

            $s = new Social();

            return $s->Verify($data['social']);
        }

        $error = '';

        foreach ($data as $k => $v) {
            if (!is_array($data[$k]) && $k != 'password' && !is_bool($v)) {
                $data[$k] = trim($v);
            }
        }

        $error = $this->userDataCheck($data, $loginType);
        //If at least 1 error exist, return errors json
        if (!empty($error)) {
            return json_encode(array('error' => $error), JSON_UNESCAPED_UNICODE);
        }

        $original_password = $data['password'];

        if (!isset($data['social_uid'])) {
            $data['password'] = self::passwordHash($data['password']);
        }

        $firstName = empty($data['firstName']) ? '' : $data['firstName'];
        $lastName = empty($data['lastName']) ? '' : $data['lastName'];

        $data['code'] = sha1($firstName . '/' . $lastName . '/' . $data['email'] . '/' . microtime());
        $data['email_code'] = sha1($firstName . '/' . $lastName . '/' . $data['email'] . '/' . microtime() . '/email-verification');
        $data['uhash'] = sha1(md5(base64_encode($original_password) . self::$salt));
        if (!empty($original_password)) {
            $data['original_password'] = $original_password;
        }

        // @codeCoverageIgnoreStart
        if ($loginType === self::LOGIN_TYPE_METAMASK) {
            $data['login'] = $data['walletAddress'];
            unset($data['message'], $data['walletAddress'], $data['signature']);
        }
        // @codeCoverageIgnoreEnd

         if (_cfg('enableAmlCheck')) {
             $dob = "{$data['birth_year']}-{$data['birth_month']}-{$data['birth_day']}";
             $reference = (new KycAml())->amlCheck([
                 'Email' => $data['email'],
                 'Country' => $data['country'] ?? null,
                 'Name' => $data['firstName'],
                 'LastName' => $data['lastName'],
                 'DateOfBirth' => $dob,
             ]);

            $data['aml_reference'] = $reference;
         }

        //Registering user in local WLC table
        $uid = $this->registerDB($data, $fast_registration);
        $fastRegistrationWithoutBetsFlag = (bool) _cfg('fastRegistrationWithoutBets');

        // @codeCoverageIgnoreStart
        if (isset($data['social_uid']) ||
            _cfg('fastRegistration') == 1 ||
            $fastRegistrationWithoutBetsFlag ||
            $fast_registration === true
        ) {
            if ($loginType === self::LOGIN_TYPE_DEFAULT) {
                $on_registration = Db::fetchRow('SELECT * FROM `users_temp` WHERE `email` = "' . Db::escape($data['email']) . '" and `additional_fields` like "%finishRegistrationFlag%"');
                if (!empty($on_registration)) {
                    if (_cfg('hideEmailExistence')) {
                        $this->sendPasswordRecovery(intval($uid), $data);

                        return true;
                    }

                    return json_encode(array('error' => array('email' => _('email_already_registered'))), JSON_UNESCAPED_UNICODE);
                }
            }

            $answer = $this->finishRegistration($uid, $data, $skip_login, $fast_registration, $loginType);

            if (!is_object($answer) && $answer !== true) {
                return $answer;
            }

            if (_cfg('fastPhoneRegistration') && !empty($data['main_phone'])) {
                $request = [];
                $query = [];
                $request['phoneCode'] = $data['pre_phone'];
                $request['phoneNumber'] = $data['main_phone'];
                $query['action'] = 'reg';
                $message = _('sms_registration');
                $query['message'] = sprintf($message, $data['pre_phone'] . $data['main_phone'], $data['original_password'] );
                $resource = new SmsProviderResource();
                $resource->post($request, $query);
            }

            if (_cfg('fastRegistration') && _cfg('sendVerificationEmailAlongWithConfirmation') && self::isAuthenticated()) {
                $u = new User($_SESSION['user']['id']);
                $u->profileConfirmEmail();
                $u->profileAdditionalFieldsUpdate(['emailChangeVerifyAttempts' => 0], (int)$this->userData->id);
            }

            if($fastRegistrationWithoutBetsFlag || _cfg('useFundistTemplate') == 1)
                return true;
            // @codeCoverageIgnoreEnd
        }
        if (_cfg('disableRegistrationMail') || empty($data['email'])) return true;

        $sendEmailResult = $this->sendConfirmationEmail($data, $fast_registration);

        return _cfg('hideEmailExistence') ? true : $sendEmailResult;
    }

    public function sendPasswordRecovery(int $uid, array $data): array {
        if (!isset($data['redirectUrl'])) {
            $restorePasswordUrl = _cfg('restorePasswordUrl') ?: '';
            $restorePasswordUrl = str_replace('%language%', _cfg('language'), $restorePasswordUrl);

            $data['redirectUrl'] = _cfg('site') . '/' . $restorePasswordUrl . '?message=SET_NEW_PASSWORD';
        }

        [$code, $message] = explode(';', $this->checkIfEmailExist($data), 2);

        if ($code != '1') {
            Logger::log(sprintf('User [%s]: Sending a password recovery link has failed: %s', $uid, $message));
        }

        return [$code, $message];
    }

    public function sendConfirmationEmail(array $data, bool $fast_registration = false): bool {
        $firstName = empty($data['firstName']) ? '' : $data['firstName'];
        $lastName = empty($data['lastName']) ? '' : $data['lastName'];
        $original_password = $data['original_password'] ?: $data['password'];

        $templateName = !empty($data['social_uid']) ? 'registration-social' : 'registration';
        $templateContext = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $data['email'],
            'password' => $original_password,
            'code' => $data['code'],
            'url' => _cfg('site') . '/run/code',
            'social_network' => (isset($data['social_network']) ? $data['social_network'] : ''),
            'email_verification_url' => _cfg('site') . '/run/emailverify',
            'email_code' => $data['email_code'],
            'site_url' => _cfg('site'),
            'site_name' => _cfg('websiteName'),
            'site_support_link' => _cfg('site') . '/' . (!empty($data['site_support_link']) ? $data['site_support_link'] : ''),
            'user_profile_link' => _cfg('site') . '/' . (!empty($data['user_profile_link']) ? $data['user_profile_link'] : '')
        ];

        //Add bonus fields to mail template
        if (!empty($data['reg_bonus'])) {
            $bonusesCacheKey = implode(':', ['bonuses', _cfg('fundistApiKey')]);
            $bonusesDataCache = $this->_cache->get($bonusesCacheKey);
            $idBonusCache = array_search($data['reg_bonus'], array_column($bonusesDataCache, 'ID'));

            if ($idBonusCache !== false) {
                $bonusDataCache = $bonusesDataCache[$idBonusCache];
                $templateContext = array_merge($templateContext, [
                    'reg_bonus_target' => !empty($bonusDataCache['Target']) ? $bonusDataCache['Target'] : '',
                    'reg_bonus_name' => !empty($bonusDataCache['Name']) ? $bonusDataCache['Name'] : ''
                ]);
            }
        }

        if(_cfg('useFundistTemplate') == 1) {
            $fundistEmail = new FundistEmailTemplate();
            $mailData = [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $data['email'],
                'password' => $original_password,
                'currency' => $data['currency'],
                'code' => $data['code'],
                'reg_ip' => System::getUserIP(),
                'reg_site' => _cfg('site'),
                'reg_lang' => _cfg('language'),
            ];
            return $fundistEmail->sendRegistration($mailData);
        }

        $template = new Template();
        $msg = $template->getMailTemplate($templateName, $templateContext);

        if (_cfg('fastRegistration') || $fast_registration === true) {
            $msg = preg_replace('/\{% if \!fastRegistration %\}[^\{]+\{% \/if \%}/', '', $msg);
        } else {
            $msg = str_replace(['{% if \!fastRegistration %}', '{% /if %}'], '', $msg);
        }

        if (_cfg('emailVerification')) {
            $msg = str_replace(['{% if emailVerification %}', '{% /if %}'], '', $msg);
        } else {
            $msg = preg_replace('/\{% if emailVerification %\}[^\{]+\{% \/if \%}/', '', $msg);
        }

        $msgReplaceKeys = [];
        $msgReplaceVals = [];
        foreach($templateContext as $k => $v) {
            $msgReplaceKeys[] = '%'.$k.'%';
            $msgReplaceVals[] = $v;
        }

        $msg = str_replace( $msgReplaceKeys, $msgReplaceVals, $msg );
        $_SESSION['just_registered'] = 1;

        if (_cfg('enqueue_emails') == 1) {
            return Email::enqueue($data['email'], _('Activate your account'), $msg);
        } else {
            $mailMsg = Email::send($data['email'], _('Activate your account'), $msg);
            if ($mailMsg) {
                return $mailMsg;
            } else {
                self::logRegistrationMailError($data);
            }
        }

        return true;
    }

    static function logRegistrationMailError($data, $response = [])
    {
        $system = System::getInstance();
        $url = '/WLCRegistrationError/Mail?';
        $transactionId = $system->getApiTID($url);
        $completeRegstrationUrl = _cfg('completeRegstrationUrl') ?: '';
        $redirectUrl = _cfg('site') . '/'.$completeRegstrationUrl.'?message=COMPLETE_REGISTRATION&code='. $data['code'];
        $hash = md5('WLCRegistrationError/Mail/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
        $params = [
            'TID' => $transactionId,
            'Hash' => $hash,
            'SiteName' => _cfg('websiteName'),
            'Site' => _cfg('site'),
            'email' => $data['email'],
            'ComleteRegistrationLink' => $redirectUrl,
            'response' => $response,
        ];
        $url .= '&' . http_build_query($params);
        return $system->runFundistAPI($url);
    }

    private function prepareAndSendRegistrationToFundistAPI($userData, $additionalFields = [])
    {
        if(empty($additionalFields) && !empty($userData->additional_fields) && $userData->additional_fields != '{}') {
            $additionalFields = json_decode($userData->additional_fields, true);
        }

        $AffiliateSystem = !empty($additionalFields['affiliateSystem']) ? $additionalFields['affiliateSystem'] : '';
        $AffiliateId = !empty($additionalFields['affiliateId']) ? $additionalFields['affiliateId'] : '';
        $AffiliateClickID = !empty($additionalFields['affiliateClickId']) ? $additionalFields['affiliateClickId'] : '';
        $extProfile = !empty($additionalFields['ext_profile']) ? $additionalFields['ext_profile'] : [];

        // @codeCoverageIgnoreStart
        if ($AffiliateSystem == 'affilka') {
            parse_str($AffiliateId, $affData);
            if (is_array($affData) && !empty($affData['code'])) {
                $url = '/Affiliate/SyncAffilkaPlayer?&Login=' . $userData->id;
                $transactionId = $this->getApiTID($url);
                $hash = md5('SyncAffilkaPlayer/0.0.0.0/'.$transactionId.'/'._cfg('fundistApiKey').'/'._cfg('fundistApiPass'));
                $country = GeoIp::countryIsoData($userData->country)['alpha2'] ?? '';
                $params = [
                    'Hash' => $hash,
                    'TID' => $transactionId,
                    'Data' => [
                        'players' => [
                            [
                                'bonus_code' => $affData['code'],
                                'email' => $userData->email,
                                'user_id' => $userData->id,
                                'first_name' =>  $userData->first_name,
                                'last_name' =>  $userData->last_name,
                                'language' => $userData->reg_lang,
                            ],
                        ],
                    ],
                ];
                if (!empty($country)) {
                    $params['Data']['players'][0]['country'] = $country;
                }

                $url .= '&' . http_build_query($params);
                $resp = explode(',', $this->runFundistAPI($url));
                if ($resp[0] == '1' && !empty($resp[1])) {
                    list($AffiliateId, $AffiliateClickID) = explode('_', $resp[1]);
                    Affiliate::setAffilkaCookie($AffiliateId, $AffiliateClickID);
                }
            }
        }
        // @codeCoverageIgnoreEnd
        //Registering user in Fundist
        $url = '/User/Add/?&Login='.$userData->id;

        $transactionId = $this->getApiTID($url, $userData->id);

        if (is_numeric($userData->currency)) {
            $userData->currency = $this->fetchCurrency($userData->currency);
        }

        $hash = md5('User/Add/0.0.0.0/'.$transactionId.'/'._cfg('fundistApiKey').'/'.$userData->id.'/'.$userData->api_password.'/'.$userData->currency.'/'._cfg('fundistApiPass'));

        if (isset($additionalFields['reg_promo'])) {
            $promo = $additionalFields['reg_promo'];
        } else {
            $promo = '';
        }

        if (isset($additionalFields['reg_bonus'])) {
            $bonus = $additionalFields['reg_bonus'];
        } else {
            $bonus = '';
        }


        $userBirthDate = '0000-00-00'; //change from empty string to empty date due to errors on save to database
        if (!empty($userData->birth_day) && !empty($userData->birth_month) && !empty($userData->birth_year)) {

            if ($userData->birth_day !== '00' && $userData->birth_month !== '00' && $userData->birth_year !== '0000') { // to avoid the conversion of 0000-00-00 to Unix time
                $birthDateTimestamp = mktime(0,0,0, $userData->birth_month, $userData->birth_day, $userData->birth_year);
                if ($birthDateTimestamp < time()) {
                    $userBirthDate = date('Y-m-d', $birthDateTimestamp);
                }
            }
        }

        $params = [
            'Password' => $userData->api_password,
            'Name' => $userData->first_name,
            'LastName' => $userData->last_name,
            'MiddleName' => !empty($additionalFields['middleName']) ? $additionalFields['middleName'] : '',
            'Phone' => (!empty($userData->phone1) ? $userData->phone1.'-' : '') . (!empty($userData->phone2) ? $userData->phone2 : ''),
            'Country' => isset($userData->country) ? $userData->country : '',
            'City' => isset($additionalFields['city']) ? $additionalFields['city'] : '',
            'Address' => isset($additionalFields['address']) ? $additionalFields['address'] : '',
            'PostalCode' => isset($additionalFields['postal_code']) ? $additionalFields['postal_code'] : '',
            'DateOfBirth' => $userBirthDate,
            'Email' => $userData->email,
            'ExtLogin' => $userData->login,
            'TID' => $transactionId,
            'Currency' => $userData->currency,
            'AffiliateID' => $AffiliateId,
            'AffiliateSystem' => $AffiliateSystem,
            'AffiliateClickID' => $AffiliateClickID,

            'Iban' => isset($additionalFields['Iban']) ? $additionalFields['Iban'] : null,
            'BranchCode' => isset($additionalFields['BranchCode']) ? $additionalFields['BranchCode'] : null,
            'BankName' => isset($additionalFields['BankName']) ? $additionalFields['BankName'] : null,
            'Swift' => isset($additionalFields['Swift']) ? $additionalFields['Swift'] : null,
            'BankAddress' => isset($additionalFields['BankAddress']) ? $additionalFields['BankAddress'] : null,

            'State' => isset($additionalFields['state']) ? $additionalFields['state'] : null,
            'Gender' => isset($userData->sex) ? $this->getFundistValue('Gender', $userData->sex) : '',
            'IDNumber' => !empty($additionalFields['IDNumber']) ? $additionalFields['IDNumber'] : '',
            'IDIssueDate' => !empty($additionalFields['IDIssueDate']) ? $additionalFields['IDIssueDate'] : '',
            'IDIssuer' => !empty($additionalFields['IDIssuer']) ? $additionalFields['IDIssuer'] : '',
            'PlaceOfBirth' => !empty($additionalFields['PlaceOfBirth']) ? $additionalFields['PlaceOfBirth'] : '',
            'CityOfRegistration' => !empty($additionalFields['CityOfRegistration']) ? $additionalFields['CityOfRegistration'] : '',
            'AddressOfRegistration' => !empty($additionalFields['AddressOfRegistration']) ? $additionalFields['AddressOfRegistration'] : '',
            'IndexOfRegistration' => !empty($additionalFields['IndexOfRegistration']) ? $additionalFields['IndexOfRegistration'] : '',
            'Nick' => !empty($additionalFields['ext_profile']['nick']) ? $additionalFields['ext_profile']['nick'] : '',
            'Timezone' => !empty($additionalFields['timezone']) ? $additionalFields['timezone'] : '',
            'AlternativePhone' => (
                isset($additionalFields['pre_alternate_phone']) && is_string($additionalFields['pre_alternate_phone']) &&
                isset($additionalFields['main_alternate_phone']) && is_string($additionalFields['main_alternate_phone'])
                    ? $additionalFields['pre_alternate_phone'] . '-' . $additionalFields['main_alternate_phone'] : null),
            'Hash' => $hash,
            'UHash' => !empty($additionalFields['uhash']) ? $additionalFields['uhash'] : '',
            'Pincode' => !empty($additionalFields['pincode']) ? $additionalFields['pincode'] : '',
            'Language' => !empty($additionalFields['language']) ? $additionalFields['language'] : _cfg('language'),
            'RegistrationIP' => $userData->reg_ip,
            'UserIP' => $userData->reg_ip,
            'AdditionalUserIP' => json_encode(System::getUserIP(true)),
            'PromoCode' => $promo,
            'BonusID' => $bonus,
            'SendWelcomeEmail' => _cfg('useFundistTemplate') == 1,
            'FingerPrint' => !empty($additionalFields['finger_print']) ? $additionalFields['finger_print'] : '',
            'EmailAgree' => (int) !empty($additionalFields['sendEmail']),
            'SmsAgree' => (int) !empty($additionalFields['sendSMS']),
            'CustomParams' => !empty($additionalFields['ext_profile']['customParams']) ? substr((string) $additionalFields['ext_profile']['customParams'], 0, 250) : '',
            'OriginalPassword' => property_exists($userData, 'originalPassword') ? $userData->originalPassword : '',
            'IsApiCall' => Api::isApiCall(),
            'PEP' => $extProfile['pep'] ?? false,
            'fromPublicAccount' => isset($additionalFields['fromPublicAccount']) ? $additionalFields['fromPublicAccount'] : false,
            'bonusIdPublicAccount' => isset($additionalFields['bonusIdPublicAccount']) ? $additionalFields['bonusIdPublicAccount'] : 0,
            'termsOfService' => $additionalFields['termsOfService'] ?? $userData->additional_fields['termsOfService'] ?? null,
            'CPF' => !empty($additionalFields['cpf']) ? str_replace(['.', '-'], '', $additionalFields['cpf']) : null,
        ];

        if (isset($extProfile['nick'])) $params['Nick'] = $extProfile['nick'];

        if (isset($userData->sendWelcomeEmail)) {
            $params['SendWelcomeEmail'] = (bool)$userData->sendWelcomeEmail;
        }

        $url .= '&' . http_build_query($params);

        return $this->runFundistAPI($url);
    }

    public function finishRegistration($uid, $data = array(), $skip_login = false, $fast_registration = false, $loginType = self::LOGIN_TYPE_DEFAULT)
    {
        // @codeCoverageIgnoreStart
        if (!empty($data['email']) || !empty($data['login'])) {
            $where = [];

            if (!empty($data['email'])) {
                $where[] = 'u.`email` = "' . Db::escape($data['email']) . '"';
            }

            if (!empty($data['login'])) {
                $where[] = 'u.`login` = "' . Db::escape($data['login']) . '"';
            }

            $userReal = Db::fetchRow('SELECT u.*, u.currency AS currencySign, ud.* '.
                'FROM users AS u '.
                'LEFT JOIN users_data ud '.
                'ON ud.user_id = u.id '.
                'WHERE ' . implode(' AND ', $where) . ' ' .
                'ORDER BY u.id DESC '.
                'LIMIT 1'
            );
        }
        // @codeCoverageIgnoreEnd

        if (empty($userReal)) {
            $userDbData = Db::fetchRow('SELECT `u`.*, `u`.`currency` AS `currencySign` ' .
                'FROM `users_temp` AS `u` ' .
                'WHERE `u`.`id` = ' . (int)$uid
            );

            if (isset($_SERVER['TEST_RUN']) && $_SERVER['TEST_RUN']) {
                $userDbData = $data['userMock'];
            }

            if (empty($userDbData)) {
            	return false;
            }
            unset($userDbData->id);

            $additional_fields = json_decode($userDbData->additional_fields, 1);

            // if this code executed, it means user click url in register mail
            // or fast registration
            if (empty($additional_fields['finishRegistrationFlag'])) {
                $additional_fields['finishRegistrationFlag'] = true;
                Db::query('UPDATE users_temp
                       SET additional_fields = "' . Db::escape(json_encode($additional_fields)).'"
                       WHERE id = ' . (int)$uid);
            }

            unset($additional_fields['finishRegistrationFlag']);

            $userDataFields = ['sex','birth_day','birth_month','birth_year'];
            foreach($userDataFields as $userDataField) {
                $userDbData->$userDataField = !empty($additional_fields[$userDataField]) ? $additional_fields[$userDataField] : '';
                unset($additional_fields[$userDataField]);
            }

            $hash = !empty($additional_fields['uhash']) ? $additional_fields['uhash'] : '';
            unset($additional_fields['uhash']);
            $userDbData->additional_fields = json_encode( $this->clearAdditionalFields($additional_fields) );

            Db::query('START TRANSACTION');

            //WLC defined hook to specific manipulation by user data
            $userData = System::hook('user:registration:finish', $userDbData);
            if(!empty($userData)) {
                $userDbData = $userData;
            }

            if(Api::isApiCall() && isset($data['ip'])) {
                $userDbData->reg_ip = $data['ip'];
            }

            //Old user defined registration check, now use System::hook('user:finishregistration', $data)
            //TODO: Remove that condition after move all wlc userland functions to hook
            if (function_exists('userTempToReal')) {
                $userDbData->id = userTempToReal($userDbData);

                // Some casinos doesn't have implementation of reg_ip like CasinoVino (look for userTempToReal() in plugins/functions.php)
                // So it's better just to update registration IP instead of making SELECT query before
                Db::query('UPDATE users SET '.
                    'reg_ip = "'.Db::escape($userDbData->reg_ip).'" '.
                    'WHERE id = '.$userDbData->id
                );
            } else {
                //if set id field, then hook already save user data in database
                if(empty($userDbData->id)) {
                    // @codeCoverageIgnoreStart
                    if (!empty($data['finger_print'])) {
                        $additional_fields = json_decode($userDbData->additional_fields, 1);
                        if ($additional_fields['finger_print'] != $data['finger_print']) {
                            $additional_fields['finger_print'] = $data['finger_print'];
                            $userDbData->additional_fields = json_encode($additional_fields);
                        }
                    }
                    // @codeCoverageIgnoreEnd
                    //if hook not save user data, then save it
                    $res = Db::query('INSERT INTO `users` SET ' .
                        '`api_password` = "' . Db::escape($userDbData->api_password) . '", ' .
                        '`password` = "' . Db::escape($userDbData->password) . '", ' .
                        '`first_name` = "' . Db::escape($userDbData->first_name) . '", ' .
                        '`last_name` = "' . Db::escape($userDbData->last_name) . '", ' .
                        '`login` = "' . Db::escape($userDbData->login) . '", ' .
                        '`email` = "' . Db::escape($userDbData->email) . '", ' .
                        '`currency` = "' . Db::escape($userDbData->currency) . '", ' .
                        '`status` = -1 , ' .
                        '`country` = "' . Db::escape($userDbData->country) . '", ' .
                        '`phone1` = "' . Db::escape($userDbData->phone1) . '", ' .
                        '`phone2` = "' . Db::escape($userDbData->phone2) . '", ' .
                        '`reg_ip` = "' . Db::escape($userDbData->reg_ip) . '", ' .
                        '`reg_time` = "' . Db::escape($userDbData->reg_time) . '", ' .
                        '`email_verification_code` = "' . Db::escape($userDbData->code ?? '') . '", ' .
                        '`additional_fields` = "' . Db::escape($userDbData->additional_fields) . '"'
                    );

                    if ($res === false) {
                        return _('Registration confirmation is in progress');
                    }

                    $userDbData->id = Db::lastId();

                    Db::query('INSERT INTO `users_data` SET ' .
                        '`user_id` = "' . $userDbData->id . '", ' .
                        '`sex` = "' . Db::escape($userDbData->sex) . '", ' .
                        '`sms_notification` = 1 , '.
                        '`email_notification` = 1 , '.
                   	   	'`birth_day` = "' . Db::escape((int) $userDbData->birth_day) . '", ' .
                   	   	'`birth_month` = "' . Db::escape((int) $userDbData->birth_month) . '", ' .
                   	   	'`birth_year` = "' . Db::escape((int) $userDbData->birth_year) . '"'
                    );
                }
            }
        } else {
            $userDbData = $userReal;
            $additional_fields = json_decode($userDbData->additional_fields, 1);
        }
        $fastRegistrationWithoutBets = (bool) _cfg('fastRegistrationWithoutBets');

        $this->confirmationCode($userDbData->email, !$fastRegistrationWithoutBets);

        if(!empty($data['social']) && !empty($data['social_uid'])) {
            $social = $data['social'];
            $socialUid = $data['social_uid'];
        } else if(!empty($additional_fields['social']) && !empty($additional_fields['social_uid'])) {
            $social = $additional_fields['social'];
            $socialUid = $additional_fields['social_uid'];
        }

        if (!empty($data['original_password'])) {
            $userDbData->originalPassword = $data['original_password'];
        }

        if (isset($data['sendWelcomeEmail'])) {
            $userDbData->sendWelcomeEmail = $data['sendWelcomeEmail'];
        }

        if(isset($social)) {
            Db::query(
                'INSERT INTO `social` SET ' .
                '`social` = "' . Db::escape($social) . '", ' .
                '`social_uid` = "' . Db::escape($socialUid) . '", ' .
                '`user_id` = ' . $userDbData->id
            );

            Db::query(
                'UPDATE `users_data` SET ' .
                '`social_' . Db::escape($social) . '` = 1 WHERE' .
                '`user_id` = ' . $userDbData->id
            );
        }

        if (php_sapi_name() != "cli" && $fastRegistrationWithoutBets) {
            $this->sendConfirmationEmail($data, $fast_registration);
        }

        Db::query('COMMIT');
        $additional_fields['uhash'] = $hash;
        $response = $this->prepareAndSendRegistrationToFundistAPI($userDbData, $additional_fields);

        $success = ($response == 1);

        if($success) {
            Db::query('UPDATE users SET '.
                'status = 1 '.
                'WHERE id = '.$userDbData->id
            );
            // @codeCoverageIgnoreStart
            if (!$fastRegistrationWithoutBets) {
                Db::query('DELETE FROM `users_temp` ' .
                  'WHERE `email` = "' . Db::escape($userDbData->email) . '"'
                );
            }

            if(_cfg('trustDevicesEnabled') === TrustDevice::$STATUS_TRUSTED || _cfg('trustDevicesEnabled') === true) {
                $trustDeviceService = new TrustDevice();
                $configData = new TrustDeviceConfiguration(
                    $userDbData->id,
                    $userDbData->email,
                    '',
                    new \DateTime()
                );

                $trustDeviceService->registerNewDevice($configData);
            }

            // @codeCoverageIgnoreEnd
            System::hook('user:registration:finished', $userDbData);
        }

        if(!$success && !$_SERVER['TEST_RUN']) {
            if (str_contains($response, 'Email blacklisted')) {
                $response = str_replace('Email blacklisted', 'Registration error', $response);
            }

            return str_replace(',', ';', $response);
        }

        if (_cfg('enableAmlCheck')) {
            $status = (new KycAml())->amlResult($userDbData);

            if (!empty($_POST['isApiCall'])) {
                return $userDbData;
            }

            if ($status === KycAml::STATUS_FAILED) {
                throw new ApiException('AML verification declined', 400);
            }

            if ($status !== KycAml::STATUS_COMPLETED) {
                throw new ApiException('AML verification in progress', 400);
            }
        }

        //social networks login
        if (!$skip_login) //&& isset($data['social_uid']) )
        {
            $this->authorizeUser($userDbData, isset($data['phone_verified']), $loginType, $fast_registration);
        }

        error_log("Successful Registration. IDUser: {$userDbData->id}");

        return $userDbData;
    }

    public function authorizeUser($userDbData, bool $isPhoneVerified, string $loginType = self::LOGIN_TYPE_DEFAULT, $fast_registration = false): void
    {
        $loginData = [
            'login' => $userDbData->email,
            'relogin' => 1, // ignore password check at registration complete
        ];

        if ($fast_registration === true) {
            $loginData['login'] = $userDbData->login;
        }

        if ($loginType === self::LOGIN_TYPE_METAMASK) {
            $loginData['walletAddress'] = $userDbData->login;
        } elseif ($loginType === self::LOGIN_TYPE_SMS) {
            $loginData['phoneCode'] = $userDbData->phone1;
            $loginData['phoneNumber'] = $userDbData->phone2;
        }

        $this->login($loginData, true, $loginType, false, false, $fast_registration);
        if ($isPhoneVerified && isset($userDbData->phone1) && isset($userDbData->phone2)) {
            $user = new User($userDbData->id);
            $user->verifyUser($userDbData->phone1, $userDbData->phone2);
        }
    }

    public function confirmationCode(?string $email = null, bool $needDelete = true): void
    {
        $email = Db::escape($email ?? $this->userData->email);

        if ($needDelete) {
            Db::query(
              'DELETE FROM `users_temp` ' .
              'WHERE `email` = "' . $email . '"'
            );
        }
        $this->authorizeUser($this->userData, isset($this->userData->phone_verified));
    }

    /**
     * Continue unsuccessful registration #3525
     * @param $uid user id
     */
    public function cronFinishRegistration($uid)
    {
        $userDbData = Db::fetchRow('SELECT `u`.*, `u`.`currency` AS `currencySign` ' .
            'FROM `users_temp` AS `u` ' .
            'WHERE `u`.`id` = ' . (int)$uid
        );

        if (!$userDbData) return;

        $additional_fields = json_decode($userDbData->additional_fields, 1);
        if (isset($additional_fields['finishRegistrationFlag']) || _cfg('fastRegistration')) {
            $answer = $this->finishRegistration($uid, (array)$userDbData, true);
        }

        // delete temporary user after 48 hour (unconfirmed email / total unsuccessful finish registration)
        if ( (time()-(60*60*48)) > strtotime($userDbData->reg_time) ) {
            Db::query('DELETE FROM `users_temp` ' .
                'WHERE `id` = ' . (int)$uid
            );
        }
    }

    /**
     * Retry user registration requests to FundistAPI
     * if they were unsuccessful #5985
     */
    public function cronRetryFundistUserRegistrationRequests()
    {
        $erroredUsers = Db::fetchRows('SELECT u.*, u.currency AS currencySign, ud.* '.
            'FROM users AS u '.
            'LEFT JOIN users_data ud '.
            'ON ud.user_id = u.id '.
            'WHERE u.status = -1'
        );

        if(!empty($erroredUsers)) {
            foreach($erroredUsers as $user) {
                $additionalFields = json_decode($user->additional_fields, true);

                $response = $this->prepareAndSendRegistrationToFundistAPI($user, $additionalFields);
                echo "cronRetryRegistration: {$user->id}: ".$response."<br/>\n";
                if($response == 1) {
                    Db::query('UPDATE users SET '.
                        'status = 1 '.
                        'WHERE id = '.$user->id
                    );

                    if(!empty($additionalFields['social'])) {
                        $this->sendSocialRegistrationCompletedEmail(array(
                            'firstName' => $user->first_name,
                            'lastName' => $user->last_name,
                            'email' => $user->email,
                            'socialKey' => $additionalFields['social']
                        ));
                    }
                } elseif ($response === '0,User already exists') {
                    Db::query('UPDATE `users` SET `status` = -2 WHERE `id` = ' . (int)$user->id);
                }
            }
        }
    }

    /**
     * Get Trading URL or Parameters based on $mode
     *
     * @param class $user
     * @param string $mode (html, json, url)
     * @return string|mixed
     */
    public function generateTradingUrl($user, $mode = '')
    {
        $tradingUrl = false;
        $attempts = 0;
        $response_code = -1;

        do {
            $authMethod = ($mode == 'html') ? 'AuthHTML' : 'DirectAuth';
            $url = '/User/'.$authMethod.'/?&Login=' . $user->id;

            $transactionId = $this->getApiTID($url, $user->id);

            $hash = md5('User/'.$authMethod.'/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $user->id . '/' . $user->api_password . '/' . _cfg('spotOptionId') . '/' . _cfg('fundistApiPass'));

            $params = array(
                'TID' => $transactionId,
                'Password' => $user->api_password,
                'System' => _cfg('spotOptionId'),
                'Hash' => $hash,
                'Page' => 1,
                'Language' => _cfg('language'),
                'Timezone' => (isset($user->timezone) ? $user->timezone : '0'),
                'UserIP' => System::getUserIP(true),
                'UserAgent' => $_SERVER['HTTP_USER_AGENT']
            );

            if ($mode == 'json') {
                $params['UniversalLaunch'] = 1;
            }

            $url .= '&' . http_build_query($params);

            $response = $this->runFundistAPI($url);
            $response_parts = explode(',', $response, 2);
            $response_code = $response_parts[0];

            $attempts++;

        } while ($attempts < 3 && $response_code !== '1');

        if ($response_code === '1')
            $tradingUrl = $response_parts[1];
        else
            $tradingUrl = intval($response_code);

        $url_base = _cfg('tradingProxy');
        if (empty($url_base)) {
            $url_base = _cfg('frameLink');
        }

        $urlParts = parse_url($tradingUrl);
        $tradingUrl = $url_base . '/' . _cfg('language') . ((isset($urlParts['query'])) ? '?' . $urlParts['query'] : '');

        return $tradingUrl;
    }

    //must be deprecated in some time in the future, for now it should stay
    //users now have string value on new registration and only old profiles have currency "number"
    protected function fetchCurrency($id)
    {
        //Sometimes table doesn't exist and it key=>value all the same, so moving this to array.
        $currencies = array(
            1 => 'EUR',
            2 => 'RUB',
            3 => 'USD',
            4 => 'GBP',
        );

        if (!isset($currencies[$id]) || !$currencies[$id]) {
            return 'Error: Currency not found';
        }

        return $currencies[$id];
    }

    public function registerDB($data, $fastRegistration = false) {
        $userId = null;

        $data['userApiPass'] = substr(sha1(rand(0, 99999)), 0, 8);

        $fields = [
            'password',
            'firstName',
            'lastName',
            'email',
            'currency',
            'country',
            'code',
            'pre_phone',
            'main_phone'
        ]; // mandatory fields, filling up if empty (BUT SHOULD BE ERRORED!)

        foreach ($fields as $v) {
            if (!isset($data[$v])) {
                $data[$v] = '';
            }
        }

        $data = array_merge($data, Affiliate::getAffiliateData());
        $data['reg_ip'] = System::getUserIP();

        //Old user defined registration check, now use System::hook('user:registerdb', $data)
        //TODO: Remove that condition after move all wlc userland functions to hook
        if (function_exists('userDataRegisterDB')) {
            $userId = userDataRegisterDB($data);

            // Some casinos doesn't have implementation of reg_ip like CasinoVino (look for userTempToReal() in plugins/functions.php)
            // So it's better just to update registration IP instead of making SELECT query before
            Db::query('UPDATE users_temp SET '.
                'reg_ip = "'.Db::escape(System::getUserIP()).'" '.
                'WHERE id = '.$userId
            );
        } else {
            //if function not exist, use default registration

            $additionalFields = [
                'affiliateSystem' => !empty($data['affiliateSystem']) ? DB::escape($data['affiliateSystem']) : '',
                'affiliateId' => !empty($data['affiliateId']) ? DB::escape($data['affiliateId']) : '',
                'affiliateClickId' => !empty($data['affiliateClickId']) ? DB::escape($data['affiliateClickId']) : '',
            ];

            if (isset($data['nick'])) {
                $additionalFields['ext_profile'] = ['nick' => $data['nick']];
                unset($data['nick']);
            }

            if(isset($data['termsOfService'])){
                $additionalFields['termsOfService'] = $data['termsOfService'];
                unset($data['termsOfService']);
            }

            if(isset($data['finger_print'])){
                $additionalFields['finger_print'] = $data['finger_print'];
                unset($data['finger_print']);
            }

            foreach(array_keys($data) as $dataKey) {
                if (in_array($dataKey, $fields)) {
                    continue;
                }

                $additionalFields[$dataKey] = $data[$dataKey];
            }

            [
                'sendSMS' => $additionalFields['sendSMS'],
                'sendEmail' => $additionalFields['sendEmail']
            ] = self::getUserAgreement($data);

            $additionalFields = array_merge($additionalFields, $this->processRegisterCheckbox($data));

            $data['phone1'] = $data['phone2'] = '';
            if (!empty($data['pre_phone']) && is_string($data['pre_phone'])) {
                $data['phone1'] = $data['pre_phone'];
            }

            if (!empty($data['main_phone']) && is_string($data['main_phone'])) {
                $data['phone2'] = $data['main_phone'];
            }

            //hook for WLC specific manipulation by user data
            //hook must return array
            //if is set 'id' array field, this mean that user data already saved in database
            $userData = System::hook('user:registerdb', $data);
            if(!empty($userData)) {
                $data = $userData;
            }
            //if set 'id' array key, then hook already save user data
            if(empty($data['id'])) {

                if (empty($data['lastName'])) {
                    $name_parts = explode(' ', $data['firstName'], 2);
                    $data['firstName'] = $name_parts[0];
                    $data['lastName'] = !empty($name_parts[1]) ? $name_parts[1] : '';
                }
                Db::query('INSERT INTO `users_temp` SET ' .
                    '`api_password` = "' . Db::escape($data['userApiPass']) . '", ' .
                    '`password` = "' . Db::escape($data['password']) . '", ' .
                    '`first_name` = "' . Db::escape($data['firstName']) . '", ' .
                    '`last_name` = "' . Db::escape($data['lastName']) . '", ' .
                    '`login` = "' . Db::escape(!empty($data['login']) ? $data['login'] : '') . '", ' .
                    '`email` = "' . Db::escape($data['email']) . '", ' .
                    '`phone1` = "' . Db::escape($data['phone1']) . '", ' .
                    '`phone2` = "' . Db::escape($data['phone2']) . '", ' .
                    '`code` = "' . Db::escape($data['code']) . '", ' .
                    '`currency` = "' . Db::escape($data['currency']) . '", ' .
                    '`country` = "' . Db::escape($data['country']) . '", ' .
                    '`reg_ip` = "' . Db::escape(System::getUserIP()) . '", ' .
                    '`reg_time` = NOW(), ' .
                    '`reg_site` = "' . Db::escape(_cfg('site')) . '", ' .
                    '`reg_lang` = "' . Db::escape(_cfg('language')) . '", ' .
                    '`additional_fields` = "' . Db::escape(json_encode( ($fastRegistration || _cfg('dontClearAdditionalFields') == 1) ? $additionalFields : $this->clearAdditionalFields($additionalFields, 'registration') )) . '" '
                );

                $userId = Db::lastId();
            } else {
                $userId = $data['id'];
            }
        }

        return $userId;
    }

    public function isUser($check_user_object = true)
    {
        if (!empty($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['id']) &&
            $_SESSION['user']['id'] != 0 && (!$check_user_object || !empty($this->userData->id)))
        {
            return true;
        }

        return false;
    }

    static function isAuthenticated() {
    	return self::getInstance()->isUser(false);
    }

    private function socialConnect($data)
    {
        if (empty($this->userData->first_name) || empty($this->userData->last_name)) {
            $fullName = $this->getFullNameSocial($data);

            if (!empty($fullName)) {

                $firstName = !empty($this->userData->first_name)
                    ? $this->userData->first_name
                    : $fullName['firstName'];

                $lastName = !empty($this->userData->last_name)
                    ? $this->userData->last_name
                    : $fullName['lastName'];

                DB::query(
                    'UPDATE `users` SET ' .
                    '`first_name` = "' . Db::escape($firstName) . '", ' .
                    '`last_name` = "' . Db::escape($lastName) . '" ' .
                    'WHERE `id` = ' . (int)$this->userData->id
                );
            }
        }

        $row = Db::fetchRow(
            'SELECT `id` FROM `social` ' .
            'WHERE `social` = "' . Db::escape($data['social']) . '" AND ' .
            '`social_uid` = "' . Db::escape($data['social_uid']) . '" ' .
            'LIMIT 1'
        );

        if ( !$row ) {
            Db::query(
                'INSERT INTO `social` SET ' .
                '`social` = "' . Db::escape($data['social']) . '", ' .
                '`social_uid` = "' . Db::escape($data['social_uid']) . '", ' .
                '`user_id` = ' . (int)$_SESSION['user']['id']
            );
            Db::query(
                'UPDATE `users_data` SET ' .
                '`social_' . Db::escape($data['social']) . '` = 1 WHERE' .
                '`user_id` = ' . (int)$_SESSION['user']['id']
            );
        }

        #3957 - Авторизация через соц сети - seems to be some "exit" is legacy code
        //exit('<script>window.opener.location.reload(false); window.close()</script>');
        $redirUrl = _cfg('site') . '/' . _cfg('language');
        $redirParams = _cfg('socialConnectParams');
        if ($redirParams != '') {
            $redirParams = str_replace('#social#', $data['social'], $redirParams);
        }
        header('Location: ' . $redirUrl . $redirParams);
        die();
    }

    private function socialRegister($data)
    {

        if (($fraud = $this->checkIP()) != 1) {
            return isset($fraud['ip']) ? array(
                'ip' => $fraud['ip'],
                'timeout' => $fraud['timeout']
            ) : $fraud;
        }

        $social = $data['social'];
        unset($data['social']);

        $data['social_network_code'] = $social;
        $data['social_network'] = Social::getName($social);

        $user = $this->register($data);
        if ($user !== true) {
            return $user;
        }

        $row = Db::fetchRow('SELECT * FROM `users` WHERE ' .
            '`email` = "' . Db::escape($data['email']) . '" '
        );

        if ($row == false || empty($row)) {
            return json_encode(
                array(
                    'error' => array('main' => 'auth error ' . __LINE__)
                )
            );
        }

        Db::query(
            'INSERT INTO `social` SET ' .
            '`social` = "' . Db::escape($social) . '", ' .
            '`social_uid` = "' . Db::escape($data['social_uid']) . '", ' .
            '`user_id` = ' . $row->id
        );

        $_SESSION['user'] = (array)$row;

        return 1;
    }

    /**
     * Use User::loginWithSocialAccount()
     * @deprecated
     * @param $user
     * @return bool|string
     */
    public static function socialLogin($user)
    {
        if (!isset($user['social']) || !isset($user['social_uid'])) {
            return false;
        }

        $row = Db::fetchRow(
            'SELECT `S`.`id` AS `sid`, `S`.`social_uid` AS `uid`, `U`.* ' .
            'FROM `social` AS `S` ' .
            'LEFT JOIN `users` AS `U` ON (`S`.`user_id` = `U`.`id`) ' .
            'WHERE `S`.`social` = "' . Db::escape($user['social']) . '" AND ' .
            '`S`.`social_uid` = "' . Db::escape($user['social_uid']) . '" '
        );

        if ($err = Db::error()) {
            if (_cfg('env') == 'dev') {
                return $err;
            }

            return false;
        }

        if ($row || isset($_SESSION['user'])) {
            $u = new self;

            if (isset($_SESSION['user']) && $_SESSION['user']) {
                $u->socialConnect($user);

                return true;
            } else {
                if (!$u->fundist_uid($row->id, 'check_status')) {
                    return array('error' => _('user_disabled'));
                }

                $row->tradingURL = false;
                if (_cfg('enableSpotOption') == 1) {
                    $row->tradingURL = $u->generateTradingUrl($row);
                }

                $_SESSION['user'] = (array)$row;

                header('Location: ' . _cfg('site') . '/' . _cfg('language'));
                exit();//_cfg('site').'/'._cfg('language')
            }
        } else {
            $_SESSION['socialRegistration'] = $user;

            return true;
        }
    }

    public static function loginWithSocialAccount($user)
    {
        if (!isset($user['social']) || !isset($user['social_uid'])) {
            return false;
        }

        $row = Db::fetchRow(
            'SELECT `S`.`id` AS `sid`, `S`.`social_uid` AS `uid`, `U`.* ' .
            'FROM `social` AS `S` ' .
            'INNER JOIN `users` AS `U` ON (`S`.`user_id` = `U`.`id`) ' .
            'WHERE `S`.`social` = "' . Db::escape($user['social']) . '" AND ' .
            '`S`.`social_uid` = "' . Db::escape($user['social_uid']) . '" '
        );

        if ($err = Db::error()) {
            if (_cfg('env') == 'dev') {
                throw new \Exception($err, 400);
            }

            return false;
        }

        if ($row || !empty($_SESSION['user'])) {
            $u = new self;

            if (!empty($_SESSION['user'])) {
                $u->socialConnect($user);
            } else {
                if (!$u->fundist_uid($row->id, 'check_status')) {
                    throw new \Exception('user_disabled', 400);
                }

                $row->tradingURL = false;
                if (_cfg('enableSpotOption') == 1) {
                    $row->tradingURL = $u->generateTradingUrl($row);
                }

                CookieStorage::getInstance()->set('just_login', true);
                Core::getInstance()->sessionStart(true, _cfg('useJwtSocialAuth') == 1);
                $_SESSION['user'] = (array)$row;
            }
        } else {
            CookieStorage::getInstance()->set('social', $user);
        }
        return true;
    }

    public static function socialDisconnect($data)
    {
        $u = new self;
        $user = $u->checkUser();

        if (!$user->id) {
            return _('not_logged_in');
        }

        $row = Db::fetchRow('SELECT COUNT(`id`) AS `count` FROM `social` WHERE `user_id` = ' . (int)$user->id);

        if ($row->count <= 1) {
            $err = 0;
            //User trying to delete last socials, checking if mail/pass is set
            $msg = _('trying_delete_last_social');

            $row = Db::fetchRow('SELECT `password`, `email` FROM `users` WHERE `id` = ' . (int)$user->id);
            if (!trim($row->email)) { //if email is not set, it must, because it's a login itself
                $err = 1;
                $msg .= '<br />' . _('please_set_email');
            }

            if (substr($row->password, 0,
                    6) == 'social'
            ) { //if password is not set, it must, because it's required to login
                $err = 1;
                $msg .= '<br />' . _('please_set_password');
            }

            if ($err == 1) {
                return $msg;
            }
        }

        Db::query(
            'DELETE FROM `social` WHERE ' .
            '`social` = "' . Db::escape($data['provider']) . '" AND ' .
            '`user_id` = ' . (int)$user->id
        );
        Db::query(
            'UPDATE `users_data` SET ' .
            '`social_' . Db::escape($data['provider']) . '` = 0 WHERE' .
            '`user_id` = ' . (int)$user->id
        );

        return true;
    }

    /**
     * Checks if provided promo code is valid in loyalty system
     *
     * @param $code
     * @param $currency
     * @param $language
     * @param $loyalty_level
     * @return bool
     */
    public function isValidPromoCode($code, $currency, $language, $loyalty_level, $country = '')
    {
        if (!$country) {
            $country = System::getGeoData();
        }

        $path = 'Bonuses/Codes/IsValid';
        $aff_data = Affiliate::getAffiliateData();
        if ($aff_data['affiliateSystem'] == 'affilka') {
            $aff_data = [];
        }
        $params = [
            'Code' => $code,
            'Currency' => $currency,
            'Country' => $country,
            'Language' => $language,
            'Level' => $loyalty_level,
            'AffiliateSystem' => !empty($aff_data['affiliateSystem']) ? $aff_data['affiliateSystem'] : '',
            'AffiliateUrl' => !empty($aff_data['affiliateId']) ?
                $aff_data['affiliateId'] . (empty($aff_data['affiliateClickId']) ? '' : ('&'.$aff_data['affiliateClickId']))
                : ''
        ];

        if (_cfg('websiteName') == self::SKIP_AFFILIATE_CHECK_SITE) {
            unset($params['AffiliateSystem'], $params['AffiliateUrl']);
        }

        if (_cfg('setAffiliateCookieByPromoCode')) {
            $params['GetAffiliateData'] = true;
        }

        // @codeCoverageIgnoreStart
        $hookData = System::hook('user:affiliate:preparation_data', $params);

        if ($hookData !== null) {
            $params = (array) $hookData;
        }
        // @codeCoverageIgnoreEnd

        $isValid = false;

        try {
            $response = $this->runLoyaltyAPI($path, $params);
            $response = json_decode($response, true);
            if (_cfg('setAffiliateCookieByPromoCode') && !empty($response['AffiliateSystem']) &&
                !empty($response['AffiliateUrl'])) {
                if ($response['AffiliateSystem'] == 'affilka') {
                    $decoded = json_decode($response['AffiliateUrl'], JSON_OBJECT_AS_ARRAY);
                    if ($decoded !== null) {
                        $url = explode("&&", $decoded['Url'], 2);
                        Affiliate::setGlobalAffiliateCookie($response['AffiliateSystem'], $url[0], $response['AffiliateUrl']);
                        $_COOKIE['_aff'] = Affiliate::$_aff_cookie;
                    }
                } else {
                    $id = Affiliate::getAffiliateIdByUrl($response['AffiliateUrl']);
                    if ($id !== null) {
                        Affiliate::setGlobalAffiliateCookie($response['AffiliateSystem'], $id, $response['AffiliateUrl']);
                        $_COOKIE['_aff'] = Affiliate::$_aff_cookie;
                    }
                }
            }
            $isValid = $response['isValid'];
        } catch (\Exception $ex) { }

        if (!empty($_SERVER['TEST_RUN']) && $code == 'TESTPROM0CODE1') {
            $isValid = true;
        }

        return $isValid;
    }


    /**
     * Returns user`s profile data by user id.
     *
     * @param $user_id
     * @return bool
     */
    public static function getProfileData($user_id)
    {
        $profileData = Db::fetchRow('SELECT u.*, ud.* ' .
            'FROM users AS u ' .
            'LEFT JOIN users_data AS ud ON (u.id = ud.user_id) ' .
            'WHERE u.id = "' . Db::escape($user_id) . '" '
        );

        if ($profileData === false) {
            return false;
        }

        return $profileData;
    }

    /**
     * Check required fields using config
     *
     * @param array $config
     * @return bool
     * @throws ApiException
     */
    public function checkRequiredFields(array $config): bool
    {
        $profile = (array) $this->userData;
        if (isset($profile['additional_fields'])) {
            $profile = array_merge($profile, json_decode($profile['additional_fields'], true));
        }

        if (!empty($profile['birth_day']) && !empty($profile['birth_month']) && !empty($profile['birth_year'])) {
            $profile['DateOfBirth'] = sprintf("%04d-%02d-%02d", (int)$profile['birth_year'],
                (int)$profile['birth_month'], (int)$profile['birth_day']);
        }

        $profile = User::transformProfileData($profile, false);

        $fields = array_filter($profile, function ($v, $k) use ($config) {
            if (in_array($k, $config)) {
                return empty($v);
            }
        }, ARRAY_FILTER_USE_BOTH);

        unset($fields['stateCode']);

        if (!isset($fields['countryCode']) && empty($profile['stateCode'])
            && array_key_exists($profile['countryCode'], States::getStatesList())) {
            $fields['stateCode'] = '';
        }

        $fields = array_keys($fields);

        foreach ($fields as &$v) {
            $v = _($v);
        }

        if (!empty($fields)) {
            throw new ApiException(_('FieldsEmpty') . implode(', ', $fields), 400);
        }

        return true;
    }
    /**
     * Checking fields that are not allowed to be edited
     *
     * @param array $data
     * @param array $fields
     * @return bool
     * @throws ApiException
     */
    public function checkForbiddenFieldsForEditing($data, $fields = []){
        $errors = [];

        $profile = (array) $this->userData;
        $profile = array_merge($profile, json_decode($profile['additional_fields'], true, 512, JSON_THROW_ON_ERROR));
        if (!empty($profile['birth_day']) && !empty($profile['birth_month']) && !empty($profile['birth_year'])) {
            $profile['DateOfBirth'] = sprintf("%04d-%02d-%02d", (int)$profile['birth_year'],
                (int)$profile['birth_month'], (int)$profile['birth_day']);
        }
        $profile = self::transformProfileData($profile, false);

        $cfgFields = array_merge(_cfg('fieldsForbiddenForEditing') ?: [], $fields);

        foreach ($cfgFields as $cfg_key) {
            foreach ($data as $data_key => $data_value) {
                if($cfg_key === $data_key && $data_value != $profile[$data_key])
                {
                    if($profile[$data_key] == ''){
                        continue;
                    }
                    if($data_value == $profile[$data_key]){
                        continue;
                    }
                    $errors[] = $cfg_key;
                }
            }
        }

        if (!empty($errors)) {
            $errors = array_map(function ($i) {
                $word = _($i.'Forbidden');
                return $word;
            }, $errors);

            $errors = [_("The following fields cannot be changed: ") . implode(', ', $errors)];
            throw new ApiException(false, 400, null, $errors);
        }

        return true;
    }

    /**
     * Maps fields' names from wlc-names to fundist to pass required fields list to front
     *
     * @param array $fields
     * @param string $fieldName
     *
     * @return array
     */
    private function mapRequiredFieldsName(array $fields, string $fieldName): array
    {
        $country = $fieldName == 'required' ? 'IDCountry' : 'Country';

        $map = [
            'Name' => 'firstName',
            'LastName' => 'lastName',
            'DateOfBirth' => 'dateOfBirth',
            'Address' => 'address',
            'City' => 'city',
            $country => 'countryCode',
            'Email' => 'email',
            'Phone' => 'phoneNumber',
            'IDNumber' => 'idNumber',
            'PostalCode' => 'postalCode',
            'Gender' => 'gender',
            'Swift' => 'swift',
            'BankName' => 'bankName',
            'Iban' => 'ibanNumber',
            'BranchCode' => 'branchCode'
        ];

        return array_keys(array_intersect($map, $fields));
    }

    /**
     * Adds required fields
     *
     * @param array $fieldsArray
     * @param string $fieldName
     * @return array
     * @throws \Exception
     */
    public function addRequiredFieldsResources(array $fieldsArray, string $fieldName): array
    {
        $userFields = $this->userData ? (array) $this->userData : '';
        $userFields = $userFields ? array_merge($userFields, json_decode($userFields['additional_fields'], true)) : '';

        $states = new \eGamings\WLC\States();

        foreach($fieldsArray as &$field) {
            $field[$fieldName] = array_values(array_unique(array_merge($field[$fieldName], $this->mapRequiredFieldsName(_cfg('requiredFieldsList'), $fieldName))));
        }

        if (!empty($userFields['country']) && empty($userFields['state']) && array_key_exists($userFields['country'], $states::getStatesList())) {
            foreach ($fieldsArray as &$field) {
                $field[$fieldName][] = 'IDState';
            }
        }
        return $fieldsArray;
    }

    /**
     * Returns user`s info (balance, loyalty) by user login.
     *
     * @param $login
     * @param {boolean} [$fromCache=false]
     * @return array
     * @throws \Exception
     */
    public static function getInfo($login, $fromCache=true)
    {
        if ($fromCache) {
            $cachedInfo = SessionStorage::getInstance()->get(self::USER_INFO_CACHE_PREFIX . $login);
            if (!empty($cachedInfo)) {
                return $cachedInfo;
            }
        }

        static $userInfo = [];

        if (empty($login)) {
            return false;
        }

        if (!empty($userInfo[$login])) {
            return $userInfo[$login];
        }

        try {
            $url = '/WLCInfo/New?';
            //TODO: get rid of this instantiation, make System`s methods static if possible
            $system = System::$instance;
            $transactionId = $system->getApiTID($url);
            $info = array(
                'balance' => 0,
                'loyalty' => array(),
                'email' => ''
            );

            $hash = md5('WLCInfo/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));
            $params = array(
                'Login' => $login,
                'TID' => $transactionId,
                'Hash' => $hash,
                'UserIP' => System::getUserIP(),
            );

            $wallet = _cfg('wallet');

            $params['IDMerchant'] = isset($wallet['mode']) && $wallet['mode'] == 'separate' ? $wallet['default_merchant'] : 0;

            _cfg('getFullProfile') && $params['Additional'] = 1;

            $url .= '&' . http_build_query($params);

            $response = $system->runFundistAPI($url);
            $data = explode(',', $response, 2);

            if ($data[0] === '0') { throw new \Exception(_('Unknown status') . ' 0' . (!empty($data[1]) ? ':' . $data[1] : ''), 500); }
            if ($data[0] !== '1') { throw new \Exception(!empty($data[1]) ? 'Fundist API error. ' . $data[1] : _('Unknown error'), 400); }

            $data = json_decode($data[1], true);

            $profileData = array_filter((array)User::getProfileData($login), function ($fieldName) {
                return in_array($fieldName, ['first_name', 'last_name', 'email', 'status', 'additional_fields']);
            }, ARRAY_FILTER_USE_KEY);

            $additional = [];
            // @codeCoverageIgnoreStart
            if (!empty($profileData['additional_fields'])) {
                $additional = json_decode($profileData['additional_fields'], true);
                unset($profileData['additional_fields']);
                $info['firstSession'] = isset($additional['isFirstSession']) && $additional['isFirstSession'] == 1;
            }
            // @codeCoverageIgnoreEnd

            foreach ($profileData as $fieldName => $value) {
                $tmpFieldName = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $fieldName))));
                $info[$tmpFieldName] = $value;
            }

            $info['idUser'] = $data['IDUser'];
            $info['balance'] = (double)$data['Balance'];
            $info['availableWithdraw'] = (double)$data['availableWithdraw'];
            $info['currency'] = $data['Currency'] ?? '';
            $info['emailHash'] = md5(mb_strtolower($info['email']));
            $info['category'] = !empty($data['Category']) ? $data['Category'] : '';
            $info['freerounds'] = !empty($data['Freerounds']) ? $data['Freerounds'] : [];
            $info['status'] = !empty($profileData['status']) ? (int) $profileData['status'] : 0;
            $info['affiliateID'] = !empty($data['AffiliateID']) ? $data['AffiliateID'] : '';
            $info['LockExpiresAt'] = !empty($data['LockExpiresAt']) ? $data['LockExpiresAt'] : '';

            $notifyKYCQuestionnaire = $data['NotifyKYCQuestionnaire'] ?? false;

            $info['NotifyKYCQuestionnaire'] = $notifyKYCQuestionnaire;

            if (!empty($data['GlobalBlocked'])) {
                $info['status'] = 0;
            }

            $userTags = array_keys($data['Tags'] ?? []);
            if (isset($data['CategoryID']) && $data['CategoryID'] > 0 && !in_array($data['CategoryID'], $userTags)) {
                $userTags[] = $data['CategoryID'];
            }
            $_SESSION['user']['UserTags'] = implode(',', $userTags);

            if(!empty($data['OpenPositions']) && $data['OpenPositions'] !== 'none') {
                $info['openPositions'] = $data['OpenPositions'];
            }

            $info['pincode'] = '';
            if(!empty($data['Pincode'])){
                $info['pincode'] = $data['Pincode'];
            }

            if(_cfg('getFullProfile')){
                $info['additional'] = [];
                foreach(['LastPlayed', 'Deposits', 'Manager', 'Registered'] as $field){
                    $info['additional'][lcfirst($field)] = isset($data[$field])?$data[$field]:'';
                }
            }

            if (!empty($additional)) {
                if(!isset($additional['notify_2fa'])) {
                    $notify = false;
                } else {
                    $notify = (bool) $additional['notify_2fa'];
                }
                $redis = Core::DI()->get('redis');
                $redisKey = Auth2FAGoogle::buildRedisKey($info['email']);
                $secretKeyFromRedis = $redis->get($redisKey);

                if ($additional['enabled_2fa']) {
                    if (empty($secretKeyFromRedis) && $additional['secret_2fa'] == 'init') {
                        $enabled2FAGoogle = false;
                        $notify2FAGoogle = $notify ? true : false;
                    } else if(!empty($additional['secret_2fa']) && $additional['secret_2fa'] != 'init'){
                        $enabled2FAGoogle = true;
                        $notify2FAGoogle = false;
                    } else {
                        $enabled2FAGoogle = false;
                        $notify2FAGoogle = $notify ? true : false;
                    }
                } else {
                    $enabled2FAGoogle = false;
                    $notify2FAGoogle = $notify ? true : false;
                }
            } else {
                $notify2FAGoogle = false;
                $enabled2FAGoogle = false;
            }


            $info['loyalty'] = $data['Loyalty'];
            $info['loyalty']['LevelName'] = isset($info['loyalty']['LevelName']) ?
                json_decode($info['loyalty']['LevelName'], true) : [];
            $info['loyalty']['BonusRestrictions'] = isset($info['loyalty']['BonusRestrictions']) ?
                json_decode($info['loyalty']['BonusRestrictions'], true) : [];
            $info['loyalty']['BonusesBalance'] = isset($info['loyalty']['BonusesBalance']) ?
                (object)$info['loyalty']['BonusesBalance'] : (object)[];
            $info['socketsData'] = !empty($data['SocketsData']) ? $data['SocketsData'] : '';
            $info['userSessionLimit'] = !empty($data['UserSessionLimit']) ? $data['UserSessionLimit'] : false;
            $info['Tags'] = !empty($data['Tags']) ? $data['Tags'] : [];
            $info['validationLevel'] = $data['ValidationLevel'] ?? '';
            $info['streamWheelOwner'] = $data['StreamWheelOwner'] ?? 0;
            $info['streamWheelsParticipant'] = $data['StreamWheelsParticipant'] ?? [];

            $toSVersion = [
                'AcceptDateTime' => $data['ToSVersion']['AcceptDateTime'] ?? '',
                'ToSVersion' => $data['ToSVersion']['ToSVersion'] ?? '',
            ];

            if (!empty($data['ToSVersion']['ToSVersion']) && $data['ToSVersion']['ToSVersion'] != ($additional['termsOfService'] ?? '')) {
                User::saveTermsOfService($data['ToSVersion']['ToSVersion']);
            }

            $info['toSVersion'] = $toSVersion;

            $info['toSWlcVersion'] = _cfg('termsOfService') ?? null;
            $info['wallets'] = [];

            foreach ($data['Wallets'] ?? [] as $currency => $wallet) {
                $info['wallets'][$currency] = [
                    'walletId' => (int)$wallet['WalletId'],
                    'balance' => $wallet['Balance'],
                    'availableWithdraw' => $wallet['availableWithdraw'],
                    'currency' => $currency,
                    'freerounds' => $wallet['Freerounds'] ?? [],
                ];
            }
            $info['notify2FAGoogle'] = $notify2FAGoogle;
            $info['enabled2FAGoogle'] = $enabled2FAGoogle;
            $info['allowAccessToAddStreamWheel'] = $data['AllowAccessToAddStreamWheel'] ?? false;
        } catch (\Exception $ex) {
            throw $ex;
        }

        $userInfo[$login] = $info;

        SessionStorage::getInstance()->set(self::USER_INFO_CACHE_PREFIX . $login, $info);

        return $info;
    }

    /**
     * Get user fields (with passport) from Fundist
     *
     * @param array|null $user User account row (object stdClass)
     * @param bool $ignoreCache
     * @param bool $withLoyaltyUserInfo
     * @return string Fundist API response
     * @throws \Exception
     */
    public function getFundistUser($user = null, bool $ignoreCache = false, bool $withLoyaltyUserInfo = false)
    {
        static $userData = [];

        if (!$this->isUser($check_user_object = false)) {
            return '0,' . _('Session is expired');
        }

        if ($user) {
            $login = (int)$user->id;
        } else {
            $login = (int)$this->userData->id;
        }

        if (!$ignoreCache && !empty($userData[$login])) {
            return $login;
        }

        $user = (!empty($user)) ? $user : $this->userData;

        $getData = function() use ($login, $user, $withLoyaltyUserInfo) {
            $url = '/User/GetUserData/?&Login=' . $login;

            $transactionId = $this->getApiTID($url);

            $hash = md5('User/GetUserData/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));
            $params = [
                'Password' => $user->api_password,
                'TID' => $transactionId,
                'Hash' => $hash,
                'Login' => $login,
                'UserIP' => System::getUserIP(),
            ];

            if ($withLoyaltyUserInfo) {
                $params['WithLoyaltyUserInfo'] = true;
            }

            $url .= '&' . http_build_query($params);

            return $this->runFundistAPI($url);
        };

        $result = $ignoreCache
            ? $getData()
            : Cache::result('fundist_user_getuserdata', $getData, 30, ['User', 'getUserData', $login]);

        $userData[$login] = $result;

        return $result;
    }

    /**
     * @param bool $withLoyaltyUserInfo
     * @return array
     * @throws \Exception
     */
    public function getFundistUserAsArray(bool $withLoyaltyUserInfo = false): array
    {
        $data = $this->getFundistUser(null, true, $withLoyaltyUserInfo);
        $data = explode(',', $data, 2);
        if ($data[0] !== '1') {
            return [];
        }

        if (!is_array($data[1])) {
            $profileData = json_decode($data[1], true) ?: [];
        } else {
            $profileData = $data[1];
        }

        return $profileData;
    }

    /**
     * Update user fields in Fundist
     *
     * @param $data array
     * @return Fundist API response
     */
    public function updateFundistUser($data)
    {
        if (!$this->isUser()) {
            return '0,' . _('Session is expired');
        }

        $login = (int)$this->userData->id;
        $api_password = $this->userData->api_password;
        $currency = $this->userData->currency;

        $url = '/User/Update/?&Login=' . $login;

        $transactionId = $this->getApiTID($url);

        $hash = md5('User/Update/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . $api_password . '/' . $currency . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'Password' => $api_password,
            'Currency' => $currency,
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
            'AdditionalUserIP' => json_encode(System::getUserIP(true)),
        );
        $params = array_merge($params, $data);

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);

        if (_cfg('useFundistTemplate') == 1 && $this->isNeedProfileChangeMail($data, (array)$this->userData)) {
            $this->sendMailAfterUserUpdate($login, $api_password, $data, (array_key_exists('email', $data) && $this->userData->email != $data['email']));
        }

        System::hook('user:updatefundistuser:after', $response, $data);

        return $response;
    }

    /**
     * Update user fields in Fundist
     *
     * @param $data array
     * @return Fundist API response
     */
    public function verifyUser($phoneCode, $phoneNumber)
    {
        if (!$this->isUser()) {
            return '0,' . _('Session is expired');
        }

        $login = (int)$this->userData->id;
        $api_password = $this->userData->api_password;

        $url = '/User/PhoneVerify/?&Login=' . $login;

        $transactionId = $this->getApiTID($url);

        $hash = md5('User/PhoneVerify/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . $api_password . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'Password' => $api_password,
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
            'Phone' => $phoneCode . '-' . $phoneNumber
        );

        $url .= '&' . http_build_query($params);

        $response = $this->runFundistAPI($url);

        $res = explode(',', $response, 2);

        if ($res[0] == 1) {
            Db::query("UPDATE `users` SET
                `phone1` = '{$phoneCode}',
                `phone2` = '{$phoneNumber}'
                WHERE `id` = {$login}"
            );
            self::setPhoneVerified($login, 1, true);
        }

        return $res;
    }

    /**
     * Get Full Name user from data Social account
     * @param array $socialData
     * @return array
     */
    public function getFullNameSocial(Array $socialData)
    {
        $fullName = array();

        if (!empty($socialData)) {
            $nameParts = explode(' ', trim($socialData['firstName']));

            $fullName = [
                'firstName' => !empty($nameParts[0]) ? $nameParts[0] : '',
                'lastName' => !empty($socialData['lastName']) ? $socialData['lastName'] : (!empty($nameParts[1]) ? $nameParts[1] : '')
            ];
        }

        return $fullName;
    }

    public function logUserData($type, $data)
    {
        $row = [];
        if(!isset($_SESSION['token']))
            $_SESSION['token'] = uniqid();

        $row['ssid'] = $_SESSION['token'];
        $row['user_id'] = 0;
        if(isset($_SESSION['userData']))
            $row['user_id'] = $_SESSION['userData']->user_id;
        if(isset($_SESSION['user']) && isset($_SESSION['user']['id']))
            $row['user_id'] = $_SESSION['user']['id'];
        $row['ip'] = $this->getUserIP();
        $row['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

        Db::query('INSERT INTO `users_logs` SET ' .
            '`ssid` = "' . Db::escape($row['ssid']) . '", ' .
            '`user_id` = "' . Db::escape($row['user_id']) . '", ' .
            '`ip` = "' . Db::escape($row['ip']) . '", ' .
            '`user_agent` = "' . Db::escape($row['user_agent']) . '", ' .
            '`type` = "' . Db::escape($type) . '", ' .
            '`data` = "' . Db::escape($data) . '", ' .
            '`add_date` = NOW()'
        );
    }

    private function sendFundistMail($login, $apiPassword, $urlPart, $addParams) {
        $url = '/WLCAccount/SendMail/' . $urlPart . '?&Login=' . $login;
        $transactionId = $this->getApiTID($url);
        $hash = md5('WLCAccount/SendMail/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'Password' => $apiPassword,
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
        );
        $params = array_merge($params, $addParams);
        $url .= '&' . http_build_query($params);

        return $this->runFundistAPI($url);
    }

    /**
     * Mailing after updating user in Fundist
     *
     * @param $login
     * @param $api_password
     * @param array $data
     * @param boolean $emailWasChanged has email been changed?
     */
    public function sendMailAfterUserUpdate($login, $api_password, $data, $emailWasChanged = false)
    {
        if ($emailWasChanged) {
            $this->sendFundistMail($login, $api_password, 'ChangeEmail', [ 'Email' => $data['email'] ]);
        }

        if ( count($data) > 1 || (count($data) == 1 && !isset($data['email'])) ) {
            unset($data['password']);
            unset($data['currentPassword']);
            unset($data['repeatPassword']);
            unset($data['repeat_password']);
            unset($data['sms_code']);
            unset($data['reg_promo']);
            unset($data['reg_bonus']);
            $this->sendFundistMail($login, $api_password, 'ChangeProfile', [ 'Data' => json_encode($data) ]);
        }
    }


    private function checkProtectedFields($data)
    {
        $errors = [];
        $protectedFields = _cfg('protectedUserFields') ?: [];
        $fieldTranslates = [
            'firstName' => _('first name'),
            'lastName' => _('last name'),
            'birthDate' => _('birth date'),
            'sex' => _('sex'),
            'country' => _('country'),
            'phone' => _('phone number')
        ];

        if (_cfg('disallowCountryChange') && !in_array('country', $protectedFields)) {
            $protectedFields[] = 'country';
        }

        if (!empty($protectedFields)) {
            $changedFields = [];
            foreach ($protectedFields as $field) {
                $isChanged = false;
                switch ($field) {
                    case 'birthDate':
                        if (!empty($this->userData->birth_day) && isset($data['birth_year'])) {
                            $isChanged = $this->userData->birth_day !== $data['birth_day'] ||
                                         $this->userData->birth_month !== $data['birth_month'] ||
                                         $this->userData->birth_year !== $data['birth_year'];
                        }
                        break;

                    case 'phone':
                        if (isset($data['pre_phone']) || isset($data['main_phone'])) {
                            $isChanged =
                                $this->userData->phone1 !== $data['pre_phone'] ||
                                $this->userData->phone2 !== $data['main_phone'];
                        }
                        break;

                    default:
                        $isChanged = isset($data[$field]) && !empty($this->userData->{$field}) && $this->userData->{$field} !== $data[$field];
                }

                if ($isChanged) {
                    $changedFields[] = $field;
                }
            }

            if (!empty($changedFields)) {
                $changedFields = array_map(function ($field) use ($fieldTranslates) {
                    return $fieldTranslates[$field];
                }, $changedFields);

                $errors[] = sprintf(
                        ngettext(
                            'To change %s field please contact our support at ',
                            'To change %s fields please contact our support at ',
                            count($changedFields)
                        ),
                        implode(', ', $changedFields)
                    ) .
                    '<a href="mailto:' . _cfg('supportEmail') . '">' . _cfg('supportEmail') . '</a>';
            }
        }

        return $errors;
    }

    public function sendEmailUnsubscribe($code) {
        list($ID, $code) =  explode('_', $code);

        $url = '/WLCAccount/EmailUnsubscribe/?&ID=' . $ID;

        $transactionId = $this->getApiTID($url);
        $hash = md5('WLCAccount/EmailUnsubscribe/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $ID . '/' . $code . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
            'Code' => $code,
        );

        $url .= '&' . http_build_query($params);

        return $this->runFundistAPI($url);
    }

    public function checkPassOnUpdate(): bool
    {
        if (_cfg('checkPassOnUpdate') === 0) {
            return false;
        }

        $additionalFields = $this->userData !== false && isset($this->userData->additional_fields)
            ? json_decode($this->userData->additional_fields, true) ?? []
            : [];

        if (isset($additionalFields['isFirstSession']) && $additionalFields['isFirstSession'] == 1
            && _cfg('skipPassCheckOnFirstSession') == 1
            && _cfg('fastRegistration') == 1
            && _cfg('registerGeneratePassword') == 1
        ) {
            return false;
        }

        if (isset($additionalFields['type']) && $additionalFields['type'] === self::LOGIN_TYPE_METAMASK) {
            return false;
        }

        return _cfg('checkPassOnUpdate') !== 0;
    }

    public function setIsFirstSession(): void
    {
        if (!$this->userData || !$this->userData->id) {

            return;
        }

        $additionalFields = $this->userData !== false
            ? json_decode($this->userData->additional_fields, true) ?? []
            : [];

        if (!isset($additionalFields['isFirstSession'])) {
            $this->profileAdditionalFieldsUpdate(['isFirstSession' => 1], $this->userData->id);
        } else {
            $this->profileAdditionalFieldsUpdate(['isFirstSession' => 0], $this->userData->id);
        }
    }

    public function setProfileType(?string $type = ''): void
    {
        if (!$this->userData || !$this->userData->id) {
            return;
        }

        $this->profileAdditionalFieldsUpdate(
            ['type' => $type === self::LOGIN_TYPE_METAMASK ? self::LOGIN_TYPE_METAMASK : 'default'],
            $this->userData->id
        );
    }

    /**
     * Accept user`s terms of service by user login.
     *
     * @param $login
     * @return array|false
     * @throws \Exception
     */
    public static function acceptTersmOfService($login)
    {
        if (empty($login) || empty(_cfg('termsOfService'))) {
            return false;
        }

        try {
            $url = '/User/AcceptTermsOfService?';
            $system = System::$instance;
            $transactionId = $system->getApiTID($url);
            $hash = md5('User/AcceptTermsOfService/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $login . '/' . _cfg('fundistApiPass'));
            $params = array(
                'Login' => $login,
                'TID' => $transactionId,
                'Hash' => $hash,
                'UserIP' => System::getUserIP(),
                'TermsOfService' => _cfg('termsOfService')
            );

            $url .= http_build_query($params);

            $response = $system->runFundistAPI($url);
            $data = explode(',', $response, 2);

            if ($data[0] === '0') {
                throw new ApiException(_('Unknown status') . ' 0' . (!empty($data[1]) ? ':' . $data[1] : ''), 500);
            }

            if ($data[0] !== '1') {
                throw new ApiException(!empty($data[1]) ? $data[1] : _('Unknown error'), 400);
            }

            $data = json_decode($data[1], true);
            $userAdditionalFields = User::getUserAdditionalFieldsByUserId((int) $_SESSION['user']['id']);
            $userAdditionalFields['termsOfService'] = $data['ToSVersion'];
            DB::query("UPDATE `users` SET `additional_fields` = '". json_encode($userAdditionalFields) . "' WHERE `id` = ". (int)$_SESSION['user']['id']);
            $_SESSION['user']['additional_fields'] = json_encode($userAdditionalFields);
            return $data;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param int $userId
     * @return array
     */
    public static function getUserAdditionalFieldsByUserId(int $userId): array
    {
        $result = DB::query("SELECT `additional_fields` FROM `users` WHERE `id` = {$userId}")->fetch_assoc();

        return json_decode($result['additional_fields'], true) ?: [];
    }

    /**
     * @return bool
     */
    public static function isCurrentUserAcceptCurrentTermsOfService(): bool
    {
        $additionalFields = json_decode($_SESSION['user']['additional_fields']);
        return (
            !empty(_cfg('termsOfService')) &&
            !empty($additionalFields->termsOfService) &&
            _cfg('termsOfService') == $additionalFields->termsOfService
        );
    }

    /**
     * @param array $request
     * @param string $action
     * @return array|bool|mixed|object|stdClass|string|string[]|null
     */
    public static function tempUsers(array $request, string $action = '')
    {
        $userId = is_numeric($request['userId']) ? (int) $request['userId'] : null;

        if (in_array($action, ['Activation', 'Resendemail']) && !$userId) {
            return '0,UserId required';
        }

        switch ($action) {
            case 'Activation':
                $skipLogin = (bool)isset($_POST['isApiCall']);
                return (new \eGamings\WLC\User())->finishRegistration($userId, [], $skipLogin);
            case 'ResendEmail':
                $user = Db::fetchRow('SELECT * FROM `users_temp` WHERE `id` = "' . Db::escape($userId) .'"');

                if (!$user) {
                    return sprintf("0,User #%d not found", $userId);
                }

                $additionFields = json_decode($user->additional_fields, true);
                $data = [];

                $data['password'] = $data['passwordRepeat'] = $additionFields['repeat_password'];
                $data['firstName'] = $user->first_name;
                $data['lastName'] = $user->last_name;
                $data['email'] = $user->email;
                $data['currency'] = $user->currency;
                $data['code'] = $user->code;

                return User::getInstance()->sendConfirmationEmail($data);
            default:
                $limit = isset($request['limit']) && is_numeric($request['page']) ? (int) $request['page'] : 15;
                $offset = isset($request['offset']) && is_numeric($request['offset']) ? (int) $request['offset'] : 0;

                $email = $request['email'] ?? false;
                $dateFrom = $request['dateFrom'] ?? false;
                $dateTo = $request['dateTo'] ?? false;
                $id = $request['id'] ?? false;
                $name = $request['name'] ?? false;

                $where = [];
                $having = '';
                if ($email) {
                    $where[] = "`email` LIKE '%" . $email . "%'";
                }

                if ($dateFrom) {
                    $where[] = "`reg_time` >= '" . (new \DateTime($dateFrom))->format('Y-m-d H:i:s') . "'";
                }

                if ($dateTo) {
                    $where[] = "`reg_time` <= '" . (new \DateTime($dateTo))->format('Y-m-d H:i:s') . "'";
                }

                if ($id) {
                    $where[] = "`id` LIKE '%" . $id . "%'";
                }

                if ($name) {
                    $having = " HAVING `full_name` LIKE '%" . $name . "%'";
                }

                $where = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';
                $orderBy = Common::sortToOrderBy($request['sort'] ?? '', [
                    'name' => 'full_name',
                ]);

                $query = 'SELECT 
                             `id`, 
                             `first_name`, 
                             `last_name`, 
                             `email`, 
                             DATE_FORMAT(`reg_time`, "%d.%m.%Y %H:%i:%s") `reg_time`,
                             IF(first_name = "", last_name, CONCAT(first_name, " ", last_name)) as full_name
                          FROM `users_temp` '. $where . ' 
                          GROUP BY `id`' .
                          $having .
                          (empty($orderBy) ? ' ORDER BY `id` DESC ' : ' ORDER BY ' . implode(', ', $orderBy));

                if (!empty($request['csv'])) {
                    $result = Db::fetchRows($query);
                } else {
                    $result = Db::fetchRows($query . ' LIMIT '. $offset . ',' . $limit);
                }

                $num_of_all_records = Db::query('SELECT COUNT(*) FROM `users_temp` '. $where . '')->fetch_all();

                if ($result === false) {
                    return [];
                }

                $records = [];
                foreach ($result as $record) {
                    $records[] = (array) $record;
                }

                return [
                    'records' => $records,
                    'numOfRecords' => (int) $num_of_all_records[0][0]
                ];
        }
    }

    /**
     * @param array $data
     * @return string
     */
    public function depositPrestep(array $data): string
    {
        if (!$this->isUser()) {
            return '0,' . _('Session is expired');
        }

        if (empty($data['systemId'])) {
            return '0,' . _('System ID not specified');
        }

        $data['amount'] = number_format($data['amount'] ?? 0, 2, '.', '');

        if ($data['amount'] <= 0) {
            return '0,' . _('set_amount');
        }

        $url = sprintf('/WLCAccount/DepositPrestep/?&%s', http_build_query([
            'Login' => (int)$this->userData->id,
            'Amount' => $data['amount'],
            'Currency' => $this->userData->currency,
            'System' => $data['systemId'],
        ]));

        $additional = [];

        if (!empty($data['additional'])) {
            foreach ($data['additional'] as $key => $val) {
                $additional[sprintf('Additional[%s]', $key)] = $val;
            }
        }

        $transactionId = $this->getApiTID($url);

        $hash = md5(sprintf('WLCAccount/DepositPrestep/0.0.0.0/%s', implode('/', [
            $transactionId,
            _cfg('fundistApiKey'),
            $data['amount'],
            $this->userData->id,
            $this->userData->currency,
            _cfg('fundistApiPass'),
        ])));

        $params = array_merge([
            'Password' => $this->userData->api_password,
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
        ], $additional);

        $url .= '&' . http_build_query($params);

        return $this->runFundistAPI($url);
    }

    private function processRegisterCheckbox(array $data): array
    {
        $result = [];

        if (_cfg('requiredRegisterCheckbox')) {
            $date = date('Y-m-d H:i:s');
            foreach (_cfg('requiredRegisterCheckbox') as $checkbox) {
                $result[$checkbox] = $data[$checkbox];
                $result[$checkbox . 'Date'] = $date;
            }
        }

        return $result;
    }

    /**
     * Checks login in a database and changes it or adds an error based on that
     *
     * @param string $login
     * @param array $error
     * @return void
     */
    private function checkLogin(?string &$login, array &$error): void
    {
        if (!empty(trim($login)) && empty($error['login'])) {
            if (trim($login) != $this->userData->login) {
                $query = Db::fetchRow(
                    'SELECT `login` ' .
                    'FROM `users` ' .
                    'WHERE `login` = "' . Db::escape($login) . '" AND ' .
                    '`login` != "' . Db::escape($this->userData->login) . '" ' .
                    'LIMIT 1'
                );
                if ($query !== false) {
                    $error['login'] = _('login_already_in_use');
                }
            } else {
                $login = $this->userData->login;
            }
        }
    }

    /**
     * Check if phone is unique
     *
     * @param array $data
     * @return bool
     */
    public function checkUniquePhone(array $data = []): bool
    {
        $phoneNumber = $data['main_phone'] ?? null;
        $phoneCode = [];
        $phoneCode['phoneCode'] = $data['pre_phone'] ?? null;

        $validator = new UniquephoneValidator();
        return $validator->validate($phoneNumber, null, $phoneCode, null);
    }

    public function confirmationEmail(?string $code, bool $userAuth = false, string $password = '')
    {
        $result = [];
        $errors = [];

        if ($userAuth) {
            if ($code === $this->userData->email_verification_code) {
                $result = !self::setEmailVerified($this->userData->id, 1, false) ?
               'User E-mail verification failed' : true;
            }
        } else {

            $userData = $this->getUsersByCode((string) $code);
            if (!$userData) {
                throw new ApiException('Code is incorrect', 403, null, $errors);
            }

            if ($userData->email_verified) {
                throw new ApiException('Email already confirmed', 400, null, $result);
            }

            if ($code === $userData->email_verification_code) {
                $userPassword = $userData->password;
                $passwordValid = $this->verifyPassword($password, $userPassword);

                $attempts = json_decode($userData->additional_fields, true)['emailChangeVerifyAttempts'] ?? 0;
                $this->profileAdditionalFieldsUpdate(['emailChangeVerifyAttempts' => ++$attempts], (int)$userData->id);

                if ($attempts >= 3) {
                    User::removeEmailCode((int)$userData->id);
                }

                if (!$passwordValid) {

                    throw new ApiException(_('Password is invalid'), 403, null, $result);
                }

                $result = !self::setEmailVerified($userData->id, 1, false) ?
               'User E-mail verification failed' : true;

                if ($result === true) {
                    $this->login(['login' => $userData->email, 'pass' => $userPassword, true]);
                    $this->authorizeUser($userData, isset($userData->phone_verified));
                }
            } else {
                throw new ApiException('Code invalid', 400, null, $result);
            }
        }

        if (!empty($errors)) {
            return $errors;
        }

        return $result;
    }

    /**
     * @return void
     */
    public static function checkAndFixUserForBadAdditionalFieldsAfterAcceptTOS(): void
    {
        if (_cfg('termsOfService')) {
            $user = self::getInstance();
            $additionalFields = json_decode($user->userData->additional_fields, true) ?: [];

            if (
                array_key_exists('termsOfService', $additionalFields)
                && empty($additionalFields['email_code'])
                && empty($additionalFields['TosFixV1'])
            ) {
                $fundistUser = $user->getFundistUserAsArray(true);

                if (!empty($fundistUser['LoyaltyUserInfo'])) {
                    $fundistUser['LoyaltyUserInfo']['BonusRestrictions'] = json_decode($fundistUser['LoyaltyUserInfo']['BonusRestrictions'], true) ?: [];
                    $fundistUser['RestrictCasinoBonuses'] = $fundistUser['LoyaltyUserInfo']['BonusRestrictions']['RestrictCasinoBonuses']['State'] ?? "";
                    $fundistUser['RestrictSportBonuses'] = $fundistUser['LoyaltyUserInfo']['BonusRestrictions']['RestrictSportBonuses']['State'] ?? "";
                }

                $funcoreToWlcCoreAliases = [
                    'City' => 'city',
                    'Address' => 'address',
                    'AffiliateSystem' => 'affiliateSystem', //additional fields from funcore
                    'AffiliateID' => 'affiliateId',
                    'AffiliateClickID' => 'affiliateClickId',
                    'SmsAgree' => 'sendSMS',
                    'PostalCode' => 'postal_code',
                    'PhoneVerified' => 'phone_verified',
                    'Iban' => 'Iban',
                    'BranchCode' => 'BranchCode',
                    'Swift' => 'Swift',
                    'BankName' => 'BankName',
                    'DateOfBirth' => 'DateOfBirth',
                    'RegistrationIP' => 'reg_ip',
                    'BankAddress' => 'BankAddress',
                    'ValidationLevel' => 'ValidationLevel',
                    'EmailAgree' => 'sendEmail',
                    'RestrictCasinoBonuses' => 'RestrictCasinoBonuses',
                    'RestrictSportBonuses' => 'RestrictSportBonuses',
                ];

                $needCastToBool = ['sendEmail', 'sendSMS'];

                foreach ($funcoreToWlcCoreAliases as $funcoreKey => $wlcCoreKey) {
                    if (!empty($fundistUser[$funcoreKey]) && empty($additionalFields[$wlcCoreKey])) {
                        $additionalFields[$wlcCoreKey] = in_array($wlcCoreKey, $needCastToBool)
                            ? (bool) $fundistUser[$funcoreKey]
                            : $fundistUser[$funcoreKey];
                    }
                }



                $additionalFields['TosFixV1'] = true;

                Db::query("
                    UPDATE `users` SET `additional_fields` = '" . Db::escape(json_encode($additionalFields)) . "'
                    WHERE `users`.`id` = {$user->userData->id} LIMIT 1
                ");
            }
        }
    }

    public function getUsersByCode(string $code)
    {
        if ($code != '') {
            $userData = Db::fetchRow('SELECT * FROM `users` WHERE ' .
                '`email_verification_code` = "' . Db::escape($code) . '" ' .
                'LIMIT 1'
            );
        } else {
            $userData = false;
        }

        return $userData;
    }

    /**
     * @param string $termsOfServiceVersion
     * @return void
     */
    public static function saveTermsOfService(string $termsOfServiceVersion): void
    {
        DB::query("UPDATE `users` SET `additional_fields` = JSON_SET(`additional_fields`, '$.termsOfService', '{$termsOfServiceVersion}') WHERE `id` = " . (int)$_SESSION['user']['id']);
        $additionalFields = json_decode($_SESSION['user']['additional_fields'], true);
        $additionalFields['termsOfService'] = $termsOfServiceVersion;
        $_SESSION['user']['additional_fields'] = json_encode($additionalFields);
    }

    /**
     * Checks if email is valid and doesn't exist in a database
     *
     * @param string $email
     * @param array $error
     * @return void
     */
    private function checkEmailOnUpdate(string &$email, array &$error): void
    {

        if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            $error['email'] = _('Email field is incorrect');
        }

        if (empty($error['email']) && !empty(trim($email))) {

            if (trim($email) != $this->userData->email) {
                $query = Db::fetchRow(
                    'SELECT `email` ' .
                    'FROM `users` ' .
                    'WHERE `email` = "' . Db::escape($email) . '" AND ' .
                    '`email` != "' . Db::escape($this->userData->email) . '" ' .
                    'LIMIT 1'
                );

                if ($query !== false) {
                    $error['email'] = _('Email already in use');
                }
            } else {
                $email = $this->userData->email;
            }
        }
    }
}
