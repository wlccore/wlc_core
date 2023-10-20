<?php
namespace eGamings\WLC\Tests;

use eGamings\WLC\Api;

class ApiTest extends BaseCase {

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
    }

    public function testUserSendEmail() {
        $_POST['params'] = json_encode(
            [
            'to' => 'qwe@aas.rr',
            'subject' => 'test',
            'message' => 'Test msg',
            ]
        );
        $object = new Api();
        ob_start();
        $result = $this->invokeMethod($object, 'userSendEmail', [true]);
        $content = ob_get_clean();
        $this->assertFalse($result);
        $this->assertTrue(!empty($content));
    }

}
