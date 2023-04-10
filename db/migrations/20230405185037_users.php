<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Users extends AbstractMigration
{

    public function init(): void
    {
        $this->table = 'd_users';
        $this->data  = [
            [
                'active'            => 'Y',
                'login'             => 'admin',
                'password'          => '7c607172d24d7237579acfdeffe373e2',
                'name'              => 'Администратор',
                'email'             => 'rsgrinko@yandex.ru',
                'email_confirmed'   => 'Y',
                'verification_code' => null,
                'image_id'          => 1,
                'token'             => null,
                'last_active'       => time(),
                'date_created'      => date('Y-m-d H:i:s'),
                'date_updated'      => null,

            ],
        ];
    }

    public function change(): void
    {
        $table = $this->table($this->table);
        $table->addColumn('active', 'enum', ['values' => ['Y', 'N'], 'default' => 'Y', 'null' => false])
              ->addColumn('login', 'string', ['limit' => 100, 'default' => null])
              ->addColumn('password', 'string', ['limit' => 100, 'default' => null])
              ->addColumn('name', 'string', ['limit' => 150, 'default' => null])
              ->addColumn('email', 'string', ['limit' => 150, 'default' => null])
              ->addColumn('email_confirmed', 'enum', ['values' => ['Y', 'N'], 'default' => 'N', 'null' => false])
              ->addColumn('verification_code', 'string', ['limit' => 255, 'default' => null])
              ->addColumn('image_id', 'integer', ['null' => true, 'default' => null])
              ->addColumn('token', 'string', ['limit' => 255, 'default' => null])
              ->addColumn('last_active', 'string', ['limit' => 100, 'default' => null])
              ->addColumn('date_created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('date_updated', 'datetime', ['null' => true, 'default' => null])
              ->addIndex(['login'])
              ->addIndex(['token'])
              ->addIndex(['email'])
              ->create();
        $table->insert($this->data);
        $table->save();
    }
}
