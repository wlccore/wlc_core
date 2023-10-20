<?php

use Phinx\Migration\AbstractMigration;

class IpChecks extends AbstractMigration
{
    public function change()
    {
        $this->table('ip_checks')
            ->addColumn('ip', 'string', ['limit' => 40])
            ->addColumn('first_date', 'datetime')
            ->addColumn('last_date', 'datetime')
            ->addColumn('count_last_hour', 'integer')
            ->addColumn('count_last_day', 'integer')
            ->addIndex(['ip'], ['unique' => true])

            ->create();
    }
}
