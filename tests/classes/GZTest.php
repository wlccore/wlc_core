<?php

namespace eGamings\WLC\Tests;

use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\GZ;
use eGamings\WLC\System;
use eGamings\WLC\Cache\RedisCache;
use eGamings\WLC\Utils;
use ReflectionClass;

class GZTest extends BaseCase
{
    public static $testDirPath;
    public static $redisMock = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $frontReflection = new ReflectionClass(RedisCache::class);
        $redisMock = new RedisMock();

        $fProperty = $frontReflection->getProperty('redis');
        $fProperty->setAccessible(true);
        $fProperty->setValue($redisMock);

        self::$testDirPath = sprintf("%s/%s.%d/", __DIR__, "GZTest", time());

        self::$redisMock = $redisMock;

        self::cleanUpPreviousDirs();

        mkdir(self::$testDirPath);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        self::deleteDir(self::$testDirPath);
    }

    public static function cleanUpPreviousDirs(): void {
        foreach(glob(__DIR__ . "/GZTest.*", GLOB_ONLYDIR) as $tempDir) {
            self::deleteDir($tempDir);
        }
    }

    public static function deleteDir(string $dirPath): void
    {
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }

        rmdir($dirPath);
    }

    public function setUp(): void
    {
        $this->mockGamesData = [
            "merchants" => "This is not an array!",
            "categories" => [
                [
                    "Name" => [
                        "en" => "Name"
                    ]
                ]
            ],
            "games" => [
                "MyBloodyGame" => [
                    "ID" => 42,
                    "MobileUrl" => "mobile_id/mobile_url",
                    "Url" => 'default_id/default_url',
                    'IDCountryRestriction' => "1"
                ]
            ]
        ];

        $mock = $this->getMockBuilder(System::class)->disableOriginalConstructor()
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->getMock();

        $iProp = (new \ReflectionClass(System::class))->getProperty('instance');
        $iProp->setAccessible(true);
        $iProp->setValue($mock);

        $mock->expects($this->any())->method('getApiTID')->willReturn('test_123');
        $mock->expects($this->any())->method('runFundistAPI')->willReturn(
            json_encode($this->mockGamesData)
        );

        GZ::$cacheDir = self::$testDirPath;

        $nexus = tempnam(self::$testDirPath, 'nexus');
        $this->nexus = $nexus;

        $handle = fopen($nexus . '.json', "w");
        fwrite($handle, json_encode($this->mockGamesData));
        fclose($handle);

        $slim = GZ::fileNames('slim');
        GZ::$fileNames['nexus'] = pathinfo($nexus, PATHINFO_FILENAME);

        file_put_contents(self::$testDirPath . $slim.".json.gz", "HELLO");
    }

    public function testCanUse(): void
    {
        self::$redisMock::setGetReturn(serialize([]));
        $filters = ['merchant' => '0', 'category' => '1', 'order_by' => '2'];

        $this->assertFalse(GZ::canUse($filters, []), "If we have filters do not allow use GZ");

        $filters = ['merchant' => 0, 'category' => 0, 'order_by' => 0];
        $_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip';
        $query = ['slim' => '42'];

        $this->assertTrue(GZ::canUse($filters, $query), "We must use the GZip tech!");
    }

    public function testRebuildWatchCache(): void {
        global $cfg;
        $cfg['REDIS_PREFIX'] = 'redisPrefix';
        self::$redisMock::setGetReturn(serialize([]));
        $this->assertTrue(GZ::rebuildWatchCache(), "Must save all of them");
    }

    public function testCheckConfigVars(): void {
        self::$redisMock::setGetReturn(serialize("Not an array"));
        $this->assertTrue(GZ::checkConfigVars(), "Must rebuild all of them");

        $values = [];
        foreach(GZ::$watchedConfgVars as $var) {
            $values[$var] = _cfg($var);
        }
        self::$redisMock::setGetReturn(serialize($values));
        $this->assertTrue(GZ::checkConfigVars(), "Must save all of them");

        $values[GZ::$watchedConfgVars[0]] = "I don't say 'Blah blah blah'!";
        self::$redisMock::setGetReturn(serialize($values));
        $this->assertTrue(GZ::checkConfigVars(), "Must force update");
    }

    public function testcanClientHandleGZ(): void {
        $_SERVER['HTTP_ACCEPT_ENCODING'] = 'none,none';
        $this->assertFalse(GZ::canClientHandleGZ(), "This client does not support GZ, exit");
    }

    public function testGetGZ()
    {
        $this->assertEquals(GZ::getGZ(), 'HELLO');
    }

    public function testMakeMinFiles()
    {
        $this->assertTrue(GZ::makeMinFiles(), "Should create all files");

        GZ::$forceUpdate = false;
        $dir = self::$testDirPath;
        file_put_contents(Utils::joinPaths($dir, sprintf("%s.json", GZ::fileNames('nexus'))), 'nexus');
        file_put_contents(Utils::joinPaths($dir, sprintf("%s.json", GZ::fileNames('filled'))), 'filled');
        file_put_contents(Utils::joinPaths($dir, sprintf("%s.json", GZ::fileNames('slim'))), 'slim');

        $this->assertTrue(GZ::makeMinFiles(), "Already should exist");
    }

    public function testGetCacheDir(): void
    {
        GZ::$cacheDir = null;
        $this->assertEquals(GZ::getCacheDir(), _cfg('cache') . DIRECTORY_SEPARATOR);
    }
}
