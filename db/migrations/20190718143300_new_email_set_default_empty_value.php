<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class NewEmailSetDefaultEmptyValue extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('users');
        $table->changeColumn('new_email', MysqlAdapter::PHINX_TYPE_STRING, ['default' => ''])->update();
    }
}
