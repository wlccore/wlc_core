<?php
namespace eGamings\WLC\Tests;

use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\GeoIp;

class GeoIpTest extends BaseCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    public function testLocalIpList() {
        $ipMap = [
            '127.0.0.1' => true,
            '10.23.25.1' => true,
            '192.168.88.1' => true,
            '87.99.94.34' => false
        ];

        foreach($ipMap as $ip => $isLocalIp) {
            $this->assertEquals(GeoIp::isLocalIp($ip), $isLocalIp);
        }
    }

    public function testCountryMapDefault() {
        $testLocalesMap = [
            'ae' => 'ar',
            'gb' => 'en',
            'ru' => 'ru',
            'ua' => 'ru',
            'kz' => 'ru',
            'de' => 'de'
        ];

        foreach($testLocalesMap as $testCountry => $testLanguage) {
            $this->assertEquals($testLanguage, GeoIp::countryLanguage($testCountry));
        }
    }

    public function testCountryMapOverride() {
        $testLocalesMap= [
            'ae' => 'ar',
            'gb' => 'en',
            'ru' => 'ru',
            'ua' => 'en',
            'kz' => 'ru',
            'de' => 'de'
        ];

        $origLocales = GeoIp::$locales;
        GeoIp::$locales['UA'] = 'en';

        foreach($testLocalesMap as $testCountry => $testLanguage) {
            $this->assertEquals($testLanguage, GeoIp::countryLanguage($testCountry));
        }

        GeoIp::$locales = $origLocales;
    }

}
