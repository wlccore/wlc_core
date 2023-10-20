<?php

use Phinx\Migration\AbstractMigration;

class CreateEmailQueueTables extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $emailQueueTable = $this->table('email_queue', [
            'signed' => false,
            'engine' => 'InnoDB',
            'encoding' => 'utf8'
        ]);

        $emailQueueTable->addColumn('email', 'string', ['null' => false, 'limit' => 255])
            ->addColumn('subject', 'text', ['null' => false])
            ->addColumn('message', 'text', ['null' => false])
            ->addColumn('smtp_id', 'integer', ['null' => false, 'limit' => 11, 'signed' => false, 'default' => 0])
            ->addColumn('add_date', 'datetime', ['null' => false])
            ->create();

        $emailQueueSmtpTable = $this->table('email_queue_smtp', [
            'signed' => false,
            'engine' => 'InnoDB',
            'encoding' => 'utf8'
        ]);

        $emailQueueSmtpTable->addColumn('host', 'string', ['null' => false, 'limit' => 100])
            ->addColumn('username', 'string', ['null' => false, 'limit' => 100])
            ->addColumn('password', 'string', ['null' => false, 'limit' => 100])
            ->create();
    }
}

