<?php

use Phinx\Migration\AbstractMigration;

class AddedApiLogs2 extends AbstractMigration
{
    public function up() {
        $this->execute("
              CREATE TABLE IF NOT EXISTS `api_logs2` (
                `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `TID` varchar(64) NOT NULL,
                `UID` bigint(20) unsigned DEFAULT NULL,
                `ReqDate` date DEFAULT NULL,
                `Request` varchar(4000) NOT NULL,
                `Params` mediumtext NOT NULL,
                `Response` mediumtext,
                `Date` datetime(3) NOT NULL,
                `CallTime` float DEFAULT NULL,
                PRIMARY KEY (`ID`),
                KEY `key_uid` (`UID`),
                KEY `key_tid` (`TID`),
                KEY `key_date` (`ReqDate`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            /*!50500 PARTITION BY RANGE  COLUMNS(`ID`)
            (PARTITION p1 VALUES LESS THAN (350000) ENGINE = InnoDB,
             PARTITION p2 VALUES LESS THAN (700000) ENGINE = InnoDB,
             PARTITION p3 VALUES LESS THAN (1050000) ENGINE = InnoDB,
             PARTITION p4 VALUES LESS THAN (1400000) ENGINE = InnoDB,
             PARTITION p5 VALUES LESS THAN (1750000) ENGINE = InnoDB,
             PARTITION p6 VALUES LESS THAN (2100000) ENGINE = InnoDB,
             PARTITION p7 VALUES LESS THAN (2450000) ENGINE = InnoDB,
             PARTITION p8 VALUES LESS THAN (2800000) ENGINE = InnoDB,
             PARTITION p9 VALUES LESS THAN (3150000) ENGINE = InnoDB,
             PARTITION p10 VALUES LESS THAN (3500000) ENGINE = InnoDB) */;
        ");
    }

    public function down() {
        $this->execute('DROP TABLE `api_logs2`');
    }
}
