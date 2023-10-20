<?php
namespace eGamings\WLC\Provider\Service;

use eGamings\WLC\Provider\Domain\Captcha\IRenderable;

interface ICaptcha {
    public function existsRecord(): bool;
    public function isBanned(): bool;
    public function buildCaptcha(): ICaptcha;
    public function getCaptcha(): ?IRenderable;
    public function addAttempt(): bool;
    public function proceedResponse(string $response): bool;
}