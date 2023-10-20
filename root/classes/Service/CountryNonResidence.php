<?php

namespace eGamings\WLC\Service;

use eGamings\WLC\Db;
use eGamings\WLC\Provider\IUser;
use eGamings\WLC\Storage\SessionStorage;
use eGamings\WLC\System;
use ErrorException;

class CountryNonResidence
{
    /**
     * @var IUser|null
     */
    private $user = null;
    /**
     * @var array
     */
    private $countries = [];

    /**
     * @var string
     */
    private const COUNTRY_NON_RESIDENCE_KEY_PREFIX = 'country_non_residence_user_';

    /**
     * @param IUser|null $user
     */
    public function __construct(?IUser $user = null)
    {
        $this->countries = _cfg('CountryNonResidence') ? : [];
        $this->user = $user;
    }

    /**
     * @return bool
     */
    private function isEnable(): bool
    {
        return !empty($this->countries);
    }

    /**
     * @return bool
     * @throws ErrorException
     */
    protected function onlyAuthenticated(): bool
    {
        if ($this->user === null || $this->user->userData === false) {
            throw new ErrorException(_('User is not authorized'));
        }

        return true;
    }

    /**
     * @param string $route
     * @return bool
     */
    public function isBlocked(string $route = '', string $method = '', ?array $request = [], bool $checkUserInfoRoute = false): bool
    {
        if (!$this->isEnable() || $this->user === null || $this->user->userData === false) {
            return false;
        }

        if (!in_array(System::getGeoData(), $this->countries)) {
            return false;
        }

        return $this->checkRoute($route, $method, $request, $checkUserInfoRoute)
            && SessionStorage::getInstance()->get(self::COUNTRY_NON_RESIDENCE_KEY_PREFIX . $this->user->userData->id) !== true;
    }

    /**
     * @param string $route
     * @param string $method
     * @param array|null $request
     * @param $checkUserInfoRoute
     * @return bool
     */
    private function checkRoute(string $route, string $method, ?array $request, $checkUserInfoRoute = false): bool
    {
        if ($checkUserInfoRoute && 'api/v1/userInfo' === $route) {
            return true;
        }

        if (
            'api/v1/liveChat' === $route ||
            'api/v1/deposits' === $route ||
            'api/v1/transfer' === $route ||
            'api/v1/chat/user' === $route ||
            'api/v1/chat/rooms' === $route ||
            'api/v1/withdrawals' === $route ||
            'api/v1/games/launch' === $route ||
            'api/v1/supportEmail' === $route ||
            'api/v1/publicAccount' === $route ||
            'api/v1/chat/password' === $route ||
            'api/v1/chat/user/data' === $route ||
            'api/v1/publicAccount/join' === $route ||
            'api/v1/withdrawals/complete' === $route ||
            'api/v1/publicAccount/makePermanent' === $route ||
            'api/v1/loyalty/check_promocode' === $route ||
            ('api/v1/userSelfExclusion' === $route && $method !== 'get') ||
            ('api/v1/games' === $route && !empty($request['launchCode'])) ||
            (strpos($route, 'api/v1/store') !== false && $method === 'put') ||
            (strpos($route, 'api/v1/bonuses') !== false && $method !== 'get') ||
            (strpos($route, 'api/v1/cashback') !== false && $method === 'post') ||
            (strpos($route, 'api/v1/messages') !== false) ||
            (strpos($route, 'api/v1/tournaments') !== false && $method !== 'get')
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return void
     * @throws ErrorException
     */
    public function saveConfirmation(): void
    {
        if (!$this->isEnable()) {
            throw new ErrorException(_('This feature is disabled'));
        }

        $this->onlyAuthenticated();

        Db::query(
            'INSERT INTO `countries_confirmation_non_residence` SET ' .
            '`user_id` = "' . Db::escape($this->user->userData->id) . '",' .
            '`country_iso3` = "' . Db::escape(System::getGeoData()) . '",' .
            '`ip` = "' . Db::escape(System::getUserIP()) . '",' .
            '`add_date` = NOW()'
        );

        SessionStorage::getInstance()->set(self::COUNTRY_NON_RESIDENCE_KEY_PREFIX . $this->user->userData->id, true);
    }
}
