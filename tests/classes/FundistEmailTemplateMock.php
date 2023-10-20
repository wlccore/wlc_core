<?php

namespace eGamings\WLC\Tests;


use eGamings\WLC\FundistEmailTemplate;

class FundistEmailTemplateMock extends FundistEmailTemplate
{
    private static $status = true;

    public function __construct() {}

    /**
     * @return bool
     */
    public static function getStatus(): bool
    {
        return self::$status;
    }

    /**
     * @param bool $status
     */
    public static function setStatus(bool $status): void
    {
        self::$status = $status;
    }

    public function sendTrustDeviceConfirmationEmail(array $data): bool
    {
        return self::$status;
    }
}