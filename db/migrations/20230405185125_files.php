<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Files extends AbstractMigration
{
    private $table;
    private $data;

    public function init()
    {
        $this->table = 'd_files';
        $this->data  = [
            [
                'name' => 'system.png',
                'size' => 1024,
                'path' => '/uploads/users/system.png',
            ],
        ];
    }

    public function change(): void
    {
        $table = $this->table($this->table);
        $table->addColumn('name', 'string', ['limit' => 255, 'default' => null])
              ->addColumn('size', 'integer', ['signed' => false, 'null' => true, 'default' => null])
              ->addColumn('path', 'text', ['default' => null])
              ->create();
        $table->insert($this->data);
        $table->save();
    }
}
