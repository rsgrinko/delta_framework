<?php

    /**
     * Конфигурация миграций Phinx.
     *
     * Реквизиты доступа к базам берутся из config.local.php (вне git).
     * Шаблон окружения — config.local.example.php.
     */

    if (file_exists(__DIR__ . '/config.local.php') === false) {
        fwrite(STDERR, 'Не найден config.local.php — создайте его по образцу config.local.example.php.' . PHP_EOL);
        die(1);
    }

    require_once __DIR__ . '/config.local.php';

    return
    [
        'paths' => [
            'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
            'seeds' => '%%PHINX_CONFIG_DIR%%/db/seed'
        ],
        'environments' => [
            'default_migration_table' => 'd_phinxlog',
            'default_environment' => 'dev',
            'prod' => [
                'adapter' => 'mysql',
                'host' => PHINX_PROD_HOST,
                'name' => PHINX_PROD_NAME,
                'user' => PHINX_PROD_USER,
                'pass' => PHINX_PROD_PASS,
                'port' => '3306',
                'charset' => 'utf8',
            ],
            'dev' => [
                'adapter' => 'mysql',
                'host' => PHINX_DEV_HOST,
                'name' => PHINX_DEV_NAME,
                'user' => PHINX_DEV_USER,
                'pass' => PHINX_DEV_PASS,
                'port' => '3306',
                'charset' => 'utf8',
            ]
        ],
        'version_order' => 'creation'
    ];
