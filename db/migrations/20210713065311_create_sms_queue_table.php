<?php

use Phinx\Migration\AbstractMigration;

class CreateSmsQueueTable extends AbstractMigration
{
    public function up()
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `sms_queue` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `phone` varchar(50) NOT NULL,
            `message` text NOT NULL,
            `add_date` datetime NOT NULL,
            `status` enum('queue','sent','failed') DEFAULT 'queue' NOT NULL,
            PRIMARY KEY (`id`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }

    public function down() {
        $this->execute('DROP TABLE `sms_queue`');
    }
}
