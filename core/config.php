<?php
    /**
     * Путь до корня проекта
     */
    define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);

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

