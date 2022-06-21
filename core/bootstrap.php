<?php

    use Core\Models\User;
    use Core\Helpers\Cache;
    use Core\Template;
    use Core\CoreException;

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    ob_start(function($buffer) {
        try {
            Template::set('CLEAR_CACHE_LINK_NAME', 'Сброс файлового кэша');
            Template::set('ADMIN_PANEL_LINK_NAME', 'Панель администратора');
            Template::set('REFRESH_PAGE_LINK_NAME', 'Перезагрузить страницу');
            Template::set('PHP_CMD_LINK_NAME', 'Командная PHP строка');
            return Template::render($buffer);
        } catch(Throwable $e) {
            return $e->getMessage();
        }
    });

    define('START_TIME', microtime(true));                   // засекаем время старта скрипта
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

    Cache::init(ROOT_PATH . '/core/cache/', USE_CACHE);

    // очистка кэша
    if(isset($_REQUEST['clear_cache']) && $_REQUEST['clear_cache'] === CODE_VALUE_Y) {
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
    if(!empty($userId)) {
        try {
            $USER = new User($userId);
        } catch (CoreException $e) {
            $USER = null;
        }
    } else {
        $USER = null;
    }