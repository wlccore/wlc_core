<?php

use Phinx\Migration\AbstractMigration;

class NewIndexForUsersEmail extends AbstractMigration
{
    public function up() {
        $this->execute('ALTER TABLE `users` ADD INDEX(`email`);');
    }

    public function down() {
        $this->execute('ALTER TABLE `users` DROP INDEX(`email`);');
    }
}
