<?php

use Phinx\Migration\AbstractMigration;

class NewIndexForApiRequests extends AbstractMigration
{
    public function up() {
        $this->execute('ALTER TABLE `api_requests` ADD INDEX(`Date`);');
    }

    public function down() {
        $this->execute('ALTER TABLE `api_requests` DROP INDEX(`Date`);');
    }
}
