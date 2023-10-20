<?php
use Phinx\Migration\AbstractMigration;

class CreateSMSDeliveryStatusTable extends AbstractMigration
{
    public function up() {
        $this->execute("
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
        ");
    }

    public function down() {
        $this->execute('DROP TABLE `sms_delivery_status`');
    }
}

