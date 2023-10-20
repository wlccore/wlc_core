<?php
namespace eGamings\WLC\Tests;

class DbConnectionMock {
    public $connect_errno = 0;
    public $connect_error = 0;
    public $errno = 0;
    public $error = null;
    public $insert_id = 1;

    public static $hasConnectError = false;

    public function __construct() {
        if (self::$hasConnectError) {
            $this->connect_errno = 1;
            $this->connect_error = 'Connection test error';
        }
    }

    public function real_escape_string(string $value) {
        return $value;
    }

    public function query() {
        return null;
    }

    public function multi_query() {
        return null;
    }

    public function store_result() {
        return null;
    }

    public function more_results() {
        return null;
    }

    public function next_result() {
        return null;
    }
}
