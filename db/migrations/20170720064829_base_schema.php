<?php

use Phinx\Migration\AbstractMigration;

/**
 * Base wlc db schema
 * Last used change: 07.03.2017
 */
class BaseSchema extends AbstractMigration
{
    private $wlcTables = [
        'affiliate_hits', 
        'api_requests', 
        'currencies', 
        'social', 
        'social_connect', 
        'social_requests', 
        'users',
        'users_data',
        'users_favorites',
        'users_temp',
        'users_storage',
        'users_logs'
    ];

    public function up()
    {
        $this->createTables();
        $this->createProcedures();
        $this->insertData();
    }

    private function createTables()
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS `affiliate_hits` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `affiliate_id` varchar(256) NOT NULL,
            `affiliate_system` set('globo-tech','quintessence','faff') NOT NULL,
            `ip` varchar(15) NOT NULL COMMENT 'IP v4',
            `add_date` datetime NOT NULL,
            UNIQUE KEY `id` (`id`),
            KEY `hit_date` (`add_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->execute("
            CREATE TABLE IF NOT EXISTS `api_requests` (
            `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `UID` int(10) unsigned NOT NULL,
            `Url` TEXT NOT NULL,
            `Date` datetime NOT NULL,
            `Response` mediumtext,
            `Call_time` float NOT NULL DEFAULT '-1',
            PRIMARY KEY (`ID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            /*!50500 PARTITION BY RANGE COLUMNS(`ID`)
            (PARTITION p1 VALUES LESS THAN (20000000) ENGINE = InnoDB,
             PARTITION p2 VALUES LESS THAN (40000000) ENGINE = InnoDB,
             PARTITION p3 VALUES LESS THAN (60000000) ENGINE = InnoDB,
             PARTITION p4 VALUES LESS THAN (80000000) ENGINE = InnoDB,
             PARTITION p5 VALUES LESS THAN (100000000) ENGINE = InnoDB,
             PARTITION p6 VALUES LESS THAN (120000000) ENGINE = InnoDB,
             PARTITION p7 VALUES LESS THAN (140000000) ENGINE = InnoDB,
             PARTITION p8 VALUES LESS THAN (160000000) ENGINE = InnoDB,
             PARTITION p9 VALUES LESS THAN (180000000) ENGINE = InnoDB,
             PARTITION p10 VALUES LESS THAN (200000000) ENGINE = InnoDB) */;
        ");

        $this->execute("
            CREATE TABLE IF NOT EXISTS `currencies` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(10) NOT NULL,
            `country` varchar(50) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `Name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->execute("
            CREATE TABLE IF NOT EXISTS `social` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `user_id` int(10) unsigned NOT NULL,
            `social` varchar(50) NOT NULL,
            `social_uid` varchar(100) NOT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->execute("
            CREATE TABLE IF NOT EXISTS `social_connect` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `email` varchar(250) NOT NULL,
            `code` varchar(50) NOT NULL,
            `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `social` varchar(3) NOT NULL,
            `social_uid` bigint(20) unsigned NOT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->execute("
            CREATE TABLE IF NOT EXISTS `social_requests` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `url` text NOT NULL,
            `post` text,
            `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `response` text,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        
        $this->execute("
            CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `api_password` char(8) NOT NULL,
            `password` varchar(99) NOT NULL,
            `first_name` varchar(99) NOT NULL,
            `last_name` varchar(99) NOT NULL,
            `login` varchar(50) NOT NULL DEFAULT '',
            `email` varchar(150) NOT NULL,
            `phone1` varchar(10) DEFAULT NULL,
            `phone2` varchar(20) DEFAULT NULL,
            `country` varchar(3) NOT NULL DEFAULT '0',
            `currency` varchar(3) NOT NULL,
            `status` tinyint(1) NOT NULL DEFAULT -1,
            `reg_ip` varchar(39) DEFAULT NULL,
            `reg_time` datetime DEFAULT NULL,
            `email_verified` tinyint(1) NOT NULL DEFAULT 0,
            `email_verification_code` varchar(50) DEFAULT NULL,
            `email_verified_datetime` datetime DEFAULT NULL,
            `additional_fields` text COMMENT 'json',
            UNIQUE KEY `id` (`id`),
            KEY `IDUser` (`id`),
            KEY `reg_ip` (`reg_ip`),
            KEY `reg_time` (`reg_time`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->execute("
            CREATE TABLE IF NOT EXISTS `users_data` (
            `user_id` int(10) unsigned NOT NULL,
            `social_fb` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `social_gp` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `social_ok` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `social_tw` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `social_vk` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `social_ya` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `social_ml` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `sms_notification` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `email_notification` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `sex` enum('m','f','') DEFAULT NULL,
            `birth_day` tinyint(2) unsigned DEFAULT NULL,
            `birth_month` tinyint(2) unsigned DEFAULT NULL,
            `birth_year` smallint(4) unsigned DEFAULT NULL,
            PRIMARY KEY (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->execute("
            CREATE TABLE IF NOT EXISTS `users_favorites` (
            `user_id` int(10) unsigned NOT NULL,
            `game_id` int(10) unsigned NOT NULL,
            KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->execute("
            CREATE TABLE IF NOT EXISTS `users_temp` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `api_password` char(8) NOT NULL,
            `password` varchar(99) NOT NULL,
            `first_name` varchar(99) NOT NULL,
            `last_name` varchar(99) NOT NULL,
            `login` varchar(50) NOT NULL DEFAULT '',
            `email` varchar(150) NOT NULL,
            `phone1` varchar(10) DEFAULT NULL,
            `phone2` varchar(20) DEFAULT NULL,
            `country` varchar(3) NOT NULL DEFAULT '0',
            `currency` varchar(3) NOT NULL,
            `reg_ip` varchar(39) DEFAULT NULL,
            `reg_time` datetime DEFAULT NULL,
            `code` varchar(250) DEFAULT NULL,
            `additional_fields` text COMMENT 'json',
            UNIQUE KEY `id` (`id`),
            KEY `IDUser` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->execute("
            CREATE TABLE IF NOT EXISTS `users_storage` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `user_id` int(10) unsigned NOT NULL,
            `data_key` varchar(50) NOT NULL,
            `data_value` text,
            `cdate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `udate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_data_key` (`user_id`, `data_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $this->execute("
            CREATE TABLE IF NOT EXISTS `users_logs` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `user_id` int(10) unsigned DEFAULT 0,
            `ip` varchar(15) NOT NULL,
            `user_agent` text  NOT NULL,
            `ssid` varchar(50),
            `type` varchar(50) NOT NULL,
            `data` text,
            `add_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `id` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            /*!50500 PARTITION BY RANGE COLUMNS(`id`)
            (PARTITION p1 VALUES LESS THAN (5000000) ENGINE = InnoDB,
             PARTITION p2 VALUES LESS THAN (10000000) ENGINE = InnoDB,
             PARTITION p3 VALUES LESS THAN (15000000) ENGINE = InnoDB,
             PARTITION p4 VALUES LESS THAN (20000000) ENGINE = InnoDB,
             PARTITION p5 VALUES LESS THAN (25000000) ENGINE = InnoDB,
             PARTITION p6 VALUES LESS THAN (30000000) ENGINE = InnoDB,
             PARTITION p7 VALUES LESS THAN (35000000) ENGINE = InnoDB,
             PARTITION p8 VALUES LESS THAN (40000000) ENGINE = InnoDB,
             PARTITION p9 VALUES LESS THAN (45000000) ENGINE = InnoDB,
             PARTITION p10 VALUES LESS THAN (50000000) ENGINE = InnoDB) */;
        ");
    }

    private function createProcedures()
    {
         $this->execute("
            CREATE PROCEDURE `WlcCleanupOldData`()
                MODIFIES SQL DATA
                SQL SECURITY INVOKER
            BEGIN
                DECLARE v_delete_limit INT DEFAULT 1000;
                DECLARE v_row_count INT DEFAULT 0;
                
                SELECT 'Cleaning ApiRequests...';
                REPEAT
                    
                    START TRANSACTION;
                    DELETE FROM api_requests
                        WHERE `Date` < DATE_SUB( NOW(), INTERVAL 1 MONTH )
                        ORDER BY `ID`   
                        LIMIT v_delete_limit;
                    SET v_row_count = ROW_COUNT();
                    COMMIT;
                    
                    SELECT CONCAT( '... deleted ', v_row_count );
                UNTIL v_row_count < v_delete_limit
                END REPEAT;
            END
        ");
    }

    private function insertData()
    {
        $this->execute("
            INSERT INTO `currencies` (`id`, `name`, `country`) VALUES
            (1, 'EUR', 'European Union'),
            (2, 'RUB', 'Russia'),
            (3, 'USD', 'USA'),
            (4, 'GBP', 'England');
        ");
    }

    public function down()
    {
        $this->execute('DROP TABLE `'.implode('`, `', $this->wlcTables).'`');
        $this->execute('DROP PROCEDURE `WlcCleanupOldData`');
    }
}
