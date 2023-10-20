<?php
namespace eGamings\WLC\Tests;

use ReflectionClass;
use eGamings\WLC\Seo;
use eGamings\WLC\Tests\BaseCase;

class SeoTest extends BaseCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    public function testSeoApiCall() {
        Seo::$_apiEnabled = false;
        $this->assertFalse(Seo::getInstance()->wpApiCall('ApiUrl'), 'Disabled api must return false');
        Seo::$_apiEnabled = true;
    }

    public function testSeoGetInstance() {
        $seoRef = new ReflectionClass(Seo::class);
        $seo = $seoRef->newInstanceWithoutConstructor();

        $seoInst = $seoRef->getProperty('instance');
        $seoInst->setAccessible(true);
        $seoInst->setValue(null);

        $seo = Seo::getInstance();
        $this->assertTrue(is_object($seo), 'Check seo instance is object');
        $seoInst->setValue(null);
    }

    public function testFetchSeo() {
        global $cfg;
        $cfg['qtranslateMode'] = "pre-path";

        $mock = $this->getMockBuilder(Seo::class)
            ->setMethods(['wpApiCall'])
            ->getMock();

        $seoRef = new ReflectionClass(Seo::class);
        $seo = $seoRef->newInstanceWithoutConstructor();

        $seoInst = $seoRef->getProperty('instance');
        $seoInst->setAccessible(true);
        $seoInst->setValue($mock);

        $mock->expects($this->exactly(6))->method('wpApiCall')->willReturn(
            '[{"id": 1}]',
            '[]', '[]', '[]', '[]', '[]', '[]', '[]', '[]'
        );

        Seo::$_apiEnabled = false;
        $result = $mock->fetchSeo();
        Seo::$_apiEnabled = false;
        $this->assertEquals(count($result), 5, 'Seo result must be empty');

        $seoInst->setValue(null);
    }

    public function testSeoFetchFailure() {
        $mock = $this->getMockBuilder(Seo::class)
            ->setMethods(['wpApiCall'])
            ->getMock();

        $seoRef = new ReflectionClass(Seo::class);
        $seo = $seoRef->newInstanceWithoutConstructor();

        $seoInst = $seoRef->getProperty('instance');
        $seoInst->setAccessible(true);
        $seoInst->setValue($mock);

        $mock->expects($this->any())->method('wpApiCall')->willReturn(
            '[{"id": 1}]',
            '[{"acf": {"state": "one", "opengraph_title": "Title", "opengraph_description": "Desc", "opengraph_keywords": "keys", "opengraph_image": "img", "url": "/url"}},
            {"acf": {"state": "two", "opengraph_title": "Title", "opengraph_description": "Desc", "opengraph_keywords": "keys", "opengraph_image": "img", "url": "/url"}}]'
        );

        $result = $mock->fetchSeo();
        $this->assertEquals(count($result), 5, 'Seo result must be empty');

        $seoInst->setValue(null);
    }
}
