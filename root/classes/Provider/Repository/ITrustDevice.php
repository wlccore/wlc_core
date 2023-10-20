<?php
namespace eGamings\WLC\Provider\Repository;

use eGamings\WLC\Domain\TrustDeviceConfiguration\TrustDeviceConfiguration;

interface ITrustDevice {
    public function getAllDevices(int $user_id): array;
    public function issetDevice(int $user_id, string $fingerprint, string $user_agent);
    public function registerNewDevice(TrustDeviceConfiguration $config, string $fingerprint, string $user_agent): bool;
}