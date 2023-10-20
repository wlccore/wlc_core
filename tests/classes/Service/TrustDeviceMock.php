<?php
namespace eGamings\WLC\Tests\Service;

use eGamings\WLC\Provider\Service\ITrustDevice;

class TrustDeviceMock implements ITrustDevice
{
    private $devicesList = [];

    public function fetchAllDevices(): array
    {
        return $this->devicesList;
    }

    public function processCode(string $code, string $login, string $type = 'email'): bool
    {
        if ($code == '123456') {
            return true;
        } else {
            throw new \ErrorException(_('Code is incorrect'));
        }
    }

    /**
     * @return array
     */
    public function getDevicesList(): array
    {
        return $this->devicesList;
    }

    /**
     * @param array $devicesList
     */
    public function setDevicesList(array $devicesList): void
    {
        $this->devicesList = $devicesList;
    }

    public function setDeviceTrustStatus(int $deviceId, bool $status = false): bool
    {
        return true;
    }

    public function checkDevice(?string &$error): bool
    {
        return true;
    }
}