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
     * Слой совместимости со старым способом хранения настроек.
     *
     * Источник истины для конфигурации — объект Delta\Config\Config и файлы каталога config/.
     * Этот файл лишь публикует те же значения в виде глобальных констант, потому что на них
     * опирается существующий код ядра, админки и cron-задач. По мере перевода кода на Config
     * константы будут исчезать отсюда; новый код должен использовать Config, а не константы.
     *
     * Каждая константа обёрнута в if (!defined(...)) — значение, заданное раньше
     * в config.local.php, имеет приоритет.
     */

    use Delta\Config\Config;

    $config = Config::getInstance();

    /**
     * Окружение и режим отладки
     */
    if (!defined('APP_ENV')) {
        define('APP_ENV', $config->get('app.env'));
    }
    if (!defined('DEBUG')) {
        define('DEBUG', (bool)$config->get('app.debug'));
    }

    /**
     * Версия сервиса
     */
    if (!defined('CORE_VERSION')) {
        define('CORE_VERSION', $config->get('app.version'));
    }

    /**
     * Пути проекта
     */
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $config->get('app.paths.root'));
    }
    if (!defined('CORE_PATH')) {
        define('CORE_PATH', $config->get('app.paths.core'));
    }
    if (!defined('UPLOADS_PATH')) {
        define('UPLOADS_PATH', $config->get('app.paths.uploads'));
    }
    if (!defined('PATH_TO_TEMPLATES')) {
        define('PATH_TO_TEMPLATES', $config->get('app.paths.templates'));
    }
    if (!defined('LOG_PATH')) {
        define('LOG_PATH', $config->get('app.paths.log'));
    }

    /**
     * Адрес проекта
     */
    if (!defined('SITE_URL')) {
        define('SITE_URL', $config->get('app.url'));
    }
    if (!defined('SITE_URL_CORE')) {
        define('SITE_URL_CORE', SITE_URL . '/core');
    }

    /**
     * Почтовые шаблоны
     */
    if (!defined('MAIL_TEMPLATES_PATH')) {
        define('MAIL_TEMPLATES_PATH', $config->get('mail.templates_path'));
    }
    if (!defined('MAIL_TEMPLATE_DEFAULT')) {
        define('MAIL_TEMPLATE_DEFAULT', $config->get('mail.template_default'));
    }

    /**
     * Количество элементов на странице
     */
    if (!defined('PAGINATION_LIMIT')) {
        define('PAGINATION_LIMIT', $config->get('app.pagination_limit'));
    }

    /**
     * Параметры SQL базы
     */
    if (!defined('DB_HOST')) {
        define('DB_HOST', $config->get('database.host'));
    }
    if (!defined('DB_USER')) {
        define('DB_USER', $config->get('database.user'));
    }
    if (!defined('DB_PASSWORD')) {
        define('DB_PASSWORD', $config->get('database.password'));
    }
    if (!defined('DB_NAME')) {
        define('DB_NAME', $config->get('database.name'));
    }
    if (!defined('DB_TABLE_PREFIX')) {
        define('DB_TABLE_PREFIX', $config->get('database.table_prefix'));
    }

    /**
     * Параметры Telegram
     */
    if (!defined('TELEGRAM_BOT_USERNAME')) {
        define('TELEGRAM_BOT_USERNAME', $config->get('telegram.bot_username'));
    }
    if (!defined('TELEGRAM_BOT_TOKEN')) {
        define('TELEGRAM_BOT_TOKEN', $config->get('telegram.bot_token'));
    }
    if (!defined('TELEGRAM_NOTIFICATION_CHANNEL')) {
        define('TELEGRAM_NOTIFICATION_CHANNEL', $config->get('telegram.notification_channel'));
    }
    if (!defined('TELEGRAM_ADMIN_CHAT_ID')) {
        define('TELEGRAM_ADMIN_CHAT_ID', $config->get('telegram.admin_chat_id'));
    }

    /**
     * Время, в течение которого считаем пользователя онлайн, сек.
     */
    if (!defined('USER_ONLINE_TIME')) {
        define('USER_ONLINE_TIME', $config->get('app.user_online_time'));
    }

    /**
     * Кэширование
     */
    if (!defined('USE_CACHE')) {
        define('USE_CACHE', (bool)$config->get('cache.enabled'));
    }
    if (!defined('CACHE_DIR')) {
        define('CACHE_DIR', $config->get('cache.dir'));
    }
    if (!defined('CACHE_TTL')) {
        define('CACHE_TTL', $config->get('cache.ttl'));
    }

    /**
     * Ключ шифрования
     */
    if (!defined('CRYPTO_KEY')) {
        define('CRYPTO_KEY', $config->get('security.crypto_key'));
    }

    /**
     * Флаг использования логирования
     */
    if (!defined('USE_LOG')) {
        define('USE_LOG', (bool)$config->get('app.use_log'));
    }

    /**
     * Параметры почты сайта
     */
    if (!defined('SERVER_EMAIL')) {
        define('SERVER_EMAIL', $config->get('mail.from_email'));
    }
    if (!defined('SERVER_EMAIL_NAME')) {
        define('SERVER_EMAIL_NAME', $config->get('mail.from_name'));
    }

    /**
     * Captcha и защита от DDoS
     */
    if (!defined('USE_CAPTCHA')) {
        define('USE_CAPTCHA', (bool)$config->get('security.use_captcha'));
    }
    if (!defined('USE_DDOS_PROTECTION')) {
        define('USE_DDOS_PROTECTION', (bool)$config->get('security.use_ddos_protection'));
    }

    /**
     * Формат даты/времени
     */
    if (!defined('DATETIME_FORMAT')) {
        define('DATETIME_FORMAT', $config->get('app.datetime_format'));
    }

    /**
     * Коды значений Да/Нет
     */
    if (!defined('CODE_VALUE_Y')) {
        define('CODE_VALUE_Y', 'Y');
    }
    if (!defined('CODE_VALUE_N')) {
        define('CODE_VALUE_N', 'N');
    }

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

    unset($config);
