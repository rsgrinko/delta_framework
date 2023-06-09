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
    use Core\Models\User;
    use Core\Helpers\Cache;
    use Core\Models\UTM;
    use Core\Template;
    use Core\CoreException;
    use Core\Models\Router;

    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    //error_reporting(E_ALL);

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    date_default_timezone_set('Europe/Moscow');

    require_once __DIR__ . '/../vendor/autoload.php';
    \Sentry\init(
        [
            'dsn'         => 'https://8657ac1a900c44e5bc51714bb4c00dbb@o1303100.ingest.sentry.io/6541634',
            'error_types' => E_ALL & ~E_NOTICE,
        ]
    );

    Sentry\configureScope(function (Sentry\State\Scope $sentryScope) {
        $sentryScope->setExtra('user_id', $_SESSION['id'] ?? '');
        $sentryScope->setExtra('user_login', $_SESSION['login'] ?? '');
        $sentryScope->setExtra('user_token', $_SESSION['token'] ?? '');
    });

    ob_start(function ($buffer) {
        try {
            Template::set('CLEAR_CACHE_LINK_NAME', 'Сброс файлового кэша');
            Template::set('ADMIN_PANEL_LINK_NAME', 'Панель администратора');
            Template::set('REFRESH_PAGE_LINK_NAME', 'Перезагрузить страницу');
            Template::set('PHP_CMD_LINK_NAME', 'Командная PHP строка');
            return Template::render($buffer);
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    });

    define('START_TIME', microtime(true)); // засекаем время старта скрипта
    const CORE_LOADED = true; // флаг корректного запуска

    if (empty($_SERVER['SERVER_NAME'])) {
        $_SERVER['SERVER_NAME'] = 'localhost';
    }
    if (empty($_SERVER['DOCUMENT_ROOT'])) {
        $_SERVER['DOCUMENT_ROOT'] = __DIR__. '/../';
    }

    require_once __DIR__ . '/config.php';

    // Если имеется файл локальной конфигурации - подключаем его
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/config.local.php')) {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/config.local.php';
    }


    /**
     * Реализация механизма автозагрузки классов
     */
    spl_autoload_register(function ($class) {
        if (strpos($class, 'Core') === 0) {
            $class     = str_replace('\\', '/', $class);
            $classPath = ROOT_PATH . '/core/lib/' . $class . '.class.php';
            if (file_exists($classPath)) {
                require_once $classPath;
            }
        }
    });

    // Инициализация кеша
    Cache::init(CACHE_DIR, USE_CACHE);

    // Обработка UTM меток
    (new UTM())->save();

    // очистка кэша
    if (isset($_REQUEST['clear_cache']) && $_REQUEST['clear_cache'] === CODE_VALUE_Y) {
        Cache::flush();
    }

    // выход из системы
    if (isset($_REQUEST['logout']) && $_REQUEST['logout'] === CODE_VALUE_Y) {
        User::logout();
    }

    try {
        $userId = User::getCurrentUserId();
    } catch (CoreException $e) {
        $userId = null;
    }
    if (!empty($userId)) {
        try {
            $USER = new User($userId);
        } catch (CoreException $e) {
            $USER = null;
        }
    } else {
        $USER = null;
    }

    // debug
    function sendTelegram(?string $message, ?string $file = null): void
    {
        (new \Core\ExternalServices\TelegramSender(TELEGRAM_BOT_TOKEN))->setChat(TELEGRAM_NOTIFICATION_CHANNEL)->sendMessage($message);

        if (!empty($file)) {
            (new \Core\ExternalServices\TelegramSender(TELEGRAM_BOT_TOKEN))->setChat(TELEGRAM_NOTIFICATION_CHANNEL)->sendDocument($file);
        }
    }

    //end debug

    /**
     * Запускаем маршрутизатор если не сказано иного
     */
    if (defined('USE_ROUTER') && USE_ROUTER === true) {
        /**
         * Инициализация шаблонизатора
         */
        $loader = new \Twig\Loader\FilesystemLoader(PATH_TO_TEMPLATES);
        $twig   = new \Twig\Environment($loader, [
            'debug' => true,
            //'cache' => CACHE_DIR,
        ]);
        $twig->addExtension(new \Twig\Extension\DebugExtension());
        require_once __DIR__ . '/routes.php';
        Router::execute();
        die();
    }