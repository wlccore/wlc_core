<?php

use Phinx\Migration\AbstractMigration;

class CountriesConfirmationNonResidence extends AbstractMigration
{
    public function up()
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS `countries_confirmation_non_residence` (
              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
              `user_id` int(10) unsigned NOT NULL,
              `add_date` datetime NOT NULL,
              `country_iso3` varchar(4) NOT NULL NULL DEFAULT '',
              `ip` varchar(15) NOT NULL,
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function down()
    {
        $this->execute('DROP TABLE `countries_confirmation_non_residence`');
    }
}
