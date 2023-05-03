<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MailHistory extends AbstractMigration
{
    public function init()
    {
        $this->table = 'd_mail_history';
    }

    public function change(): void
    {
        $table = $this->table($this->table);
        $table->addColumn('to_mail', 'string', ['limit' => 255, 'default' => null])
              ->addColumn('to_name', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('from_mail', 'string', ['limit' => 255, 'default' => null])
              ->addColumn('from_name', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('subject', 'string', ['limit' => 255, 'default' => null])
              ->addColumn('template', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('template_vars', 'text', ['null' => true, 'default' => null])
              ->addColumn('body', 'text', ['null' => true, 'default' => null])
              ->addColumn('send', 'enum', ['values' => ['Y', 'N'], 'default' => 'N', 'null' => false])
              ->addColumn('date_created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->create();
    }
}
