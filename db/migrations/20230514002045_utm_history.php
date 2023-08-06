<?php
    declare(strict_types=1);

    use Phinx\Migration\AbstractMigration;

    final class UtmHistory extends AbstractMigration
    {
        private $table;

        public function init()
        {
            $this->table = 'd_utm_history';
        }

        public function change(): void
        {
            $table = $this->table($this->table);
            $table->addColumn('page', 'string', ['limit' => 255, 'default' => null])
                  ->addColumn('referer', 'string', ['limit' => 255, 'default' => null])
                  ->addColumn('user_agent', 'text', ['null' => true, 'default' => null])
                  ->addColumn('utm_source', 'string', ['limit' => 255, 'default' => null])
                  ->addColumn('utm_medium', 'string', ['limit' => 255, 'null' => true, 'default' => null])
                  ->addColumn('utm_campaign', 'string', ['limit' => 255, 'default' => null])
                  ->addColumn('utm_term', 'string', ['limit' => 255, 'null' => true, 'default' => null])
                  ->addColumn('utm_content', 'string', ['limit' => 255, 'default' => null])
                  ->addColumn('date_created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                  ->addIndex(['page'])
                  ->addIndex(['utm_source'])
                  ->addIndex(['date_created'])
                  ->create();
        }
    }
