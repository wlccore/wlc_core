<?php

namespace eGamings\WLC\Tests;

use eGamings\WLC\Affiliate;

class AffiliateTest extends BaseCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    public function testGetAffiliateIdByUrl()
    {
        $url = json_encode([
            'Url' => 0,
            'FaffCodes' => ['1'],
        ]);
        $id = Affiliate::getAffiliateIdByUrl($url);
        $this->assertEquals(1, $id);
    }
}