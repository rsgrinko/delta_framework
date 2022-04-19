<?php
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