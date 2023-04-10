<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UserRoles extends AbstractMigration
{
    public function init(): void
    {
        $this->table = 'd_user_roles';
        $this->data = [
            [
                'user_id' => 1,
                'role_id' => 1,

            ],
            [
                'user_id' => 1,
                'role_id' => 3,
            ],
        ];
    }

    public function change(): void
    {
        $table = $this->table($this->table);
        $table->addColumn('user_id', 'integer')
              ->addColumn('role_id', 'integer')
              ->create();
        $table->insert($this->data);
        $table->save();
    }
}
