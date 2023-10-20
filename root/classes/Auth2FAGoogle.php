<?php
namespace eGamings\WLC;

use eGamings\WLC\Cache\RedisCache;
use eGamings\WLC\RestApi\ApiException;
use eGamings\WLC\User;
use PragmaRX\Google2FA\Google2FA;

class Auth2FAGoogle
{
    public $user;
    protected $google2fa;
    protected $siteConfig;
    protected $redis;

    public const POSTFIX_AUTHKEY = 'authKey';
    public const POSTFIX_CODE2FA = 'code2FA';

    public function __construct()
    {
        $this->user = new User();
        $this->google2fa = new Google2FA();
        $this->siteConfig = Config::getSiteConfig();
        $this->redis = Core::DI()->get('redis');
    }

    public function checkEnable2FAGoogle(): bool
    {
        return (bool)(isset($this->siteConfig['Enable2FAGoogle']) && $this->siteConfig['Enable2FAGoogle'] == true);
    }

    public function checkEnable2FAGoogleOnUser(): string
    {
        $enabled_2fa = json_decode($this->user->userData->additional_fields, true)['enabled_2fa'];
        $secret_2fa = json_decode($this->user->userData->additional_fields, true)['secret_2fa'];

        if ($enabled_2fa) {
            if ($secret_2fa) {
                return 'enabled';
            } elseif (!$secret_2fa) {
                return 'notify';
            }
        }

        return 'disabled';
    }

    public function enable(): array
    {
        if ($this->checkEnable2FAGoogle() == false) {
            throw new ApiException(_('Google two-factor authentication not enabled'), 401);
        }

        $enabled_2fa = json_decode($this->user->userData->additional_fields, true)['enabled_2fa'];
        $secret_2fa = json_decode($this->user->userData->additional_fields, true)['secret_2fa'];

        if ($enabled_2fa && (!empty($secret_2fa) && $secret_2fa != 'init')){
            throw new ApiException(_('Two-factor authentication already enabled'), 401);
        }

        $secretKey = $this->google2fa->generateSecretKey();
        $redisKey = static::buildRedisKey($this->user->userData->email);
        $this->redis->del($redisKey);

        $this->redis->set(
            $redisKey,
            $secretKey,
            ['ex' => 300] //5 min
        );

        $this->user->profileAdditionalFieldsUpdate([
            'enabled_2fa' => 0,
            'secret_2fa' => "init",
            'notify_2fa' => 1
        ], $this->user->userData->id);

        $this->user->profileUpdateEnable2FAGoogle(1, 0);

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            _cfg('websiteName'),
            $this->user->userData->email,
            $secretKey
        );
    
        return ['path' => $qrCodeUrl, 'secret' => $secretKey];
    }

    public function verifiedEnable($code2FA): bool
    {
        $redisKey = static::buildRedisKey($this->user->userData->email);
        $secretKeyFromRedis = $this->redis->get($redisKey);

        if (empty($secretKeyFromRedis)) {
            throw new ApiException(_('The two-factor authentication verification time has expired'));
        }

        if ($this->checkCodeVerified($code2FA) === true) {
            $this->user->profileAdditionalFieldsUpdate([
                'enabled_2fa' => 1,
                'secret_2fa' => $secretKeyFromRedis,
                'notify_2fa' => 0,
            ], $this->user->userData->id);

            $this->redis->del($redisKey);


            $this->user->profileUpdateEnable2FAGoogle(1, 1);
        } else if ($this->checkCodeVerified($code2FA) == 'enabled') {
            throw new ApiException(_('Two-factor authentication already enabled'));
        } else if ($this->checkCodeVerified($code2FA) == 'disabled') {
            throw new ApiException(_('Google two-factor authentication not enabled'));
        } else {
            throw new ApiException(_('Two-factor authentication key is incorrect'));
        }

        return true;
    }

    public function disable(): bool
    {
        if($this->checkEnable2FAGoogle() == false) {
            throw new ApiException(_('Google two-factor authentication not enabled'), 401);
        }

        $enabled_2fa = json_decode($this->user->userData->additional_fields, true)['enabled_2fa'];

        if (!$enabled_2fa) {
            throw new ApiException(_('Two-factor authentication not enabled on user'), 401);
        }

        $this->user->profileAdditionalFieldsUpdate([
            'enabled_2fa' => 0,
            'secret_2fa' => "",
            'notify_2fa' => 0 
        ], $this->user->userData->id);

        $this->user->profileUpdateEnable2FAGoogle(0, 0);

        return true;
    }

    public function checkEnable2FAOnUserOrSendFail(array $userDataAdditional, string $email)
    {
        if (!empty($userDataAdditional['secret_2fa']) && $userDataAdditional['secret_2fa'] != 'init') {
            $redisKey = static::buildRedisAuthKey($email);

            $this->redis->set(
                $redisKey,
                $email,
                ['ex' => 600] //10 min
            );

            return $redisKey;
        } 

        return false;
    }

    public function checkCodeForAuth(string $authKey, string $code): bool
    {
        $google2fa = new Google2FA();
        $user = $this->getUserByAuthKey($authKey);
        $additionalFields = json_decode($user['additional_fields'], true);

        $secret_2fa = $additionalFields['secret_2fa'] ?? '';

        if (empty($additionalFields['secret_2fa']) || $additionalFields['secret_2fa'] == 'init') {
            throw new ApiException(_('Google two-factor authentication not enabled'), 401);
        } 

        if (!$additionalFields['enabled_2fa']) {
            throw new ApiException(_('Google two-factor authentication not enabled'), 401);
        }

        if ($additionalFields['enabled_2fa'] && empty($secret_2fa)) {
            throw new ApiException(_('Google two-factor authentication not enabled'), 401);
        }

        $timestamp = $google2fa->verifyKeyNewer($secret_2fa, $code, $additionalFields['google2fa_ts'], 2);

        if ($timestamp !== false) {
            $this->user->profileAdditionalFieldsUpdate(['google2fa_ts' => $timestamp], $user['id']);
        } 

        return (bool) $timestamp;
    }

    public function getUserByAuthKey(string $authKey): array
    {
        $email = $this->redis->get($authKey);

        if (!$email) {
            if (!_cfg('disableRateLimiterGoogle2FA')) {
                $lockTime = _cfg('lockTimeGoogle2FAAuthKey') ? _cfg('lockTimeGoogle2FAAuthKey') : 60;
                $this->rateLimiter(self::POSTFIX_AUTHKEY, $lockTime);
            }

            throw new ApiException(_('Auth key not found or expired'), 431);
        }

        $result = Db::fetchRow("
            SELECT * 
            FROM `users` 
            WHERE `email` = '{$email}'"
        );

        if ($result === false) {
            return true;
        }

        $user = (array)$result;

        return $user;
    }
    /**
     * @param string $email 
     * @param string $authKey 
     *
     * @return void
     */
    public function loginUserAfterCheck2FAGoogle(string $email, string $authKey): void
    {
        $instanceUser = User::getInstance();
        $userData = $instanceUser->getUserByEmail($email);
        
        if (empty($userData)) {
            throw new \ErrorException(_('User not found'), 401);
        }

        $this->deleteRedisAuthKey($authKey);

        $instanceUser->login([
            'login' => $userData->email,
            'pass'  => $userData->password
        ], true, '', false, true);
    }

    public function checkCodeVerified(string $code) 
    {
        $google2fa = new Google2FA();
        $userDataAdditional = json_decode($this->user->userData->additional_fields, true);

        $redisKey = static::buildRedisKey($this->user->userData->email);
        $secretKeyFromRedis = $this->redis->get($redisKey);

        if ($userDataAdditional['enabled_2fa']) {
            return 'enabled';
        }

        if (empty($secretKeyFromRedis)) {
            return 'disabled';
        }

        if ($userDataAdditional['enabled_2fa'] && empty($secretKeyFromRedis)) {
            return 'disabled';
        }

        $timestamp = $google2fa->verifyKeyNewer($secretKeyFromRedis, $code, $userDataAdditional['google2fa_ts'], 2);

        if ($timestamp !== false) {
            $this->user->profileAdditionalFieldsUpdate(['google2fa_ts' => $timestamp], $this->user->userData->id);
        } 

        return (bool) $timestamp;
    }

    public function disableNotify(): bool
    {
        if($this->checkEnable2FAGoogle() == false) {
            throw new ApiException(_('Google two-factor authentication not enabled'), 401);
        }

        $userDataAdditional = json_decode($this->user->userData->additional_fields, true);

        if ($userDataAdditional['notify_2fa'] === 0) {
            throw new ApiException(_('Notifications are already disable'), 401);
        }

        $this->user->profileAdditionalFieldsUpdate([
            'notify_2fa' => 0
        ], $this->user->userData->id);

        $this->user->profileUpdateEnable2FAGoogle(0, 0);

        return true;
    }

    public static function buildRedisKey(string $email): string
    {
        return join('_', [
            Logger::getInstanceName(),
            $email
        ]);
    }

    public static function buildRedisAuthKey(string $email): string
    {
        $string = join('_', [
            Logger::getInstanceName(),
            time(),
            $email
        ]);

        return hash('sha256', $string);
    }

    public function deleteRedisAuthKey(string $authKey): void
    {
        $this->redis->del($authKey);
    }

    public function rateLimiter(string $postfix, int $lockTime): void
    {
        $httpMethod = !empty($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $method = strtolower($httpMethod);
        $route = Router::getRoute();
        $ip = System::getUserIP();

        RateLimiter::getInstance()->limit($method . $route . $postfix, $ip, 10, 30, $lockTime * 60);
    }

}
