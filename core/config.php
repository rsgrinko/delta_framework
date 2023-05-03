<?php
    /**
     * Copyright (c) 2022 Roman Grinko <rsgrinko@gmail.com>
     * Permission is hereby granted, free of charge, to any person obtaining
     * a copy of this software and associated documentation files (the
     * "Software"), to deal in the Software without restriction, including
     * without limitation the rights to use, copy, modify, merge, publish,
     * distribute, sublicense, and/or sell copies of the Software, and to
     * permit persons to whom the Software is furnished to do so, subject to
     * the following conditions:
     * The above copyright notice and this permission notice shall be included
     * in all copies or substantial portions of the Software.
     * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
     * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
     * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
     * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
     * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
     * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
     * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
     */

    /**
     * Версия сервиса
     */
    const CORE_VERSION = '1.0.0_dev';

    /**
     * Флаг отладки
     */
    define('DEBUG', true);

    /**
     * Путь до корня проекта
     */
    define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);

    /**
     * Путь до папки ядра
     */
    define('CORE_PATH', ROOT_PATH . '/core');

    /**
     * Адрес проекта
     */
    define('SITE_URL', 'https://' . $_SERVER['SERVER_NAME']);

    /**
     * Путь до папки ядра
     */
    define('SITE_URL_CORE', SITE_URL . '/core');

    /**
     * Путь до папки загрузок
     */
    define('UPLOADS_PATH', ROOT_PATH . '/uploads');

    /**
     * Путь до папки почтовых шаблонов
     */
    define('MAIL_TEMPLATES_PATH', CORE_PATH . '/mail_templates');

    /**
     * Почтовый шаблон по умолчанию
     */
    define('MAIL_TEMPLATE_DEFAULT', 'default');

    /**
     * Путь до директории с логами
     */
    define('LOG_PATH', $_SERVER['DOCUMENT_ROOT'] . '/core/log');

    /**
     * Количество элементов на странице
     */
    define('PAGINATION_LIMIT', 10);

    /**
     * Параметры SQL базы
     */
    define('DB_HOST', 'localhost'); //'localhost';
    define('DB_USER', 'rsgrinko_delta');
    define('DB_PASSWORD', '2670135');
    define('DB_NAME', 'rsgrinko_delta');
    define('DB_TABLE_PREFIX', 'd_');

    /**
     * Путь до шаблонов
     */
    define('PATH_TO_TEMPLATES', ROOT_PATH . '/templates');

    /**
     * Токен телеграм бота
     */
    define('TELEGRAM_BOT_TOKEN', '5357759725:AAEPGfLRaye1ZOPMBGOrBVCMhz4kE_aecME'); //delta

    /**
     * ID чата канала уведомлений в телеграм
     */
    define('TELEGRAM_NOTIFICATION_CHANNEL', '-1001714289174');

    /**
     * ID чата админа в телеграм
     */
    define('TELEGRAM_ADMIN_CHAT_ID', '412790359');

    /**
     * Время, в течении которого считаем пользователя онлайн, сек.
     */
    define('USER_ONLINE_TIME', 60 * 5);

    /**
     * Использование кэша
     */
    define('USE_CACHE', false);

    /**
     * Папка кэша
     */
    define('CACHE_DIR', ROOT_PATH . '/core/cache');

    /**
     * Время жизни кэша
     */
    define('CACHE_TTL', 3600);

    /**
     * Код значения Да
     */
    const CODE_VALUE_Y = 'Y';

    /**
     * Код значения Нет
     */
    const CODE_VALUE_N = 'N';

    /**
     * Ключ шифрования
     */
    define('CRYPTO_KEY', '642a43f13133ea61cb6315bf46c89cd26346bd7b2cda43cee6d17b4a733854639b22b7688582b3cc');

    /**
     * Флаг использования логирования
     */
    define('USE_LOG', true);

    /**
     * E-Mail сайта
     */
    define('SERVER_EMAIL', 'noreply@dev.it-stories.ru');

    /**
     * Имя E-Mail сайта
     */
    define('SERVER_EMAIL_NAME', 'Delta Framework');
