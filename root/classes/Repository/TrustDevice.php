<?php

namespace eGamings\WLC\Repository;

use eGamings\WLC\Core;
use eGamings\WLC\Domain\TrustDeviceConfiguration\TrustDeviceConfiguration;
use eGamings\WLC\Provider\IDb;
use eGamings\WLC\Provider\Repository\ITrustDevice;

class TrustDevice implements ITrustDevice
{
    public function issetDevice(int $user_id, string $fingerprint, string $user_agent)
    {
        $db = $this->getDB();

        return $db::fetchRow(
            sprintf("SELECT `id`, `is_trusted` FROM `users_devices` WHERE
                                       `user_id` = %u AND
                                       `fingerprint_hash` = '%s' AND
                                       `user_agent` = '%s'",
                $db::escape($user_id),
                $db::escape($fingerprint),
                $db::escape($user_agent)
            )
        );
    }

    public function registerNewDevice(TrustDeviceConfiguration $config, string $fingerprint, string $user_agent): bool
    {
        $issetDevice = $this->issetDevice($config->getUserId(), $fingerprint, $user_agent);

        if ($issetDevice === false) {
            $db = $this->getDB();

            $result = $db::query(
                sprintf("INSERT INTO `users_devices` (`user_id`, `fingerprint_hash`, `user_agent`, `is_trusted`, `updated`) VALUES (%u, '%s', '%s', %u, NOW())",
                    $db::escape($config->getUserId()),
                    $db::escape($fingerprint),
                    $db::escape($user_agent),
                    1
                )
            );

            return $result !== false;
        } else {
            return $this->setDeviceTrustStatus($issetDevice->id, true);
        }
    }

    public function getAllDevices(int $user_id): array
    {
        $db = $this->getDB();

        $result = $db::fetchRows(
            sprintf("SELECT `id`, `fingerprint_hash`, `user_agent`, `is_trusted`, `updated` FROM `users_devices` WHERE `user_id` = %u",
                $db::escape($user_id)
            )
        );

        if ($result === false) {
            $result = [];
        }

        $result = (array) $result;

        foreach ($result as &$row) {
            $row->id = (int) $row->id;
            $row->is_trusted = (bool) $row->is_trusted;
        }

        return $result;
    }

    public function setDeviceTrustStatus(int $deviceId, bool $status = false): bool
    {
        $db = $this->getDb();

        $db::query(
            sprintf('UPDATE `users_devices` SET `is_trusted` = %u, `updated` = NOW() WHERE `id` = %u',
                $db::escape($status),
                $db::escape($deviceId)
            )
        );

        return $db::affectedRows() != 0;
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