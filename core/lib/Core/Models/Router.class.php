<?php

    namespace Core\Models;


    class Router
    {

        /**
         * Страница 404 ошибки
         */
        const ERROR_PAGE = '/^\/404$/';

        /**
         * @var array $routes Маршруты
         */
        private static $routes = [];

        /**
         * Метод вывода 404 ошибки
         */
        private static function notFound()
        {
            header('HTTP/1.0 404 Not Found');
            echo '404 - Not Found';
        }

        /**
         * Добавление маршрута в таблицу маршрутизации
         *
         * @param string $pattern  Маршрут
         * @param mixed $callback Callback функция
         *
         * @return void
         */
        public static function route(string $pattern, $callback)
        {
            $pattern                = '/^' . str_replace('/', '\/', $pattern) . '$/';
            self::$routes[$pattern] = $callback;
        }

        /**
         * Обработка маршрута
         *
         * @return mixed|void
         */
        public static function execute()
        {
            foreach (self::$routes as $pattern => $callback) {
                if (preg_match($pattern, $_SERVER['REQUEST_URI'], $params)) // сравнение идет через регулярное выражение
                {
                    array_shift($params);
                    return call_user_func_array($callback, array_values($params));
                }
            }

            if (in_array(self::ERROR_PAGE, array_keys(self::$routes), true)) {
                return self::$routes[self::ERROR_PAGE]();
            } else {
                return self::notFound();
            }
        }

    }