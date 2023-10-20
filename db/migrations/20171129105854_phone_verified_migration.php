<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class PhoneVerifiedMigration extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('users');
        $table->addColumn('phone_verified', MysqlAdapter::PHINX_TYPE_INTEGER, ['limit' => MysqlAdapter::INT_TINY, 'null' => false, 'default' => 0, 'after' => 'email_verified'])->update();
    }
}
