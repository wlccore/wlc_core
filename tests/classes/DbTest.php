<?php
namespace eGamings\WLC\Tests;

use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Db;
use eGamings\WLC\Tests\DbMock;
use eGamings\WLC\Tests\DbConnectionMock;

class DbTest extends BaseCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        DbMock::setConnection(null);
        DbMock::setConnClass('eGamings\WLC\Tests\DbConnectionMock');
        DbConnectionMock::$hasConnectError = false;
    }

    public function testGetConnection() {
        Db::connect();
        $this->assertEquals(true, is_object(DbMock::getConnection()), 'Check if we have connection');
        $this->assertEquals('eGamings\WLC\Tests\DbConnectionMock', get_class(DbMock::getConnection()), 'Check if we have test connection handler');
    }

    public function testEscape() {
        DbMock::setConnection(null);
        DbConnectionMock::$hasConnectError = false;
        
        $value = "test_123";
        $this->assertEquals(Db::escape($value), $value, "Check escaped string value");

        $value = ["test_1", "test_2"];
        $this->assertEquals(Db::escape($value), $value, "Check if escaped array value");
    }

    public function testEscapeNotConnected() {
        DbMock::setConnection(null);
        DbConnectionMock::$hasConnectError = true;
        $value = "test_123";
        $this->assertEquals(false, Db::escape($value), "Check escaped string not connected value");
    }

    public function testClose() {
        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['close'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $closeResult = true;
        $conn
            ->expects($this->exactly(1))
            ->method('close')
            ->willReturn($closeResult);
        
        DbMock::setConnection($conn);
        
        $this->assertEquals(Db::close(), $closeResult, "Check connection close");
    }

    public function testCloseWithoutConnection() {
        DbMock::setConnection(null);
        $this->assertEquals(Db::close(), true, "Check connection close without connection");
    }

    public function testError() {
        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['close'])
            ->disableOriginalConstructor()
            ->getMock();

        DbMock::setConnection(null);
        $this->assertEquals(Db::error(), false, "Check error for not connected");

        DbMock::setConnection($conn);

        $conn->errno = 0;
        $conn->error = null;
        $this->assertEquals(Db::error(), false, "Check error for empty error");

        $conn->errno = 1;
        $conn->error = 'Test Error';
        $this->assertEquals(Db::error(), $conn->errno . ": " . $conn->error, "Check error result");
    }

    public function testLastId() {
        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        $conn->insert_id = 1;
        
        DbMock::setConnection($conn);
        
        $this->assertEquals(Db::lastId(), $conn->insert_id, "Check affected rows");
    }

    public function testLastIdNotConnected() {
        DbMock::setConnection(null);
        $this->assertEquals(Db::lastId(), false, "Check affected rows without connection");
    }
    
    public function testAffectedRows() {
        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        $conn->affected_rows = 1;

        $this->assertEquals(false, Db::affectedRows(), "Check affected rows");

        DbMock::setConnection($conn);
        $this->assertEquals($conn->affected_rows, Db::affectedRows(), "Check affected rows");
    }

    public function testQuery() {
        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        DbMock::setConnection($conn);

        $queryResult = '1';
        $conn
            ->expects($this->exactly(1))
            ->method('query')
            ->willReturn($queryResult);

        $sql = "SELECT 1";
        $result = Db::query($sql);
        $this->assertEquals($result, $queryResult, "Query result check");
    }

    public function testQueryNotConnected() {
        DbMock::setConnection(null);
        DbConnectionMock::$hasConnectError = true;
        $prevEnv = _cfg('env');
        _cfg('env', 'qatest');
        $this->assertEquals(false, Db::query("SELECT 1"), "Query not connected result check");
        _cfg('env', $prevEnv);

        $prevEnv = _cfg('env');
        _cfg('env', 'dev');
        $this->assertEquals(false, Db::query("SELECT 1"), "Query not connected result dev check");
        _cfg('env', $prevEnv);
    }

    public function testQueryWithoutResult() {
        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        $conn->errno = 1;
        $conn->error = 'Test query error';

        DbMock::setConnection($conn);

        $queryResult = null;
        $conn
            ->expects($this->exactly(1))
            ->method('query')
            ->willReturn($queryResult);
        
        $sql = "SELECT this is fail SQL";
        $prevEnv = _cfg('env');
        _cfg('env', 'qatest');
        $result = Db::query($sql);
        _cfg('env', $prevEnv);
        $this->assertEquals($result, $queryResult, "Query result check");
    }

    public function testFetchRow() {
        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();

        DbMock::setConnection($conn);

        $queryResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryResult->num_rows = 1;

        $fetchResult = new \stdClass();
        $fetchResult->id = 1;

        $queryResult->fetch_object = function() use ($fetchResult) {
            return $fetchResult;
        };
        
        $conn
            ->expects($this->exactly(1))
            ->method('query')
            ->willReturn($queryResult);

        $queryResult
            ->expects($this->exactly(1))
            ->method('fetch_object')
            ->willReturn($fetchResult);

        $queryResult
            ->expects($this->exactly(1))
            ->method('free')
            ->willReturn(true);
        
        $sql = "SELECT * FROM users WHERE id=1";
        $result = Db::fetchRow($sql);
        $this->assertEquals($result, $fetchResult, "Query fetchRow check");
    }

    public function testFetchRowEmptyResult() {
        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        
        DbMock::setConnection($conn);
        
        $queryResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryResult->num_rows = 0;
        
        $conn
            ->expects($this->exactly(1))
            ->method('query')
            ->willReturn($queryResult);

        $queryResult
            ->expects($this->exactly(1))
            ->method('fetch_object')
            ->willReturn(null);

        $queryResult
            ->expects($this->exactly(1))
            ->method('free')
            ->willReturn(null);

        $sql = "SELECT * FROM users WHERE id=null";
        $result = Db::fetchRow($sql);
        $this->assertEquals($result, null, "Query fetchRow with no results check");
    }

    public function testFetchRows() {
        $conn = $this
            ->getMockBuilder(DbConnectionMock::class)
            ->onlyMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();

        DbMock::setConnection($conn);

        $queryResult = $this
            ->getMockBuilder('stdClass')
            ->addMethods(['fetch_object', 'free'])
            ->getMock();

        $queryResult
            ->expects($this->any())
            ->method('fetch_object')
            ->willReturn(null);

        $conn
            ->expects($this->exactly(1))
            ->method('query')
            ->willReturn($queryResult);

        $queryResult
            ->expects($this->exactly(1))
            ->method('free');

        Db::fetchRows("SELECT * FROM users WHERE id >= 1");
    }

    public function testFetchRowsEmptyResult() {
        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->onlyMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        
        DbMock::setConnection($conn);
        
        $queryResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryResult->num_rows = 0;
        
        $conn
            ->expects($this->exactly(1))
            ->method('query')
            ->willReturn($queryResult);
        
        $queryResult
            ->expects($this->exactly(1))
            ->method('fetch_object');

        $queryResult
            ->expects($this->exactly(1))
            ->method('free');
        
        $sql = "SELECT * FROM users WHERE id = null";
        $result = Db::fetchRows($sql);
        $this->assertEquals($result, false, "Query fetchRows without results check");
    }
}
