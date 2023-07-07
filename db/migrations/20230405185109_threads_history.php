<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ThreadsHistory extends AbstractMigration
{
    public function init()
    {
        $this->table = 'd_threads_history';
    }

    public function change(): void
    {
        $table = $this->table($this->table);
        $table->addColumn('task_id', 'integer')
              ->addColumn('priority', 'integer', ['default' => 5])
              ->addColumn('execution_time', 'string', ['limit' => 255, 'default' => null])
              ->addColumn('attempts', 'integer', ['default' => 0])
              ->addColumn('attempts_limit', 'integer', ['default' => 1])
              ->addColumn('date_created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('date_updated', 'datetime', ['null' => true, 'default' => null, 'update' => 'CURRENT_TIMESTAMP'])
              ->addColumn('class', 'string', ['limit' => 255, 'default' => null])
              ->addColumn('method', 'string', ['limit' => 255, 'default' => null])
              ->addColumn('params', 'text', ['default' => null])
              ->addColumn('status', 'string', ['limit' => 255, 'default' => null])
              ->addColumn('response', 'text', ['default' => null])
              ->addIndex(['task_id'])
              ->create();
    }
}
