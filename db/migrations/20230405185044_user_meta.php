<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UserMeta extends AbstractMigration
{
    public function init(): void
    {
        $this->table = 'd_user_meta';
    }

    public function change(): void
    {
        $table = $this->table($this->table);
        $table->addColumn('user_id', 'integer')
              ->addColumn('name', 'string', ['limit' => 255, 'default' => null])
              ->addColumn('value', 'text', ['default' => null])
              ->addIndex(['user_id'])
              ->create();
    }
}
