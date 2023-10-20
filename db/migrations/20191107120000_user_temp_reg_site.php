<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class UserTempRegSite extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('users_temp');
        $table->addColumn('reg_site', MysqlAdapter::PHINX_TYPE_STRING, [
            'limit' => 250,
            'null' => false,
            'default' => '',
            'after' => 'reg_time',
        ])->update();
    }
}
