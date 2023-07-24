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

    namespace Core\Helpers;

    class Registry
    {
        /** @var array $_storage Массив данных */
        private static array $_storage = [];

        /**
         * Установка значения
         *
         * @param string $key   Ключ
         * @param mixed  $value Значение
         */
        public static function set(string $key, $value): void
        {
            self::$_storage[$key] = $value;
        }

        /**
         * Получение значения
         *
         * @param string $key     Ключ
         * @param mixed  $default Значение по умолчанию
         *
         * @return mixed|null
         */
        public static function get(string $key, $default = null)
        {
            return (isset(self::$_storage[$key])) ? self::$_storage[$key] : $default;
        }

        /**
         * Удаление
         *
         * @param string $key Ключ
         */
        public static function remove(string $key): void
        {
            unset(self::$_storage[$key]);
        }

        /**
         * Полная очистка
         */
        public static function flush(): void
        {
            self::$_storage = [];
        }
    }