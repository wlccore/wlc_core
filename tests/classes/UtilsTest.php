<?php
namespace eGamings\WLC\Tests;

use eGamings\WLC\Utils;
use eGamings\WLC\Tests\BaseCase;

class UtilsTest extends BaseCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    public function testAtomicReplaceWithoutFile(): void
    {
        $tempFileName = _cfg('cache') . '/utils_atomic_test_'.time().'.txt';
        $atomicReplace = Utils::$atomicReplace;
        Utils::$atomicReplace = false;
        $result = Utils::atomicFileReplace($tempFileName, 'test');
        Utils::$atomicReplace = $atomicReplace;

        $this->assertTrue($result, 'Check replace is successful');
        $this->assertFalse(file_exists($tempFileName), 'File must not be created');

        Utils::$atomicReplace = true;
        $tmpFile = tmpfile();
        $tmpFilePath = stream_get_meta_data($tmpFile)['uri'];
        $this->assertTrue(Utils::atomicFileReplace($tmpFilePath, 'data'));
    }

    public function testObfuscateEmail(): void
    {
        $email = 'wlc_core@test.com';
        $test = 'wlc_****@****.com';
        $result = Utils::obfuscateEmail($email);
        $this->assertEquals($test, $result, 'Check expected result');
    }

    public function testEncodeURIComponent(): void
    {
        $this->assertEquals(Utils::encodeURIComponent('test=42'), 'test%3D42', 'Should be encoded');
    }

    public function testHideStringWithWildcards(): void
    {
        $this->assertEquals(Utils::hideStringWithWildcards('something!'),    'somet*****');
        $this->assertEquals(Utils::hideStringWithWildcards('Сергей И.'),     'Серге*****');
        $this->assertEquals(Utils::hideStringWithWildcards('something!', 6), 'som***');
        $this->assertEquals(Utils::hideStringWithWildcards('so**'),          'so********');
    }

    public function testGenerateSid(): void
    {
        $this->assertEquals(preg_match('/[0-9a-f]{3}-[0-9a-f]{3}-[0-9a-f]{3}-[0-9a-f]{3}/', Utils::generateSid()),1);
    }
}
