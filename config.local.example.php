<?php
    /**
     * Шаблон локальной конфигурации окружения.
     *
     * Скопируйте этот файл в config.local.php в корне проекта и заполните значения.
     * Сам config.local.php в git не хранится и создаётся на каждом окружении отдельно.
     *
     * Файл подключается из core/bootstrap.php ДО core/config.php, поэтому любая константа,
     * определённая здесь, имеет приоритет над значением по умолчанию из core/config.php.
     *
     * Без заполненных DB_* приложение не стартует: core/config.php выбросит понятную ошибку.
     */

    /**
     * Параметры подключения к базе данных (обязательно)
     */
    define('DB_HOST', 'localhost');
    define('DB_USER', '');
    define('DB_PASSWORD', '');
    define('DB_NAME', '');

    /**
     * Токен телеграм-бота (обязателен, если используются уведомления или вход через Telegram)
     */
    define('TELEGRAM_BOT_TOKEN', '');

    /**
     * Ключ шифрования
     */
    define('CRYPTO_KEY', '');

    /**
     * Параметры подключения для миграций Phinx
     */
    define('PHINX_DEV_HOST', 'localhost');
    define('PHINX_DEV_NAME', '');
    define('PHINX_DEV_USER', '');
    define('PHINX_DEV_PASS', '');

    define('PHINX_PROD_HOST', 'localhost');
    define('PHINX_PROD_NAME', '');
    define('PHINX_PROD_USER', '');
    define('PHINX_PROD_PASS', '');
