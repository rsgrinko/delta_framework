<?php

    declare(strict_types=1);

    use Delta\Config\Env;

    /**
     * Параметры кэширования
     */

    $rootPath = Env::get('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__));

    return [
        'enabled' => (bool)Env::get('USE_CACHE', false),
        'dir'     => Env::get('CACHE_DIR', $rootPath . '/core/cache'),
        'ttl'     => (int)Env::get('CACHE_TTL', 3600),
    ];
