<?php

    use Core\Models\User;
    use Core\Helpers\Cache;
    use Core\Template;

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    /*ob_start(function($buffer) {
        Template::set('CLEAR_CACHE_LINK_NAME', 'Сброс файлового кэша');
        Template::set('ADMIN_PANEL_LINK_NAME', 'Панель администратора');
        Template::set('REFRESH_PAGE_LINK_NAME', 'Перезагрузить страницу');
        return Template::render($buffer);
    });*/

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


    if(isset($_REQUEST['clear_cache']) && $_REQUEST['clear_cache'] === CODE_VALUE_Y) {
        Cache::flush();
    }