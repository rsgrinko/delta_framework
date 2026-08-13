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
    const CORE_VERSION = '1.0.2';

    /**
     * Флаг отладки
     */
    if (!defined('DEBUG')) {
        define('DEBUG', true); //SystemConfig
    }


    /**
     * Путь до корня проекта
     */
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
    }


    /**
     * Путь до папки ядра
     */
    if (!defined('CORE_PATH')) {
        define('CORE_PATH', ROOT_PATH . '/core');
    }


    /**
     * Адрес проекта
     */
    if (!defined('SITE_URL')) {
        define('SITE_URL', 'https://' . $_SERVER['SERVER_NAME']);
    }


    /**
     * Путь до папки ядра
     */
    if (!defined('SITE_URL_CORE')) {
        define('SITE_URL_CORE', SITE_URL . '/core');
    }

    /**
     * Путь до папки загрузок
     */
    if (!defined('UPLOADS_PATH')) {
        define('UPLOADS_PATH', ROOT_PATH . '/uploads');
    }

    /**
     * Путь до папки почтовых шаблонов
     */
    if (!defined('MAIL_TEMPLATES_PATH')) {
        define('MAIL_TEMPLATES_PATH', CORE_PATH . '/mail_templates');
    }

    /**
     * Почтовый шаблон по умолчанию
     */
    if (!defined('MAIL_TEMPLATE_DEFAULT')) {
        define('MAIL_TEMPLATE_DEFAULT', 'default');
    }

    /**
     * Путь до директории с логами
     */
    if (!defined('LOG_PATH')) {
        define('LOG_PATH', $_SERVER['DOCUMENT_ROOT'] . '/core/log');
    }

    /**
     * Количество элементов на странице
     */
    if (!defined('PAGINATION_LIMIT')) {
        define('PAGINATION_LIMIT', 10);
    }

    /**
     * Параметры SQL базы.
     * Реквизиты доступа задаются в config.local.php (вне git), здесь только безопасные значения
     * по умолчанию. Шаблон окружения — config.local.example.php.
     */
    if (!defined('DB_HOST')) {
        define('DB_HOST', 'localhost');
    }
    if (!defined('DB_USER')) {
        define('DB_USER', '');
    }
    if (!defined('DB_PASSWORD')) {
        define('DB_PASSWORD', '');
    }
    if (!defined('DB_NAME')) {
        define('DB_NAME', '');
    }
    if (!defined('DB_TABLE_PREFIX')) {
        define('DB_TABLE_PREFIX', 'd_');
    }

    /**
     * Путь до шаблонов
     */
    if (!defined('PATH_TO_TEMPLATES')) {
        define('PATH_TO_TEMPLATES', ROOT_PATH . '/templates');
    }

    /**
     * Имя телеграм бота
     */
    if (!defined('TELEGRAM_BOT_USERNAME')) {
        define('TELEGRAM_BOT_USERNAME', 'deltacore_bot');
    }

    /**
     * Токен телеграм бота. Задаётся в config.local.php.
     */
    if (!defined('TELEGRAM_BOT_TOKEN')) {
        define('TELEGRAM_BOT_TOKEN', '');
    }

    /**
     * ID чата канала уведомлений в телеграм
     */
    if (!defined('TELEGRAM_NOTIFICATION_CHANNEL')) {
        define('TELEGRAM_NOTIFICATION_CHANNEL', '-1001714289174');
    }

    /**
     * ID чата админа в телеграм
     */
    if (!defined('TELEGRAM_ADMIN_CHAT_ID')) {
        define('TELEGRAM_ADMIN_CHAT_ID', '412790359');
    }

    /**
     * Время, в течении которого считаем пользователя онлайн, сек.
     */
    if (!defined('USER_ONLINE_TIME')) {
        define('USER_ONLINE_TIME', 60 * 5);
    }

    /**
     * Использование кэша
     */
    if (!defined('USE_CACHE')) {
        define('USE_CACHE', false);
    }

    /**
     * Папка кэша
     */
    if (!defined('CACHE_DIR')) {
        define('CACHE_DIR', ROOT_PATH . '/core/cache');
    }

    /**
     * Время жизни кэша
     */
    if (!defined('CACHE_TTL')) {
        define('CACHE_TTL', 3600);
    }

    /**
     * Код значения Да
     */
    const CODE_VALUE_Y = 'Y';

    /**
     * Код значения Нет
     */
    const CODE_VALUE_N = 'N';

    /**
     * Ключ шифрования. Задаётся в config.local.php.
     * Объявлен через define(), а не const, чтобы значение из config.local.php имело приоритет:
     * const в глобальной области переопределить нельзя.
     */
    if (!defined('CRYPTO_KEY')) {
        define('CRYPTO_KEY', '');
    }

    /**
     * Флаг использования логирования
     */
    if (!defined('USE_LOG')) {
        define('USE_LOG', true);
    }

    /**
     * E-Mail сайта
     */
    if (!defined('SERVER_EMAIL')) {
        define('SERVER_EMAIL', 'noreply@dev.it-stories.ru');
    }

    /**
     * Имя E-Mail сайта
     */
    if (!defined('SERVER_EMAIL_NAME')) {
        define('SERVER_EMAIL_NAME', 'Delta Framework');
    }

    /**
     * Флаг использования Captcha
     */
    if (!defined('USE_CAPTCHA')) {
        define('USE_CAPTCHA', false);
    }

    /**
     * Флаг использования защиты от DDoS
     */
    if (!defined('USE_DDOS_PROTECTION')) {
        define('USE_DDOS_PROTECTION', false);
    }

    /**
     * Формат даты/времени
     */
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * Проверка обязательных реквизитов окружения.
     * Без config.local.php приложение не поднимется, поэтому падаем сразу и с понятным текстом,
     * а не невнятной ошибкой подключения к базе где-то в глубине запроса.
     */
    if (DB_USER === '' || DB_NAME === '') {
        $configErrorMessage = 'Не заданы реквизиты доступа к базе данных. '
            . 'Создайте файл config.local.php в корне проекта по образцу config.local.example.php.';

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $configErrorMessage . PHP_EOL);
        } else {
            http_response_code(500);
            echo $configErrorMessage;
        }
        die(1);
    }
