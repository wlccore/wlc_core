<?php

use Phinx\Migration\AbstractMigration;

class NewIndexForUsersLogin extends AbstractMigration
{
    public function up() {
        $this->execute('ALTER TABLE `users` ADD INDEX(`login`);');
    }

    public function down() {
        $this->execute('ALTER TABLE `users` DROP INDEX(`login`);');
    }
}
