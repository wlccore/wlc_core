<?php
namespace eGamings\WLC\Provider;

interface IDb {
    public static function fetchRow(string $query);
    public static function fetchRows(string $query);
    public static function query(string $query);
    public static function escape($variable);
    public static function affectedRows();
}