<?php

    /**
     * Версия сервиса
     */
    const VERSION = '1.0.0';

    /**
     * Путь до корня проекта
     */
    define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);

    /**
     * Путь до папки загрузок
     */
    const UPLOADS_PATH = ROOT_PATH . '/uploads';

    /**
     * Путь до директории с логами
     */
    define('LOG_PATH', $_SERVER['DOCUMENT_ROOT'] . '/core/log');


    /**
     * БД
     */
    const DB_HOST     = 'localhost';
    const DB_USER     = 'rsgrinko_delta';
    const DB_PASSWORD = '2670135';
    const DB_NAME     = 'rsgrinko_delta';

    /**
     * Путь до шаблонов
     */
    const PATH_TO_TEMPLATES = ROOT_PATH . '/templates';

    /**
     * Токен телеграм бота
     */
    const TELEGRAM_BOT_TOKEN = '5232660453:AAGfMWu6EcRfBGSSURJsEEvGPmAqhCyzYHU';

    /**
     * ID чата канала уведомлений в телеграм
     */
    const TELEGRAM_NOTIFICATION_CHANNEL = '-1001714289174';

    /**
     * Время, в течении которого считаем пользователя онлайн, сек.
     */
    const USER_ONLINE_TIME = 60 * 5;

    /**
     * Использование кэша
     */
    const USE_CACHE = false;

    /**
     * Папка кэша
     */
    const CACHE_DIR = ROOT_PATH . '/core/cache';

    /**
     * Время жизни кэша
     */
    const CACHE_TTL = 3600;

    /**
     * Код значения Да
     */
    const CODE_VALUE_Y = 'Y';

    /**
     * Флаг использования логирования
     */
    const USE_LOG = true;

