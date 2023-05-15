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
        private static array $routes = [];

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
         * @param mixed  $callback Callback функция
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