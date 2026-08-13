<?php

    declare(strict_types=1);

    /**
     * Конфигурация миграций Phinx.
     *
     * Реквизиты доступа берутся из того же источника, что и у приложения: config.local.php
     * (вне git) либо переменные окружения. Шаблон окружения — config.local.example.php.
     */

    use Delta\Config\Env;

    require_once __DIR__ . '/vendor/autoload.php';

    if (file_exists(__DIR__ . '/config.local.php')) {
        require_once __DIR__ . '/config.local.php';
    }

    $environment = static function (string $prefix): array {
        return [
            'adapter' => 'mysql',
            'host'    => Env::get($prefix . '_HOST', 'localhost'),
            'name'    => Env::get($prefix . '_NAME', ''),
            'user'    => Env::get($prefix . '_USER', ''),
            'pass'    => Env::get($prefix . '_PASS', ''),
            'port'    => (int)Env::get($prefix . '_PORT', 3306),
            'charset' => Env::get($prefix . '_CHARSET', 'utf8'),
        ];
    };

    $dev  = $environment('PHINX_DEV');
    $prod = $environment('PHINX_PROD');

    if ($dev['name'] === '' && $prod['name'] === '') {
        fwrite(
            STDERR,
            'Не заданы реквизиты для миграций. Создайте config.local.php по образцу config.local.example.php.' . PHP_EOL
        );
        die(1);
    }

    return [
        'paths' => [
            'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
            'seeds'      => '%%PHINX_CONFIG_DIR%%/db/seed',
        ],
        'environments' => [
            'default_migration_table' => 'd_phinxlog',
            'default_environment'     => 'dev',
            'dev'                     => $dev,
            'prod'                    => $prod,
        ],
        'version_order' => 'creation',
    ];
