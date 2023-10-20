<?php
namespace eGamings\WLC\Tests;

use eGamings\WLC\Email;

class Emailest extends BaseCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        DbMock::setConnection(null);
        DbMock::setConnClass('eGamings\WLC\Tests\DbConnectionMock');
        DbConnectionMock::$hasConnectError = false;
    }

    public function testMakeMessage() {
        $msgBody = <<<EOF
<html>
<head>
<title>Test Mail Message</title>
</head>
<body>
<div>This is test message body</div>
</body>
</html>
EOF;
        $email = new \ReflectionClass(Email::class);
        $method = $email->getMethod('makeMessage');
        $method->setAccessible(true);
        $result = $method->invokeArgs(null, ['test@test.com', 'Subject', $msgBody, ["no-reply@casino.com"]]);
        $this->assertTrue(is_object($result), 'Result must be swift object');
    }
}
