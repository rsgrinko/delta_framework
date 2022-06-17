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
 * Путь до директории с логами
 */
define('LOG_PATH', $_SERVER['DOCUMENT_ROOT'] . '/core/log');

/**
 * Префикс таблиц в БД
 */
const TABLE_PREFIX = '';

/**
 * БД
 */
const DB_HOST = 'localhost';
const DB_USER = 'rsgrinko_delta';
const DB_PASSWORD = '2670135';
const DB_NAME = 'rsgrinko_delta';

/**
 * Время, в течении которого считаем пользователя онлайн, сек.
 */
const USER_ONLINE_TIME = 60 * 5;

/**
 * Время жизни кэша
 */
const CACHE_TTL = 3600;

/**
 * Код значения Да
 */
const CODE_VALUE_Y = 'Y';

