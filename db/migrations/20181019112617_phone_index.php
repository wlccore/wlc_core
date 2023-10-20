<?php

use Phinx\Migration\AbstractMigration;

class PhoneIndex extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('users');
        $table->addIndex(['phone1','phone2'], ['name' => 'phone'])
            ->update();
    }
}
