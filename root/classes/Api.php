<?php

namespace eGamings\WLC;

use eGamings\WLC\RestApi\ApiEndpoints;

class Api
{
    protected static $success = 0;
    protected static $message = '';
    protected static $result = Array();
    protected static $error_fields = Array();
    private static $is_api_call = false;

    /**
     * Create/update user
     *
     * @param string $operation 'create', 'update'
     */
    private static function userDo($operation)
    {
        do {
            if(!self::checkRequest())
                break;

            self::$is_api_call = true;
            $fields = $_POST;

            if ($operation === 'create') {
                if (Affiliate::identifyAffiliate($fields)) {
                    $fields['affiliateSystem'] = Affiliate::getSystem();
                    $fields['affiliateId'] = Affiliate::getAffiliateId($fields);
                }

                if (empty($fields['country']) && !empty(_cfg('userCountry'))) {
                    $fields['country'] = _cfg('userCountry');
                }
                $u = new User();
                $r = $u->register($fields, $skip_login = true, $fast_registration = true);
            } elseif ($operation === 'update' && _cfg('emailResetLink') && !empty($fields['reset']) && $fields['reset'] == 1) {
                $update = new User($fields['id']);
                $update->resetPassword($fields);
                return 1;
            } elseif ($operation === 'update') {
                $u = new User($fields['id']);
                $r = $u->profileUpdate($fields, true);
            } elseif ($operation === 'statusupdate') {
                $u = new User($fields['id']);
                $r = $u->updateStatus($fields);
            } elseif (in_array($operation, ['GetTempUsers', 'Activation', 'ResendEmail'])) {
                $body = User::tempUsers($fields, $operation);
                $response = ApiEndpoints::buildResponse(200, 'success', $body);
                exit(json_encode($response, JSON_UNESCAPED_UNICODE));
            } else {
                self::$message = 'Error';
                break;
            }

            //field level error
            $json = json_decode($r, $assoc = true);
            if (isset($json['error'])) {
                self::$message = 'Error';
                self::$error_fields = $json['error'];
                break;
            }

            if ($r === '1' || $r === true) {
                if ($operation === 'create') {
                    self::$message = 'User created';
                } elseif ($operation === 'update') {
                    self::$message = 'User updated';
                } elseif ($operation === 'statusupdate') {
                    self::$message = 'User status updated';
                }
                self::$success = 1;
            } else {
                //assume Fundist gave some non-1 response
                $parts = explode(';', $r);
                self::$message = 'Error';
                if (isset($parts[1])) {
                    self::$message .= ': ' . $parts[1];
                } else {
                    self::$message .= ': ' . $r;
                }
            }
        } while (false);

        self::$result = Array('success' => self::$success, 'message' => self::$message);
        if (count(self::$error_fields)) {
            self::$result['error_fields'] = self::$error_fields;
        }

        header('Content-Type: application/json');
        echo json_encode(self::$result, JSON_UNESCAPED_UNICODE);
    }

    public static function userRegister()
    {
        self::userDo('create');
    }

    public static function userUpdate()
    {
        self::userDo('update');
    }

    public static function userStatusUpdate()
    {
        self::userDo('statusupdate');
    }

   public static function userTemp($action)
   {
        self::userDo($action);
   }

    public static function resendBGCData()
    {
        if (empty(_cfg('bgcUrl'))) {
            echo 'bgc Url not configured on wlc';
            return;
        }
        $headers = [];
        $serverHeadersMap = [
            'HTTP_AUTHORIZATION' => 'Authorization',
            'HTTP_SOAPACTION' => 'SOAPAction',
            'HTTP_CONTENT_TYPE' => 'Content-Type',
            'HTTP_USER_AGENT' => 'User-Agent',
            'HTTP_CONTENT_LENGTH' => 'Content-Length'
        ];
        foreach ($_SERVER as $name => $value) {
            if (isset($serverHeadersMap[$name])) {
                $headers[] = $serverHeadersMap[$name] . ': ' . $value;
            }
        }

        $data = file_get_contents("php://input");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_URL, _cfg('bgcUrl'));
        $response = curl_exec($ch);
        echo $response;
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!$response || $http_code > 300) {
            if (_cfg('bgcUrl') !== 'test/test') header(':', true, $http_code);
        }
    }

    public static function userSendEmail()
    {
        do {
            if(!self::checkRequest() && empty($_SERVER['TEST_RUN'])) {
                break;
            }

            $fields = [];
            if (!empty($_POST['params'])) {
            	$fields = json_decode($_POST['params'], JSON_OBJECT_AS_ARRAY);
            }
            
            if (empty($fields['to'])) {
            	self::$message = _('Empty to address');
            	break;
            }

            if (empty($fields['subject'])) {
            	self::$message = _('Empty subject');
            	break;
            }

            if (empty($fields['message'])) {
            	self::$message = _('Empty message');
            	break;
            }

            //wlc placeholders
            $placeholders = [
                'site-name' => _cfg('websiteName'),
                'site-url' => _cfg('site'),
                'support_email' => _cfg('supportEmail'),
            ];

            try {
            	//finally replace placeholders with wlc placeholders
                $subject = self::replacePlaceholders($placeholders, $fields['subject']);
                $message = self::replacePlaceholders($placeholders, $fields['message']);

                $smtp = !empty($fields['smtp']) ? $fields['smtp'] : [];
                $from = !empty($fields['from']) ? $fields['from'] : [];
                $eventEmail = !empty($fields['eventEmail']) ? $fields['eventEmail'] : '';

	            if(empty($smtp)) {

	                $result = Email::send($fields['to'], $subject, $message, [], null, $eventEmail);
	            } else {
	                $result = Email::sendOverExternalSmtp($smtp, $fields['to'], $subject, $message, $from);
	            }
	
	            if ($result !== true) {
	                self::$message = _cfg('websiteName') . ' ( ' . _cfg('site') . ' ): Email send fail';
	                break;
	            }

	            self::$success = 1;
	            self::$message = 'Email send success';
            } catch(\Exception $ex) {
            	self::$message = $ex->getMessage();
            }
        } while(false);

        self::$result = ['success' => self::$success, 'message' => self::$message];
        if (!self::$success) {
        	error_log(json_encode(['result' => self::$result, 'request' => $_POST]));
        }

        if (empty($_SERVER['TEST_RUN'])) header('Content-Type: application/json');
        echo json_encode(self::$result, JSON_UNESCAPED_UNICODE);

        return (self::$success) ? true : false;
    }

    public static function runCron()
    {
        register_shutdown_function(function() {
            $_GET['force'] = 1;
            $s = new System();
            $s->runCron();
        });
    }

    protected static function checkRequest()
    {
        $wlc_secret = _cfg('wlc_secret');

        if (empty($wlc_secret)) {
            self::$message = 'Configuration error';
            return false;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return false;
        }

        $route_parts = explode('/', $_GET['route']);
        $key = array_pop($route_parts);

        if ($key !== $wlc_secret) {
            self::$message = 'Invalid key';
            return false;
        }
        return true;
    }

    /**
     * Create email queue
     *
     * @return void
     */
    public static function createEmailQueue()
    {
        do {
            $rows = !empty($_POST['params']) ? json_decode($_POST['params'], true) : '';

            if (!$rows) {
                $error = _('Empty required parameter');
            }

            $error = '';
            $smtp = [];

            $result = [
               'success' => 1,
               'message' => ''
            ];

            if (isset($rows['smtp'])) {
                $smtp = $rows['smtp'];

                unset($rows['smtp']);
            }

            $placeholders = [
               'site-name' => _cfg('websiteName'),
               'site-url' => _cfg('site'),
               'support_email' => _cfg('supportEmail'),
            ];

            $smtpId = $smtp ? EmailQueue::addSmtpConfig($smtp) : 0;

            foreach ($rows as &$row) {
               $row['subject'] = self::replacePlaceholders($placeholders, $row['subject']);
               $row['message'] = self::replacePlaceholders($placeholders, $row['message']);
               $row['smtp_id'] = $smtpId;
            }

            EmailQueue::multiEnqueue($rows);
        } while(false);

        if ($error) {
            $result['success'] = 0;
            $result['message'] = $error;
        }

        header('Content-Type: application/json');

        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * create sms queue
     * 
     * @return void
     */
    public static function createSmsQueue() : void
    {
        $rows = !empty($_POST['params']) ? json_decode($_POST['params'], true) : '';
        $error = '';

        if (!$rows) {
            $error = _('Empty required parameter');
        }
        
        $result = [
            'success' => 1,
            'message' => ''
        ];

        $placeholders = [
            'site-name' => _cfg('websiteName'),
            'site-url' => _cfg('site'),
        ];

        if (!empty($rows)) {
            foreach ($rows as &$row) {
                $row['message'] = self::replacePlaceholders($placeholders, $row['message']);
            }

            SmsQueue::multiEnqueue($rows);
        } else {
            $result['success'] = 0;
            $result['message'] = $error;
        }
    
        // @codeCoverageIgnoreStart
        if (empty($_SERVER['TEST_RUN'])) { 
            header('Content-Type: application/json'); 
        }
        // @codeCoverageIgnoreEnd

        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Replace template placeholders
     *
     * @param array $fields
     * @param $content
     * @return mixed
     */
    protected static function replacePlaceholders(array $fields, $content)
    {
        if (!empty($fields)) {
            foreach ($fields as $key => $value) {
                $content = str_replace('[' . $key . ']', $value, $content);
            }
        }

        return $content;
    }

    static public function isApiCall()
    {
        return self::$is_api_call;
    }
}
