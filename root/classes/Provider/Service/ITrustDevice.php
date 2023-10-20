<?php
namespace eGamings\WLC\Provider\Service;

interface ITrustDevice {
    public function fetchAllDevices(): array;
    public function processCode(string $code, string $login, string $type = 'email'): bool;
    public function setDeviceTrustStatus(int $deviceId, bool $status = false): bool;
    public function checkDevice(?string &$error): bool;
}