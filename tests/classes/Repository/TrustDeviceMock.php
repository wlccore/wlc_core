<?php
namespace eGamings\WLC\Tests\Repository;

use eGamings\WLC\Domain\TrustDeviceConfiguration\TrustDeviceConfiguration;
use eGamings\WLC\Provider\Repository\ITrustDevice;

class TrustDeviceMock implements ITrustDevice
{
    private $devices;

    public function __construct() {
        $this->devices = [];
    }

    public function getAllDevices(int $user_id): array
    {
        return $this->devices;
    }

    /**
     * @return array
     */
    public function getDevices(): array
    {
        return $this->devices;
    }

    /**
     * @param array $devices
     */
    public function setDevices(array $devices): void
    {
        $this->devices = $devices;
    }

    public function issetDevice(int $user_id, string $fingerprint, string $user_agent)
    {
        return count($this->devices) > 0;
    }

    public function registerNewDevice(TrustDeviceConfiguration $config, string $fingerprint, string $user_agent): bool
    {
        return true;
    }
}