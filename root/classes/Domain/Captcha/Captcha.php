<?php
namespace eGamings\WLC\Domain\Captcha;

use eGamings\WLC\Provider\Domain\Captcha\IRenderable;
use Gregwar\Captcha\CaptchaBuilder;

class Captcha implements IRenderable
{
    /** @var \Gregwar\Captcha\CaptchaBuilder|null  */
    protected $controller = null;
    protected $rendered = '';

    public function __construct(?string $phrase = null) {
        $this->controller = new CaptchaBuilder($phrase);
        $this->controller->build();
    }

    public function render(): string
    {
        if ($this->rendered === '') {
            $this->rendered = $this->controller->get(100);
        }

        return $this->rendered;
    }

    public function output(): void
    {
        $this->controller->output();
    }

    public function getRawData(): array
    {
        return [
            'controller' => $this->controller
        ];
    }
}