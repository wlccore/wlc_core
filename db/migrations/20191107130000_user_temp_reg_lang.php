<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class UserTempRegLang extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('users_temp');
        $table->addColumn('reg_lang', MysqlAdapter::PHINX_TYPE_STRING, [
            'limit' => 10,
            'null' => false,
            'default' => 'en',
            'after' => 'reg_site',
        ])->update();
    }
}
