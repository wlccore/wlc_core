<?php
namespace eGamings\WLC\Tests\Recaptcha;

use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Recaptcha;

class RecaptchaTest extends BaseCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testRecaptcha(): void
    {
        $recaptcha = new Recaptcha();

        global $cfg;
        $cfg['recaptchaLog'] = true;

        $reflection = new \ReflectionClass($recaptcha);
        $property = $reflection->getProperty('url');
        $property->setAccessible(true);
        $property->setValue($recaptcha, '/');

        $this->assertFalse($recaptcha->enabled());
        $this->assertEquals($recaptcha->getSiteKey(), '');

        $this->assertFalse($recaptcha->check(''));
        $this->assertFalse($recaptcha->check('token'));
    }
}
