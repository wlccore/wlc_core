<?php
namespace eGamings\WLC\Service;

use eGamings\WLC\Core;
use eGamings\WLC\Domain\Captcha\Record;
use eGamings\WLC\Logger;
use eGamings\WLC\Provider\Domain\Captcha\IRenderable;
use eGamings\WLC\Provider\Service\ICaptcha;
use eGamings\WLC\System;

class Captcha implements ICaptcha
{
    public static $NONE = 0x00;
    public static $HOUR = 0x01;
    public static $DAY  = 0x02;

    public static $BANNED_BY  = 0x00;
    public static $RETRY_AFTER = null;

    /** @var IRenderable|null */
    protected $captchaInstance = null;
    protected $uniqueUserMark  = '';

    public function __construct(string $uniqueUserMark) {
        self::$RETRY_AFTER = new \DateTime();
        $this->uniqueUserMark = $uniqueUserMark;
    }

    public function existsRecord(): bool
    {
        return !!Core::DI()->get('redis')->get($this->buildCaptchaKey());
    }

    public function isBanned(): bool
    {
        /** @var Record $record */
        $record = null;
        $result = $this->getRepo()->existsIpRecord($this->getUserIp(), $record);

        if ($result === true) {
            $ban = $this->issetBanAndType($record);

            if ($ban === true) {
                $this->incrDay($record);
            }

            return true;
        }

        return false;
    }

    public function buildCaptcha(): ICaptcha
    {
        $this->captchaInstance = new \eGamings\WLC\Domain\Captcha\Captcha();

        [
            'controller' => $captchaController
        ] = $this->captchaInstance->getRawData();

        $this->rememberCaptcha($captchaController->getPhrase());

        return $this;
    }

    public function getCaptcha(): ?IRenderable
    {
        return $this->captchaInstance;
    }

    public function addAttempt(): bool
    {
        /** @var Record $record */
        $record = null;
        $userIp = $this->getUserIp();
        $repo   = $this->getRepo();
        $result = $repo->existsIpRecord($userIp, $record);

        if ($result === false) {
            return $this->getRepo()->createRecord($userIp);
        } else {
            return $this->updateRecord($record, $record->getCountLastHour() + 1, $record->getCountLastDay() + 1);
        }
    }

    public function incrDay(Record $record): bool
    {
        $record->incCountLastDay();
        return $this->updateRecord($record, null, $record->getCountLastDay());
    }

    public function incrHour(Record $record): bool
    {
        $record->incCountLastHour();
        return $this->updateRecord($record, $record->getCountLastHour());
    }

    public function proceedResponse(string $response): bool
    {
        /** @var \Redis $redis */
        $redis = Core::DI()->get('redis');
        $redisKey = $this->buildCaptchaKey();
        $captchaCode = $redis->get($redisKey);
        /** @var Record|null $record */
        $record = null;

        // Found the response without the existing captcha
        if (!$captchaCode) {
            return true;
        }

        $this->getRepo()->existsIpRecord($this->getUserIp(), $record);

        if ($record !== null && $this->issetBanAndType($record) === true && self::$BANNED_BY === self::$DAY) {
            $this->incrDay($record);
            $this->showDayBan();
        }

        // Case-insensitive
        if ($captchaCode && strtolower(trim($response)) === strtolower($captchaCode)) {
            $redis->del($redisKey);

            if ($record !== null) {
                // Drop the hour, do not inc the day
                $this->updateRecord($record, 0);
            }

            return true;
        } else {
            if ($record !== null) {
                $this->incrDay($record);
            }
        }

        return false;
    }


    public function showDayBan(): void
    {
        http_response_code(429);
        header(sprintf("Retry-After: %s", self::$RETRY_AFTER->format(\DateTime::RFC7231)));
        exit(0);
    }

    protected function issetBanAndType(Record &$record): bool
    {
        [
            'hour' => $hour,
            'day'  => $day
        ] = _cfg('captchaConfig');

        $currentDate = new \DateTimeImmutable();

        if ($record->getCountLastDay() >= $day && $currentDate->modify("-1 day") <= $record->getLastDate()) {
            self::$BANNED_BY = self::$DAY;
            self::$RETRY_AFTER = $record->getLastDate()->modify("+1 day");

            return true;
        }

        if ($record->getCountLastHour() >= $hour && $currentDate->modify("-1 hour") <= $record->getLastDate()) {
            self::$BANNED_BY = self::$HOUR;
            self::$RETRY_AFTER = $record->getLastDate()->modify("+1 hour");

            return true;
        }

        return false;
    }

    protected function updateRecord(Record &$record, ?int $hour = null, ?int $day = null): bool
    {
        $this->flushCountersByDate($record);

        if ($day !== null) {
            $record->setCountLastDay($day);
        }

        if ($hour !== null) {

            $record->setCountLastHour($hour);
        }

        return $this->getRepo()->updateRecord($record);
    }

    protected function flushCountersByDate(Record &$record, ?int $defaultHour = 1, ?int $defaultDay = 1): Record
    {
        $lastDate = $record->getLastDate();
        $currentDate = new \DateTimeImmutable();

        $record->setLastDate($currentDate);

        if ($currentDate->modify('-1 day') >= $lastDate) {
            $record->setCountLastDay($defaultDay ?? 0);
        }

        if ($currentDate->modify('-1 hour') >= $lastDate) {
            $record->setCountLastHour($defaultHour ?? 0);
        }

        return $record;
    }

    protected function rememberCaptcha(string $secret): bool
    {
        /** @var \Redis $redis */
        $redis = Core::DI()->get('redis');
        $redisKey = $this->buildCaptchaKey();

        // Dropping the old key if exists
        $redis->del($redisKey);

        return $redis->set(
            $redisKey,
            $secret,
            [
                'ex' => 60 * 60 // 1 hour TTL
            ]
        );
    }

    protected function buildCaptchaKey(): string
    {
        return join('_', [
            Logger::getInstanceName(),
            $this->uniqueUserMark,
            $this->getUserIp()
        ]);
    }

    protected function getUserIp(): string
    {
        return System::getUserIP(false);
    }

    protected function getRepo(): \eGamings\WLC\Provider\Repository\ICaptcha
    {
        return Core::DI()->get('repository.captcha');
    }
}