<?php
namespace eGamings\WLC\Tests\SportsBook;

use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\SportsBook\ApiClientConfig;

class ApiClientConfigTest extends BaseCase 
{
    public function testClientId() 
    {
        $config = new ApiClientConfig();

        $this->assertEquals($config->setClientId(10), $config);
        $this->assertEquals($config->getClientId(), 10);
    }

    public function testURL() 
    {
        $config = new ApiClientConfig();

        $this->assertEquals($config->setURL('/test/url'), $config);
        $this->assertEquals($config->getURL(), '/test/url');
    }

    public function testGetEndPoint() 
    {
        $config = new ApiClientConfig();

        $this->assertEquals($config->getEndPoint('any'), '');
        $this->assertEquals($config->getEndPoint('widgets'), '/static/v1/widgets');
    }
}
