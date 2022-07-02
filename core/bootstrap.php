<?php

    use Core\Models\User;
    use Core\Helpers\Cache;
    use Core\Template;
    use Core\CoreException;
    use Core\Models\Router;

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
    /*
        try {
        $this->functionFailsForSure();
    } catch (\Throwable $exception) {
        \Sentry\captureException($exception);
    }

    // OR

    \Sentry\captureLastError();
        */


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

    define('START_TIME', microtime(true));                          // засекаем время старта скрипта
    const CORE_LOADED = true;                                       // флаг корректного запуска

    require_once __DIR__ . '/config.php';


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

    require_once __DIR__ . '/routes.php';

    /**
     * Инициализация шаблонизатора
     */
    $loader = new \Twig\Loader\FilesystemLoader(PATH_TO_TEMPLATES);
    $twig   = new \Twig\Environment($loader, [//'cache' => CACHE_DIR,
    ]);

    Cache::init(CACHE_DIR, USE_CACHE);

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

    /**
     * Запускаем маршрутизатор если не сказано иного
     */
    if (defined('USE_ROUTER') && USE_ROUTER === true) {
        Router::execute();
        die();
    }