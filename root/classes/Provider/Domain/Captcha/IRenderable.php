<?php
namespace eGamings\WLC\Provider\Domain\Captcha;

interface IRenderable {
    public function render(): string;
    public function output(): void;
    public function getRawData(): array;
}