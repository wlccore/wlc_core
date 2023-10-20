<?php
namespace eGamings\WLC\Tests;

use eGamings\WLC\ConfigProcessor;
use eGamings\WLC\Tests\BaseCase;

class ConfigProcessorTest extends BaseCase
{
    /** @var \eGamings\WLC\ConfigProcessor $configProcessorInstance */
    protected $configProcessorInstance = null;
    protected $testConfig = [];
    protected $testConfigDefinition = [];

    public function setUp(): void {
        $this->testConfig = [
            'site_ip_list' => ['127.0.0.1']
        ];

        $this->testConfigDefinition = [
            'site_ip_list' => [
                'type' => 'array',
                'errors' => [
                    'errorMessage' => 'Use an Vec<IPv4Addr>!',
                    ConfigProcessor::$VALIDATOR_TYPES_MISMATCH => 'This is not an Vec<IPv4Addr>',
                    ConfigProcessor::$VALIDATOR_ERROR_VALUE_NOT_SET => 'There is no such value'
                ],
                'validator' => static function (array $vars): int {
                    foreach($vars as $var) {
                        if (filter_var($var, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) === false) {
                            return ConfigProcessor::$VALIDATOR_ERROR;
                        }
                    }

                    return ConfigProcessor::$VALIDATOR_OK;
                },
                'validatorError' => 'There is NOT an array of IPs'
            ]
        ];

        $this->configProcessorInstance = new ConfigProcessor($this->testConfig, $this->testConfigDefinition);
    }

    public function tearDown(): void {
        $this->configProcessorInstance = null;
        $this->testConfig = [];
        $this->testConfigDefinition = [];
    }

    public function testGetConfigValue(): void
    {
        $this->configProcessorInstance->validateConfig();

        $this->assertTrue(ConfigProcessor::isValidateOk());
    }

    public function testDefinitionNotFound(): void
    {
        unset($this->testConfigDefinition['site_ip_list']);

        $this->configProcessorInstance->setConfigDefinition($this->testConfigDefinition);
        $this->configProcessorInstance->getConfigValue('site_ip_list');

        $this->assertEquals(ConfigProcessor::getState(), ConfigProcessor::$VALIDATOR_ERROR);
    }

    public function testGetCorruptedValue(): void
    {
        $this->testConfig['site_ip_list'] = 42;

        $this->configProcessorInstance->setConfig($this->testConfig);
        $this->configProcessorInstance->validateConfig();

        $this->assertEquals(ConfigProcessor::getState(), ConfigProcessor::$VALIDATOR_ERROR);

        $this->assertEquals($this->configProcessorInstance->getErrors(), [
            'site_ip_list' => "This is not an Vec<IPv4Addr>, got 'integer'"
        ]);
    }

    public function testGetUnsetValue(): void
    {
        unset($this->testConfig['site_ip_list']);

        $this->configProcessorInstance->setConfig($this->testConfig);
        $this->configProcessorInstance->validateConfig();

        $this->assertEquals(ConfigProcessor::getState(), ConfigProcessor::$VALIDATOR_ERROR);

        $this->assertEquals($this->configProcessorInstance->getErrors(), [
            'site_ip_list' => "There is no such value"
        ]);
    }

    public function testUnsetFallbackValue(): void
    {
        unset($this->testConfig['site_ip_list']);

        $this->configProcessorInstance->setConfig($this->testConfig);
        $this->configProcessorInstance->validateConfig([
            'site_ip_list' => ['127.0.0.1']
        ]);

        $this->assertEquals(ConfigProcessor::getState(), ConfigProcessor::$VALIDATOR_OK);

        $this->assertEquals($this->configProcessorInstance->getValidatedValues(), [
            'site_ip_list' => ['127.0.0.1']
        ]);
    }

    public function testGetWrongTValue(): void
    {
        $this->testConfig['site_ip_list'] = ['not_an_ip'];
        $this->configProcessorInstance->setConfig($this->testConfig);
        $this->configProcessorInstance->validateConfig();

        $this->assertEquals(ConfigProcessor::getState(), ConfigProcessor::$VALIDATOR_ERROR);

        $this->assertEquals($this->configProcessorInstance->getErrors(), [
            'site_ip_list' => "There is NOT an array of IPs, got 'array'"
        ]);
    }
}
