<?php

use Phinx\Migration\AbstractMigration;

class PaymentRedirects extends AbstractMigration
{
    public function up() {
        $this->execute("
            CREATE TABLE IF NOT EXISTS `redirects` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `user_id` int(10) unsigned DEFAULT 0,
              `domain` varchar(100) NOT NULL,
              `add_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY `id` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function down() {
        $this->execute('DROP TABLE `redirects`');
    }
}
