<?php

    declare(strict_types=1);

    use Delta\Config\Env;

    /**
     * Общие параметры приложения.
     *
     * Значения берутся через Env: сначала переменная окружения, затем одноимённая
     * константа из config.local.php, затем указанное здесь значение по умолчанию.
     */

    $rootPath = Env::get('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__));

    return [
        /**
         * Окружение: local | dev | prod. Определяет режим отладки по умолчанию.
         */
        'env' => Env::get('APP_ENV', 'prod'),

        /**
         * Режим отладки. Если явно не задан — включён везде, кроме prod.
         */
        'debug' => Env::get('APP_DEBUG', Env::get('APP_ENV', 'prod') !== 'prod'),

        /**
         * Версия ядра
         */
        'version' => '1.0.2',

        /**
         * Адрес сайта
         */
        'url' => Env::get('SITE_URL', 'https://' . ($_SERVER['SERVER_NAME'] ?? 'localhost')),

        /**
         * Часовой пояс
         */
        'timezone' => Env::get('APP_TIMEZONE', 'Europe/Moscow'),

        /**
         * Количество элементов на странице
         */
        'pagination_limit' => (int)Env::get('PAGINATION_LIMIT', 10),

        /**
         * Время, в течение которого пользователь считается онлайн, сек.
         */
        'user_online_time' => (int)Env::get('USER_ONLINE_TIME', 300),

        /**
         * Формат даты и времени
         */
        'datetime_format' => 'Y-m-d H:i:s',

        /**
         * Флаг ведения логов
         */
        'use_log' => (bool)Env::get('USE_LOG', true),

        /**
         * Пути проекта
         */
        'paths' => [
            'root'      => $rootPath,
            'core'      => $rootPath . '/core',
            'uploads'   => $rootPath . '/uploads',
            'templates' => $rootPath . '/templates',
            'log'       => $rootPath . '/core/log',
        ],
    ];
