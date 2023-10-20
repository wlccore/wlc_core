<?php
namespace eGamings\WLC\Provider\Repository;

use eGamings\WLC\Domain\Captcha\Record;

interface ICaptcha {
    public function existsIpRecord(string $ip, ?Record &$record = null): bool;
    public function createRecord(string $ip): bool;
    public function updateRecord(Record $record): bool;
    public function deleteRecord(string $ip): bool;
}