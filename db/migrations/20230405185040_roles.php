<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Roles extends AbstractMigration
{
    private $table;
    private $data;
    public function init(): void
    {
        $this->table = 'd_roles';
        $this->data  = [
            [
                'name'         => 'Администраторы',
                'description'  => 'Административная роль, имеет доступ ко всему',
                'date_created' => date('Y-m-d H:i:s'),
                'date_updated' => null,
            ],
            [
                'name'         => 'Система',
                'description'  => 'Роль для системных пользователей',
                'date_created' => date('Y-m-d H:i:s'),
                'date_updated' => null,
            ],
            [
                'name'         => 'Доступ в панель администратора',
                'description'  => 'Данная роль разрешает входить в панель администратора',
                'date_created' => date('Y-m-d H:i:s'),
                'date_updated' => null,
            ],
            [
                'name'         => 'Менеджеры',
                'description'  => 'Административная роль с максимально урезанными правами',
                'date_created' => date('Y-m-d H:i:s'),
                'date_updated' => null,
            ],
            [
                'name'         => 'Пользователи',
                'description'  => 'Базовая роль для простых зарегистрированных пользователей',
                'date_created' => date('Y-m-d H:i:s'),
                'date_updated' => null,
            ],
        ];
    }

    public function change(): void
    {
        $table = $this->table($this->table);
        $table->addColumn('name', 'text', ['default' => null])
              ->addColumn('description', 'text', ['default' => null])
              ->addColumn('date_created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('date_updated', 'datetime', ['null' => true, 'default' => null, 'update' => 'CURRENT_TIMESTAMP'])
              ->create();
        $table->insert($this->data);
        $table->save();
    }
}
