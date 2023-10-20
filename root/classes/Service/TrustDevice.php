<?php
namespace eGamings\WLC\Service;

use DateTime;
use eGamings\WLC\Core;
use eGamings\WLC\Provider\Repository\ITrustDevice as ITrustDeviceRepo;
use eGamings\WLC\Provider\Service\ITrustDevice;
use eGamings\WLC\Provider\IUser;
use eGamings\WLC\Domain\TrustDeviceConfiguration\TrustDeviceConfiguration;
use eGamings\WLC\FundistEmailTemplate;
use eGamings\WLC\RestApi\ApiException;
use eGamings\WLC\System;
use eGamings\WLC\User;

class TrustDevice implements ITrustDevice
{
    /**
     * @var IUser|null
     */
    protected $user = null;

    /**
     * STATUS_TRUSTED
     *
     * @var string
     */
    public static $STATUS_TRUSTED = 'trusted';

    /**
     * STATUS_ALWAYS
     *
     * @var string
     */
    public static $STATUS_ALWAYS = 'always';

    /**
     * __construct
     *
     * @param ?IUser $user
     */
    public function __construct(?IUser $user = null)
    {
        $this->user = $user;
    }

    /**
     * fetchAllDevices
     *
     * @return array 
     */
    public function fetchAllDevices(): array
    {
        $this->onlyAuthenticated();

        return $this->getRepository()->getAllDevices((int) $this->user->userData->id);
    }

    /**
     * checkDevice
     *
     * @param ?string $error
     * @return bool 
     */
    public function checkDevice(?string &$error): bool
    {
        $this->onlyAuthenticated();

        [
            'user_fingerprint' => $fingerprint,
            'user_agent' => $userAgent
        ] = $this->getAuthenticationDataFromEnv();

        if ($userAgent == '') {
            $error = 'missing user agent';
            return false;
        }

        $issetDevice = $this->getRepository()->issetDevice((int) $this->user->userData->id, $fingerprint, $userAgent);
        return $issetDevice !== false && $issetDevice->is_trusted == true;
    }

    /**
     * @throws \ErrorException
     * @throws ApiException
     */
    public function sendConfirmationEmail(): bool
    {
        $this->onlyAuthenticated();
        
        $redis = Core::DI()->get('redis');

        $this->removeOldKeys($redis);

        while(true) {
            $code = (string) mt_rand(100000, 999999);
            if ($redis->exists($this->generateCacheKey($code, $this->user->userData->email)) == 0) {
                break;
            } 
        }

        $configData = new TrustDeviceConfiguration(
            (int) $this->user->userData->id,
            $this->user->userData->email,
            $code,
            new \DateTime()
        );

        $restoreCodeStatus = $redis->set(
            $this->generateCacheKey($configData->getCode(), $configData->getUserEmail()),
            serialize($configData),
            60 * 30
        );

        if (!$restoreCodeStatus) {
            return false;
        }

        /** @var FundistEmailTemplate $fTemplate */
        $fTemplate = Core::DI()->get('fundist_email_template');
        if ($fTemplate->sendTrustDeviceConfirmationEmail(
            $this->buildDataForEmail($configData)
        ) === false) {
            throw new ApiException(_('Impossible to send email'), 403);
        }

        throw new ApiException(
            sprintf(_('unknown_device_message'),
                _cfg('userCountry'),
                System::getUserIP(false),
                $_SERVER['HTTP_USER_AGENT']
            ), 418);
            
    }

    /**
     * @param string $code
     * @param string $login
     * @param string $type
     * @throws \ErrorException
     * @codeCoverageIgnore
     */
    public function processCode(string $code, string $login, string $type = 'email'): bool
    {
        if ($type != 'email') {
            $userData = (User::getInstance())->getUserByLogin($login);
            if (empty($userData)) {
                throw new \ErrorException(_('User not found'), 401);
            }

            if ($userData->email == '') {
                throw new \ErrorException(_('You have to verify your email first'), 401);
            }

            $login = $userData->email;
        }

        $redis = System::redis();

        $redisKey = $this->generateCacheKey($code, $login);
        $codeData = $redis->get($redisKey);
        if (!$codeData) {
            throw new \ErrorException(_('Invalid confirmation code'));
        }

        $redis->delete($redisKey);

        /** @var TrustDeviceConfiguration $configData */
        $configData = unserialize($codeData);
        
        $diffDate = (new DateTime())->diff($configData->getTime());
        $lockTime = _cfg('lockTime2FA') ? _cfg('lockTime2FA') : 5;
        if ($diffDate->i >= $lockTime) {
            throw new ApiException(_('ConfirmationСodeExpired'), 403);
        }

        return $this->registerNewDevice($configData);
    }

    /**
     * @param int $deviceId
     * @param bool $status
     * @return bool
     * @throws \ErrorException
     * @codeCoverageIgnore
     */
    public function setDeviceTrustStatus(int $deviceId, bool $status = false): bool
    {
        $this->onlyAuthenticated();

        return $this->getRepository()->setDeviceTrustStatus($deviceId, $status);
    }

    /**
     * @param TrustDeviceConfiguration $configData
     * @return bool
     * @codeCoverageIgnore
     */
    public function registerNewDevice(TrustDeviceConfiguration $configData): bool
    {
        [
            'user_fingerprint' => $fingerprint,
            'user_agent' => $userAgent
        ] = $this->getAuthenticationDataFromEnv();

        $this->loginUserAfterRegisterNewDevice($configData);

        if (_cfg('trustDevicesEnabled') === self::$STATUS_ALWAYS) {
            return true;
        }

        return $this->getRepository()->registerNewDevice(
            $configData,
            $fingerprint,
            $userAgent
        );
    }

    /**
     * @param TrustDeviceConfiguration $configData
     * @return void
     * @codeCoverageIgnore
     */
    public function loginUserAfterRegisterNewDevice(TrustDeviceConfiguration $configData): void
    {
        $instanceUser = User::getInstance();
        $userData = $instanceUser->getUserByEmail($configData->getUserEmail());
        
        if (empty($userData)) {
            throw new \ErrorException(_('User not found'), 401);
        }

        $instanceUser->login([
            'login' => $userData->email,
            'pass'  => $userData->password
        ], true);
    }

    protected function buildDataForEmail(TrustDeviceConfiguration $config): array
    {
        $this->onlyAuthenticated();

        [
            'user_agent' => $userAgent
        ] = $this->getAuthenticationDataFromEnv();
        $userData = $this->user->userData;

        return [
            'first_name' => $userData->first_name,
            'last_name' => $userData->last_name,
            'email' => $userData->email,
            'user_agent' => $userAgent,
            'code' => $config->getCode(),
            'userIP' => System::getUserIP()
        ];
    }

    /**
     * @throws \ErrorException
     * @codeCoverageIgnore
     */
    protected function onlyAuthenticated(): bool
    {
        if ($this->user === null || $this->user->userData === false) {
            throw new \ErrorException(_('User is not authorized'));
        }

        return true;
    }

    protected function generateCacheKey(string $code, string $login): string
    {
        return sprintf('user_confirm_device_%s_%s', strtolower($login), $code);
    }

    protected function getAuthenticationDataFromEnv(): array
    {
        // @TODO: Create the ENV provider
        return [
            'user_fingerprint' => $_SERVER['HTTP_X_UA_FINGERPRINT'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
    }

    protected function getRepository(): ITrustDeviceRepo
    {
        return Core::DI()->get('repository.trust_device');
    }

    /**
     * @codeCoverageIgnore
     */
    protected function removeOldKeys($redis): void
    {

        $redisPattern = '*' . $this->user->userData->email . '*';
        $it = null;
        while ($keys = $redis->scan($it, $redisPattern, 1000000)) {
            foreach ($keys as $key) {
                $key = stristr($key, 'user_confirm_device_');
                $redis->delete($key);
            }
        }
    }
}
