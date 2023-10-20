<?php
namespace eGamings\WLC\Repository;

use eGamings\WLC\Core;
use eGamings\WLC\Domain\Captcha\Record;
use eGamings\WLC\Provider\IDb;
use eGamings\WLC\Provider\Repository\ICaptcha;

class Captcha implements ICaptcha
{
    protected static $MySQLDateTimeFormat = 'Y-m-d H:i:s';

    public function existsIpRecord(string $ip, ?Record &$record = null): bool
    {
        $db = $this->getDB();

        $result = $db::fetchRow(
            sprintf("SELECT * FROM `ip_checks` WHERE `ip` = '%s'", $db::escape($ip))
        );

        if ($result !== false) {
            $record = $this->createRecordFromRawData($result);
            return true;
        }

        return false;
    }

    public function createRecord(string $ip): bool
    {
        $db = $this->getDB();

        $result = $db::query(
            sprintf("INSERT INTO `ip_checks` (`ip`, `first_date`, `last_date`, `count_last_hour`, `count_last_day`) VALUES ('%s', NOW(), NOW(), 1, 1)",
                $db::escape($ip)
            )
        );

        return $result !== false;
    }

    public function updateRecord(Record $record): bool
    {
        $db = $this->getDb();

        $db::query(
            sprintf('UPDATE `ip_checks` SET `first_date` = "%s", `last_date` = "%s", `count_last_hour` = %u, `count_last_day` = %u WHERE `id` = %u',
                $db::escape($record->getFirstDate()->format(self::$MySQLDateTimeFormat)),
                $db::escape($record->getLastDate()->format(self::$MySQLDateTimeFormat)),
                $db::escape($record->getCountLastHour()),
                $db::escape($record->getCountLastDay()),
                $db::escape($record->getId())
            )
        );

        return $db::affectedRows() != 0;
    }

    public function deleteRecord(string $ip): bool
    {
        $db = $this->getDb();

        $db::query(
            sprintf('DELETE FROM `ip_checks` WHERE `ip` = "%s"',
                $db::escape($ip)
            )
        );

        return $db::affectedRows() != 0;
    }

    protected function createRecordFromRawData(\stdClass $rawData): Record
    {
        $record = new Record();

        $record->setId($rawData->id);
        $record->setIp($rawData->ip);
        $record->setFirstDate(new \DateTimeImmutable($rawData->first_date));
        $record->setLastDate(new \DateTimeImmutable($rawData->last_date));
        $record->setCountLastHour($rawData->count_last_hour);
        $record->setCountLastDay($rawData->count_last_day);

        return $record;
    }

    /**
     * @return IDb
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    protected function getDb()
    {
        return Core::DI()->get('db');
    }
}