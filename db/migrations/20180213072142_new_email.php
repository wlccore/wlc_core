<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class NewEmail extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('users');
        $table->addColumn('new_email', MysqlAdapter::PHINX_TYPE_STRING, ['limit' => 150, 'null' => false, 'after' => 'email'])->update();
    }
}
