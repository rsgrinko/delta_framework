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
     * Класс реестра
     */

    namespace Core\Helpers;

    class Registry
    {
        private static $_storage = [];

        /**
         * Установка значения
         */
        public static function set($key, $value)
        {
            return self::$_storage[$key] = $value;
        }

        /**
         * Получение значения
         */
        public static function get($key, $default = null)
        {
            return (isset(self::$_storage[$key])) ? self::$_storage[$key] : $default;
        }

        /**
         * Удаление
         */
        public static function del($key): void
        {
            unset(self::$_storage[$key]);
        }

        /**
         * Очистка
         */
        public static function flush(): void
        {
            self::$_storage = [];
        }
    }