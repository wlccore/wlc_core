<?php

use Phinx\Migration\AbstractMigration;

class ChangeUsersTempCurrencyFieldSize extends AbstractMigration
{
    public function up()
    {
        $this->execute('ALTER TABLE `users_temp` CHANGE COLUMN `currency` `currency` VARCHAR(10) NOT NULL DEFAULT "";');
    }

    public function down()
    {
        $this->execute('ALTER TABLE `users_temp` CHANGE COLUMN `currency` `currency` VARCHAR(3) NOT NULL;');
    }
}
