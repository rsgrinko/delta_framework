<?php
    require_once __DIR__ . '/config.php';

    /**
     * Реализация механизма автозагрузки классов
     */
    spl_autoload_register(function ($class) {
        if (strpos($class, 'Core') === 0) {
            $class = str_replace('\\', '/', $class);
            $classPath = __DIR__ . '/lib/' . $class . '.class.php';
            echo $classPath;
            if (file_exists($classPath)) {
                require_once $classPath;
            }
        }
    });