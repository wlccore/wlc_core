--
-- 12.07.2021
--
CREATE TABLE IF NOT EXISTS `sms_queue` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `phone` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `add_date` datetime NOT NULL,
  `status` enum('queue','sent','failed') DEFAULT 'queue' NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
--
-- 04.12.2020
--
ALTER TABLE `users` CHANGE COLUMN `currency` `currency` VARCHAR(10) NOT NULL DEFAULT "";
ALTER TABLE `users_temp` CHANGE COLUMN `currency` `currency` VARCHAR(10) NOT NULL DEFAULT "";

--
-- 10.08.2020
--
CREATE TABLE IF NOT EXISTS `sms_delivery_status` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(32) NOT NULL,
  `msgid` varchar(64) NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT '',
  `updated` datetime NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `msgid` (`msgid`),
  KEY `provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- 16.11.2018
--
CREATE TABLE IF NOT EXISTS `email_queue_smtp` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `host` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `email_queue` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `subject` text NOT NULL,
  `message` text NOT NULL,
  `smtp_id` INT(11) INSIGNED NOT NULL,
  `add_date` datetime NOT NULL,
  `status` enum('queue','sent','failed') DEFAULT 'queue' NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- 07.05.2018
--
ALTER TABLE `users` ADD `new_email` varchar(150) NOT NULL AFTER `email`;

--
-- 23.11.2017
--
ALTER TABLE `users` ADD COLUMN `phone_verified` tinyint(1) default '0' not null AFTER `email_verified`;

--
-- 15.08.2017
--
CREATE TABLE IF NOT EXISTS `redirects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT 0,
  `domain` varchar(100) NOT NULL,
  `add_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- 07.03.2017
--
ALTER TABLE `users` CHANGE `status` `status` tinyint(1) NOT NULL DEFAULT -1;

--
-- 24.01.2017
--
ALTER TABLE `affiliate_hits` CHANGE `affiliate_system` `affiliate_system` set('globo-tech','quintessence', 'faff') NOT NULL;

--
-- 17.01.2017
--

ALTER TABLE `users_data`
  ADD `sms_notification` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `users_data`
  ADD `email_notification` tinyint(1) NOT NULL DEFAULT 0;
  
--
-- 15.11.2016
--
ALTER TABLE `affiliate_hits` CHANGE `affiliate_system` `affiliate_system` set('globo-tech','quintessence') NOT NULL;


--
-- 10.10.2016
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- 11.07.2016
--

ALTER TABLE `users`
  ADD `login` varchar(50) NOT NULL DEFAULT '' AFTER `last_name`;
ALTER TABLE `users_temp`
  ADD `login` varchar(50) NOT NULL DEFAULT '' AFTER `last_name`;

--
-- 18.03.2016
--

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

--
-- 17.03.2016
--

ALTER TABLE `users` ADD `additional_fields` text COMMENT 'json';

--
-- 03.02.2016
--

ALTER TABLE `users`
  ADD `email_verified` tinyint(1) NOT NULL DEFAULT 0 AFTER `reg_time`,
  ADD `email_verification_code` varchar(50) DEFAULT NULL AFTER `email_verified`,
  ADD `email_verified_datetime` datetime DEFAULT NULL AFTER `email_verification_code`;

--
-- 29.09.2015
--

ALTER TABLE `users` ADD `status` tinyint(1) NOT NULL DEFAULT 1 AFTER `currency`;

--
-- 15.05.2015
--

ALTER TABLE `users` ADD `reg_ip` VARCHAR( 39 ) NULL DEFAULT NULL AFTER `currency`; 
ALTER TABLE `users` ADD INDEX ( `reg_ip` ) ;
ALTER TABLE `users` ADD INDEX ( `reg_time` ) ;

-- 07.05.2015
ALTER TABLE `api_requests` CHANGE `Url` `Url` TEXT NOT NULL;

-- 28.01.2015
-- Changing currency from numeric to varchar
ALTER TABLE `users_temp` CHANGE `currency` `currency` VARCHAR(3) NOT NULL;
ALTER TABLE `users` CHANGE `currency` `currency` VARCHAR(3) NOT NULL;
