<?php

use Phinx\Migration\AbstractMigration;

class SettingAutoincrementFromApiLogs extends AbstractMigration
{
    public function up() {
        if ($this->hasTable('api_logs') && $this->hasTable('api_logs2')) {
            $lastId = $this->fetchRow('SELECT GREATEST((SELECT MAX(`ID`) FROM `api_logs`), (SELECT MAX(`ID`) FROM `api_logs2`)) AS last_id')['last_id'] ?? 0;

            if ($lastId) {
                $this->execute(sprintf('ALTER TABLE `api_logs2` AUTO_INCREMENT = %u', $lastId));
            }
        }
    }

    public function down() {}
}
