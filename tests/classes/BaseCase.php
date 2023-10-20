<?php
namespace eGamings\WLC\Tests;

use PHPUnit\Framework\TestCase;

class BaseCase extends TestCase {


    /**
     * @param object &$object Instantiated object that we will run method on
     * @param string $methodName Method name to call
     * @param mixed $parameters Parameters to pass into method
     * @return mixed Method return
     * @throws \ReflectionException
     */
    public function invokeMethod(&$object, $methodName, $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invoke($object, ...$parameters);
    }

}
