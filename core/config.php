<?php

    /**
     * Версия сервиса
     */
    define('VERSION', '1.0.0');

    /**
     * Путь до корня проекта
     */
    define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);

    /**
     * Путь до директории с логами
     */
    define('LOG_PATH', $_SERVER['DOCUMENT_ROOT'] . '/core/log');

    /**
     * Префикс таблиц в БД
     */
    define('TABLE_PREFIX', 'd_');

    /**
     * Сервер БД
     */
    define('DB_HOST', 'localhost');

    /**
     * Имя пользователя БД
     */
    define('DB_USER', 'delta_core');

    /**
     * Пароль пользователя БД
     */
    define('DB_PASSWORD', 'dickpick');

    /**
     * Название базы
     */
    define('DB_NAME', 'delta_core');

    /**
     * Время, в течении которого считаем пользователя онлайн, сек.
     */
    define('USER_ONLINE_TIME', 60 * 5);

    /**
     * Время жизни кэша
     */
    define('CACHE_TTL', 3600);

    /**
     * Код значения Да
     */
    define('CODE_VALUE_Y', 'Y');

