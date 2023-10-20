<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class UserTempRegNotify extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('users_temp');
        $table->addColumn('reg_notify', MysqlAdapter::PHINX_TYPE_INTEGER, [
            'null' => false,
            'default' => 0,
            'after' => 'reg_lang',
        ])->addColumn('reg_notify_time', MysqlAdapter::PHINX_TYPE_DATETIME, [
            'null' => true,
            'default' => null,
            'after' => 'reg_notify',
        ])->update();
    }
}
