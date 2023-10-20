<?php

use Phinx\Migration\AbstractMigration;

class UsersDevices extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('users_devices');
        $table->addColumn('user_id', 'integer')
            ->addColumn('fingerprint_hash', 'string', ['limit' => 40])
            ->addColumn('user_agent', 'string', ['limit' => 150])
            ->addColumn('is_trusted', 'boolean', ['default' => false])
            ->addColumn('updated', 'datetime', ['null' => true])
            ->addIndex(['user_id']) // All devices
            ->addIndex(['user_id', 'is_trusted']) // Only allowed devices
            ->addIndex(['user_id', 'fingerprint_hash', 'user_agent']) // Has the user allowed devices?
            ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])

            ->create();
    }
}
