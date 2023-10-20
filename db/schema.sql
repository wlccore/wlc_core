-- MySQL dump 10.13  Distrib 5.6.27, for debian-linux-gnu (x86_64)
--
-- Host: devxa.lan    Database: wlccoresdev
-- ------------------------------------------------------
-- Server version	5.6.26-74.0-56-log
--
-- command
-- mysqldump -h devxa.lan -u wlcadamasdev -p --no-data --triggers --routines --events --set-gtid-purged=OFF --skip-add-drop-table wlcadamasdev > schema.sql
--

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `email_queue_smtp`
--
CREATE TABLE `email_queue_smtp` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `host` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `email_queue`
--
CREATE TABLE `email_queue` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `subject` text NOT NULL,
  `message` text NOT NULL,
  `smtp_id` int(11) unsigned NOT NULL,
  `add_date` datetime NOT NULL,
  `status` enum('queue','sent','failed') DEFAULT 'queue' NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `affiliate_hits`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `affiliate_hits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `affiliate_id` varchar(256) NOT NULL,
  `affiliate_system` set('globo-tech','quintessence','faff') NOT NULL,
  `ip` varchar(15) NOT NULL COMMENT 'IP v4',
  `add_date` datetime NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `hit_date` (`add_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `api_requests`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_requests` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `UID` int(10) unsigned NOT NULL,
  `Url` TEXT NOT NULL,
  `Date` datetime NOT NULL,
  `Response` mediumtext,
  `Call_time` float NOT NULL DEFAULT '-1',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `currencies`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `currencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(10) NOT NULL,
  `country` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redirects`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `redirects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT 0,
  `domain` varchar(100) NOT NULL,
  `add_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `social`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `social` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `social` varchar(50) NOT NULL,
  `social_uid` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `social_connect`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `social_connect` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(250) NOT NULL,
  `code` varchar(50) NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `social` varchar(3) NOT NULL,
  `social_uid` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `social_requests`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `social_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url` text NOT NULL,
  `post` text,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `response` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_password` char(8) NOT NULL,
  `password` varchar(99) NOT NULL,
  `first_name` varchar(99) NOT NULL,
  `last_name` varchar(99) NOT NULL,
  `login` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(150) NOT NULL,
  `new_email` varchar(150) NOT NULL,
  `phone1` varchar(10) DEFAULT NULL,
  `phone2` varchar(20) DEFAULT NULL,
  `country` varchar(3) NOT NULL DEFAULT '0',
  `currency` varchar(3) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT -1,
  `reg_ip` varchar(39) DEFAULT NULL,
  `reg_time` datetime DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `phone_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_verification_code` varchar(50) DEFAULT NULL,
  `email_verified_datetime` datetime DEFAULT NULL,
  `additional_fields` text COMMENT 'json',
  UNIQUE KEY `id` (`id`),
  KEY `IDUser` (`id`),
  KEY `reg_ip` (`reg_ip`),
  KEY `reg_time` (`reg_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_data`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_data` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_favorites`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_favorites` (
  `user_id` int(10) unsigned NOT NULL,
  `game_id` int(10) unsigned NOT NULL,
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_temp`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_temp` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_storage`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_storage` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `data_key` varchar(50) NOT NULL,
  `data_value` text,
  `cdate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `udate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_data_key` (`user_id`, `data_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_logs`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'wlcadamasdev'
--
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2015-10-29 15:48:56
