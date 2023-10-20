<?php

use Phinx\Migration\AbstractMigration;

class ChangeUsersCurrencyFieldSize extends AbstractMigration
{
    public function up()
    {
        $this->execute('ALTER TABLE `users` CHANGE COLUMN `currency` `currency` VARCHAR(10) NOT NULL DEFAULT "";');
    }

    public function down()
    {
        $this->execute('ALTER TABLE `users` CHANGE COLUMN `currency` `currency` VARCHAR(3) NOT NULL;');
    }
}
