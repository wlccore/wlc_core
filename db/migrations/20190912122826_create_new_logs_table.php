<?php
use Phinx\Migration\AbstractMigration;

class CreateNewLogsTable extends AbstractMigration
{
    public function up() {
        $this->execute("
            CREATE TABLE IF NOT EXISTS  `api_logs` (
              `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
              `TID` varchar(64) NOT NULL,
              `UID` bigint(20) unsigned DEFAULT NULL,
              `ReqDate` date DEFAULT NULL,
              `Request` varchar(250) NOT NULL,
              `Params` mediumtext NOT NULL,
              `Response` mediumtext,
              `Date` datetime(3) NOT NULL,
              `CallTime` float DEFAULT NULL,
              PRIMARY KEY (`ID`),
              KEY `key_uid` (`UID`),
              KEY `key_tid` (`TID`),
              KEY `key_request` (`Request`),
              KEY `key_date` (`ReqDate`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function down() {
        $this->execute('DROP TABLE `api_logs`');
    }
}

