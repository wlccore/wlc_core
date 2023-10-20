<?php

namespace eGamings\WLC;

use eGamings\WLC\Logger;

// Class for storing the keys
final class PrometheusKeys
{
    // @codeCoverageIgnoreStart

    /** @var PrometheusKeys The instance of the singleton */
    private static $instance;

    /** @var PrometheusKey */
    public $AUTH_ERROR;

    /** @var PrometheusKey */
    public $AUTH_START;

    /** @var PrometheusKey */
    public $AUTH_SESSION_START;

    /** @var PrometheusKey */
    public $AUTH_REGISTER_START;

    /** @var PrometheusKey */
    public $AUTH_REGISTER_SESSION_START;

    /** @var PrometheusKey */
    public $AUTH_METAMASK_NOT_ALLOWED;

    /** @var PrometheusKey */
    public $AUTH_PROFILE_INCOMPLETE;

    /** @var PrometheusKey */
    public $AUTH_FORBIDDEN_COUNTRY;

    /** @var PrometheusKey */
    public $AUTH_SMS_AUTH_FAILED;

    /** @var PrometheusKey */
    public $AUTH_INVALID_SMS_CODE;

    /** @var PrometheusKey */
    public $AUTH_INVALID_SIGNATURE_METAMASK;

    /** @var PrometheusKey */
    public $AUTH_FAIL;

    /** @var PrometheusKey */
    public $AUTH_ACCESS_FORBIDDEN;

    /** @var PrometheusKey */
    public $AUTH_INCORRECT_PASSWORD;

    /** @var PrometheusKey */
    public $REGISTRATION_IN_PROGRESS;

    /** @var PrometheusKey */
    public $USER_DISABLED;

    /** @var PrometheusKey */
    public $HOOK_USER_LOGIN_FAIL;

    /** @var PrometheusKey */
    public $REG_FORBIDDEN_COUNTRY;

    /** @var PrometheusKey */
    public $REG_CHECKBOXES_NOT_SET;

    /** @var PrometheusKey */
    public $REG_INVALID_SMS_CODE;


    /** @var PrometheusKey */
    public $AUTH_DEFAULT_TYPE;

    /** @var PrometheusKey */
    public $AUTH_SMS_TYPE;

    /** @var PrometheusKey */
    public $AUTH_METAMASK_TYPE;

    /** @var PrometheusKey */
    public $AUTH_SAVE_SMS_CODE;

    /** @var PrometheusKey */
    public $AUTH_BY_EMAIL;

    /** @var PrometheusKey */
    public $AUTH_BY_LOGIN;

    /** @var PrometheusKey */
    public $AUTH_BY_PHONE;

    /** @var PrometheusKey */
    public $SESSION_START;

    /** @var PrometheusKey */
    public $AUTH_FAST_TRACK_AUTHENTICATION;

    /** @var PrometheusKey */
    public $AUTH_JWT_TOKEN_RECORD;

    /** @var PrometheusKey */
    public $HOOK_AUTH_PUT;

    /** @var PrometheusKey */
    public $HOOK_AUTH_PASSWORD_VERIFY;

    /** @var PrometheusKey */
    public $HOOK_AUTH_AFTER_LOGIN;

    /** @var PrometheusKey */
    public $REG_COMPLETED;

    /** @var PrometheusKey */
    public $REG_SUCCESS;

    /** @var PrometheusKey */
    public $REG_FAST_BY_NUMBER;

    /** @var PrometheusKey */
    public $SESSION_DELETE;

    /** @var PrometheusKey */
    public $HOOK_SESSION_DELETE;

    /** @var PrometheusKey */
    public $BASIC_AUTHENTICATION;

    /** @var PrometheusKey */
    public $BEARER_AUTHENTICATION;

    private $prefix = 'wlc';

    public function __construct()
    {
        // Authentication error metrics
        $this->AUTH_ERROR =                      $this->initKey('auth_error', PrometheusMetricTypes::COUNTER, 'optional description');
        $this->AUTH_START =                      $this->initKey('auth_start', PrometheusMetricTypes::COUNTER);
        $this->AUTH_SESSION_START =              $this->initKey('auth_session_start ', PrometheusMetricTypes::COUNTER);
        $this->AUTH_REGISTER_START =             $this->initKey('auth_register_start ', PrometheusMetricTypes::COUNTER);
        $this->AUTH_REGISTER_SESSION_START =     $this->initKey('auth_register_session_start ', PrometheusMetricTypes::COUNTER);
        $this->AUTH_METAMASK_NOT_ALLOWED =       $this->initKey('metamask_not_allowed', PrometheusMetricTypes::COUNTER);
        $this->AUTH_PROFILE_INCOMPLETE =         $this->initKey('incomplete_profile', PrometheusMetricTypes::COUNTER); // new
        $this->AUTH_FORBIDDEN_COUNTRY =          $this->initKey('forbidden_country', PrometheusMetricTypes::COUNTER);
        $this->AUTH_SMS_AUTH_FAILED =            $this->initKey('sms_auth_failed', PrometheusMetricTypes::COUNTER);
        $this->AUTH_INVALID_SMS_CODE =           $this->initKey('invalid_sms_code', PrometheusMetricTypes::COUNTER);
        $this->AUTH_FAIL =                       $this->initKey('login_fail', PrometheusMetricTypes::COUNTER);
        $this->AUTH_INVALID_SIGNATURE_METAMASK = $this->initKey('invalid_signature_metamask', PrometheusMetricTypes::COUNTER);
        $this->AUTH_ACCESS_FORBIDDEN =           $this->initKey('access_forbidden', PrometheusMetricTypes::COUNTER);
        $this->AUTH_INCORRECT_PASSWORD =         $this->initKey('incorrect_password', PrometheusMetricTypes::COUNTER);
        $this->REGISTRATION_IN_PROGRESS =        $this->initKey('registration_in_progress', PrometheusMetricTypes::COUNTER);
        $this->USER_DISABLED =                   $this->initKey('user_disabled', PrometheusMetricTypes::COUNTER);
        $this->HOOK_USER_LOGIN_FAIL =            $this->initKey('hook_user_login_fail', PrometheusMetricTypes::COUNTER);

        // Registration error metrics
        $this->REG_FORBIDDEN_COUNTRY =          $this->initKey('reg_forbidden_country_error', PrometheusMetricTypes::COUNTER);
        $this->REG_CHECKBOXES_NOT_SET =         $this->initKey('reg_checkboxes_not_set_error', PrometheusMetricTypes::COUNTER);
        $this->REG_INVALID_SMS_CODE =           $this->initKey('reg_invalid_sms_code', PrometheusMetricTypes::COUNTER);

        // Authorization actions metrics
        $this->AUTH_DEFAULT_TYPE =              $this->initKey('auth_default_type', PrometheusMetricTypes::COUNTER);
        $this->AUTH_SMS_TYPE =                  $this->initKey('auth_sms_type', PrometheusMetricTypes::COUNTER);
        $this->AUTH_METAMASK_TYPE =             $this->initKey('auth_metamask_type', PrometheusMetricTypes::COUNTER);

        $this->AUTH_SAVE_SMS_CODE =             $this->initKey('auth_save_sms_code', PrometheusMetricTypes::COUNTER);

        $this->AUTH_BY_EMAIL =                  $this->initKey('auth_by_email', PrometheusMetricTypes::COUNTER);
        $this->AUTH_BY_LOGIN =                  $this->initKey('auth_by_login', PrometheusMetricTypes::COUNTER);
        $this->AUTH_BY_PHONE =                  $this->initKey('auth_by_phone', PrometheusMetricTypes::COUNTER);
        $this->SESSION_START =                  $this->initKey('session_start', PrometheusMetricTypes::COUNTER);
        $this->AUTH_FAST_TRACK_AUTHENTICATION = $this->initKey('auth_fast_track_authentication', PrometheusMetricTypes::COUNTER);
        $this->AUTH_JWT_TOKEN_RECORD =          $this->initKey('auth_jwt_token_record', PrometheusMetricTypes::COUNTER);
        $this->HOOK_AUTH_PUT =                  $this->initKey('hook_auth_put', PrometheusMetricTypes::COUNTER);
        $this->HOOK_AUTH_PASSWORD_VERIFY =      $this->initKey('hook_auth_password_verify', PrometheusMetricTypes::COUNTER);
        $this->HOOK_AUTH_AFTER_LOGIN =          $this->initKey('hook_auth_after_login', PrometheusMetricTypes::COUNTER);


        // Registration actions metrics
        $this->REG_COMPLETED =                  $this->initKey('reg_completed', PrometheusMetricTypes::COUNTER);
        $this->REG_SUCCESS =                    $this->initKey('reg_success', PrometheusMetricTypes::COUNTER);
        $this->REG_FAST_BY_NUMBER =             $this->initKey('reg_fast_by_number', PrometheusMetricTypes::COUNTER);

        // Logout action metrics
        $this->SESSION_DELETE =                 $this->initKey('session_delete', PrometheusMetricTypes::COUNTER);
        $this->HOOK_SESSION_DELETE =            $this->initKey('hook_session_delete', PrometheusMetricTypes::COUNTER);

        $this->BASIC_AUTHENTICATION =           $this->initKey('basic_authentication', PrometheusMetricTypes::COUNTER);
        $this->BEARER_AUTHENTICATION =          $this->initKey('bearer_authentication', PrometheusMetricTypes::COUNTER);

        return $this;
    }

    /**
     * @param string $label_type
     * @param string $metric_type
     * @return PrometheusKey
     */
    private function initKey(string $label_type, string $metric_type, string $description = '')
    {
        // @codeCoverageIgnoreStart
        $key = new PrometheusKey($metric_type, $label_type, $description);

        $instance_name = Logger::getInstanceName();
        $server_name = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : "placeholder_server_name";

        return $key
                ->l_type($label_type)
                ->l_app($instance_name)
                ->l_domain($server_name)
                ->l_hostname(gethostname());
        // @codeCoverageIgnoreEnd
    }

    public function getRedisKeys(): string
    {
        return implode("", [
            $this->AUTH_ERROR->getAllKeysValue(),
            $this->AUTH_START->getAllKeysValue(),
            $this->AUTH_SESSION_START->getAllKeysValue(),
            $this->AUTH_REGISTER_START->getAllKeysValue(),
            $this->AUTH_REGISTER_SESSION_START->getAllKeysValue(),
            $this->AUTH_METAMASK_NOT_ALLOWED->getAllKeysValue(),
            $this->AUTH_PROFILE_INCOMPLETE->getAllKeysValue(),
            $this->AUTH_FORBIDDEN_COUNTRY->getAllKeysValue(),
            $this->AUTH_SMS_AUTH_FAILED->getAllKeysValue(),
            $this->AUTH_INVALID_SMS_CODE->getAllKeysValue(),
            $this->AUTH_FAIL->getAllKeysValue(),
            $this->AUTH_INVALID_SIGNATURE_METAMASK->getAllKeysValue(),
            $this->AUTH_ACCESS_FORBIDDEN->getAllKeysValue(),
            $this->AUTH_INCORRECT_PASSWORD->getAllKeysValue(),
            $this->REGISTRATION_IN_PROGRESS->getAllKeysValue(),
            $this->USER_DISABLED->getAllKeysValue(),
            $this->HOOK_USER_LOGIN_FAIL->getAllKeysValue(),
            $this->REG_FORBIDDEN_COUNTRY->getAllKeysValue(),
            $this->REG_CHECKBOXES_NOT_SET->getAllKeysValue(),
            $this->REG_INVALID_SMS_CODE->getAllKeysValue(),
            $this->AUTH_DEFAULT_TYPE->getAllKeysValue(),
            $this->AUTH_SMS_TYPE->getAllKeysValue(),
            $this->AUTH_SAVE_SMS_CODE->getAllKeysValue(),
            $this->AUTH_BY_EMAIL->getAllKeysValue(),
            $this->AUTH_BY_LOGIN->getAllKeysValue(),
            $this->AUTH_BY_PHONE->getAllKeysValue(),
            $this->SESSION_START->getAllKeysValue(),
            $this->AUTH_FAST_TRACK_AUTHENTICATION->getAllKeysValue(),
            $this->AUTH_JWT_TOKEN_RECORD->getAllKeysValue(),
            $this->HOOK_AUTH_PUT->getAllKeysValue(),
            $this->HOOK_AUTH_PASSWORD_VERIFY->getAllKeysValue(),
            $this->REG_COMPLETED->getAllKeysValue(),
            $this->REG_SUCCESS->getAllKeysValue(),
            $this->REG_FAST_BY_NUMBER->getAllKeysValue(),
            $this->SESSION_DELETE->getAllKeysValue(),
            $this->HOOK_SESSION_DELETE->getAllKeysValue(),
            $this->BASIC_AUTHENTICATION->getAllKeysValue(),
            $this->BEARER_AUTHENTICATION->getAllKeysValue(),
        ]);
    }

    /** @return PrometheusKeys */
    public static function getInstance()
    {
        return self::$instance ?? new self();
    }
    // @codeCoverageIgnoreEnd
}
