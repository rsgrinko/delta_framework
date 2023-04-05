<?php

return
[
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'd_phinxlog',
        'default_environment' => 'dev',
        'prod' => [
            'adapter' => 'mysql',
            'host' => 'localhost',
            'name' => 'rsgrinko_delta',
            'user' => 'rsgrinko_delta',
            'pass' => '2670135',
            'port' => '3306',
            'charset' => 'utf8',
        ],
        'dev' => [
            'adapter' => 'mysql',
            'host' => 'it-stories.ru',
            'name' => 'rsgrinko_test',
            'user' => 'rsgrinko_test',
            'pass' => '2670135',
            'port' => '3306',
            'charset' => 'utf8',
        ]
    ],
    'version_order' => 'creation'
];
