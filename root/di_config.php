<?php

// @codeCoverageIgnoreStart
use eGamings\WLC\System;
use function DI\create;

return [
    'service.trust_device' => create(\eGamings\WLC\Service\TrustDevice::class),
    'service.captcha' => create(\eGamings\WLC\Service\Captcha::class),
    'repository.trust_device' => create(\eGamings\WLC\Repository\TrustDevice::class),
    'repository.captcha' => create(\eGamings\WLC\Repository\Captcha::class),
    'user' => create(\eGamings\WLC\User::class),
    'db' => \eGamings\WLC\Db::class,
    'fundist_email_template' => create(\eGamings\WLC\FundistEmailTemplate::class),
    'redis' => function () {
        return System::redis();
    }
];
// @codeCoverageIgnoreEnd