<?php

    declare(strict_types=1);

    /**
     * Загрузчик тестового окружения.
     *
     * Реквизиты доступа к базе берутся из config.local.php — в репозитории их нет и быть не должно.
     * Переопределяется только префикс таблиц: тесты работают в той же базе, но в собственных
     * таблицах `test_*`, поэтому рабочие данные не затрагиваются. Префикс обязан быть определён
     * до загрузки классов ядра, так как имена таблиц вычисляются в константах классов при
     * первой загрузке файла.
     */

    $root = dirname(__DIR__);

    $_SERVER['DOCUMENT_ROOT'] = $root;
    $_SERVER['SERVER_NAME']   = 'localhost';
    $_SERVER['REQUEST_URI']   = '/';
    $_SERVER['REMOTE_ADDR']   = '127.0.0.1';

    if (file_exists($root . '/config.local.php')) {
        require_once $root . '/config.local.php';
    }

    define('DB_TABLE_PREFIX', 'test_');

    /**
     * Заглушки на случай отсутствия config.local.php (например, в CI): без них core/config.php
     * сработает как fail-fast и завершит процесс, не дав отработать даже юнит-тестам.
     * Подключиться с такими реквизитами нельзя, поэтому интеграционный набор просто пропустится.
     */
    if (!defined('DB_USER')) {
        define('DB_USER', 'нет-реквизитов');
    }
    if (!defined('DB_NAME')) {
        define('DB_NAME', 'нет-реквизитов');
    }

    if (!defined('APP_ENV')) {
        define('APP_ENV', 'testing');
    }
    define('APP_DEBUG', true);
    define('USE_CACHE', false);
    define('USE_LOG', false);
    define('USE_CAPTCHA', false);
    define('USE_DDOS_PROTECTION', false);

    require_once $root . '/vendor/autoload.php';

    Delta\Config\Config::boot($root . '/config');

    require_once $root . '/core/config.php';

    Core\Helpers\Cache::init(CACHE_DIR, false);

    if (session_status() === PHP_SESSION_NONE) {
        $_SESSION = [];
    }
