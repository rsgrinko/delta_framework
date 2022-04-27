<?php

    use Core\Models\User;
    use Core\Helpers\Cache;
    use Core\Template;
    use Core\ExternalServices\Telegram;
    use Core\CoreException;

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    ob_start(function($buffer) {
        try {
            Template::set('CLEAR_CACHE_LINK_NAME', 'Сброс файлового кэша');
            Template::set('ADMIN_PANEL_LINK_NAME', 'Панель администратора');
            Template::set('REFRESH_PAGE_LINK_NAME', 'Перезагрузить страницу');
            return Template::render($buffer);
        } catch(Throwable $e) {
            return $e->getMessage();
        }
    });

    define('START_TIME', microtime(true));                   // засекаем время старта скрипта
    define('CORE_LOADED', true);                                    // флаг корректного запуска

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




    Cache::init(ROOT_PATH . '/core/cache/', true);

    // очистка кэша
    if(isset($_REQUEST['clear_cache']) && $_REQUEST['clear_cache'] === CODE_VALUE_Y) {
        Cache::flush();
    }

    // выход из системы
    if (isset($_REQUEST['logout']) && $_REQUEST['logout'] === CODE_VALUE_Y) {
        User::logout();
    }

    Telegram::init('5232660453:AAGfMWu6EcRfBGSSURJsEEvGPmAqhCyzYHU', './');


    $userId = User::getCurrentUserId();
    if(!empty($userId)) {
        try {
            $USER = new User($userId);
        } catch (CoreException $e) {
            $USER = null;
        }
    } else {
        $USER = null;
    }