<?php

    declare(strict_types=1);

    use Delta\Config\Env;

    /**
     * Параметры подключения к базе данных.
     *
     * Реквизиты задаются в config.local.php (вне git) либо переменными окружения.
     * Пустые значения по умолчанию — намеренно: при отсутствии реквизитов приложение
     * должно падать сразу и с понятным текстом, а не подключаться куда-то ещё.
     */
    return [
        'driver'       => Env::get('DB_DRIVER', 'mysql'),
        'host'         => Env::get('DB_HOST', 'localhost'),
        'name'         => Env::get('DB_NAME', ''),
        'user'         => Env::get('DB_USER', ''),
        'password'     => Env::get('DB_PASSWORD', ''),
        'port'         => (int)Env::get('DB_PORT', 3306),
        'charset'      => Env::get('DB_CHARSET', 'utf8'),
        'table_prefix' => Env::get('DB_TABLE_PREFIX', 'd_'),
    ];
