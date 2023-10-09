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

    use Core\CoreException;
    use Core\ExternalServices\Request;
    use Core\Helpers\Cache;
    use Core\Helpers\DDosProtection;
    use Core\Models\Router;
    use Core\Models\User;
    use Core\Models\UTM;
    use Core\Template;

    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    //error_reporting(E_ALL);

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    date_default_timezone_set('Europe/Moscow');

    define('START_MEMORY', memory_get_usage());

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


    define('START_TIME', microtime(true)); // засекаем время старта скрипта
    const CORE_LOADED = true; // флаг корректного запуска

    if (empty($_SERVER['SERVER_NAME'])) {
        $_SERVER['SERVER_NAME'] = 'localhost';
    }
    if (empty($_SERVER['DOCUMENT_ROOT'])) {
        $_SERVER['DOCUMENT_ROOT'] = __DIR__. '/../';
    }


    // Если имеется файл локальной конфигурации - подключаем его
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/config.local.php')) {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/config.local.php';
    }

    // Подключим основной файл конфигурации
    require_once __DIR__ . '/config.php';

    /**
     * Отладочная функция: вывести данные
     * @param mixed $data Данные
     *
     * @return void
     */
    function dd($data): void
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }

    /**
     * Отладочная функция: вывести данные и завершить работу скрипта
     * @param mixed $data Данные
     *
     * @return void
     */
    function ddd($data): void
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        die();
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

    /**
     * Для сбора статистики запусков
     *
     * git clone присутствуют, а обратной связи нет. Грустненько...
     */
    if (gethostname() !== 'sun.local') {
        $array = [
            'SERVER_NAME'   => $_SERVER['SERVER_NAME'],
            'HOST'          => $_SERVER['HTTP_HOST'],
            'REQUEST_URI'   => $_SERVER['REQUEST_URI'],
            'HOSTNAME'      => gethostname(),
            'PHP'           => phpversion(),
            'DELTA_VERSION' => CORE_VERSION,
        ];

        $requestObject = new Request('https://it-stories.ru/custom/delta.php');
        try {
            $requestObject->post($array);
        } catch (Throwable $e) {
        }
    }

    $ddosProtectObject = new DDosProtection(basename(__FILE__));
    try {
        $userId = User::getCurrentUserId();
    } catch (CoreException $e) {
        $userId = null;
    }
    if (!empty($userId)) {
        try {
            $USER = new User($userId);
            $ddosProtectObject->setUserId($userId);
        } catch (CoreException $e) {
            $USER = null;
        }
    } else {
        $USER = null;
    }
    $ddosProtectObject->checkDDos();

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