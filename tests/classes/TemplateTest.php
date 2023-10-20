<?php
namespace eGamings\WLC\Tests;

use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Template;
use PHP_CodeSniffer\Generators\HTML;

class TemplateTest extends BaseCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    public function testTemplateInit() {
        $tpl = new Template();
        $this->assertTrue(is_object($tpl), 'Class creation success');
    }

    public function testAppendCounters() {
        global $cfg;

        $template = new Template();
        $goodHTML = <<<HTML
<html>
  <head>
    <title>Test title</title>
  </head>
  <body>
      <div>Body</div>
  </body>
</html>
HTML;

        $cfg['counters'] = [
          'google_tag_manager' => [
            'key' => 'some_key_for_the_google_tm'
          ],
          'google_analytics' => [
            'key' => 'some_key_for_the_google_analytics'
          ]
        ];
        // $counterTemplate = $template->twig->render('counters.tpl', $cfg['counters']);
        $filledHTML = $template->addCounters('index', $goodHTML);


        $this->assertTrue(stripos($filledHTML, 'https://www.googletagmanager.com/gtm.js?id=') !== false, "RRRRRRR");

        // $this->assertSame($goodHTML, $template->addCounters('__test_for_counter_template_name__', $goodHTML), "Shouldn't change the result if the template is not allowed");

        // $cfg['counters'] = null;
        // $this->assertSame($goodHTML, $template->addCounters('index', $goodHTML), "Shouldn't change the result if the counters config is empty");
    }
}
