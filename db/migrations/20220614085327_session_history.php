<?php

use Phinx\Migration\AbstractMigration;

class SessionHistory extends AbstractMigration
{
    public function up()
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS `session_history` (
              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
              `user_id` int(10) unsigned NOT NULL,
              `finger_print` varchar(40) NOT NULL NULL DEFAULT '',
              `session_id` varchar(32) NOT NULL DEFAULT '',
              `add_date` datetime NOT NULL,
              `updated` datetime NOT NULL,
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`),
              KEY `finger_print` (`finger_print`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function down()
    {
        $this->execute('DROP TABLE `session_history`');
    }
}
