<?php

    declare(strict_types=1);

    namespace Delta\Config;

    /**
     * Доступ к значениям окружения.
     *
     * Порядок поиска значения фиксирован и одинаков для всех вызовов:
     *   1. переменная окружения процесса ($_ENV / $_SERVER / getenv);
     *   2. одноимённая глобальная константа — так подхватывается config.local.php,
     *      который на сегодня является хранилищем секретов проекта;
     *   3. значение по умолчанию, переданное вызывающим кодом.
     *
     * Файл .env сознательно не используется до переноса docroot в public/ (Этап 3):
     * сейчас корень проекта совпадает с корнем сайта, а веб-сервер отдаёт любой файл
     * не с расширением .php сырым текстом — то есть .env читался бы прямо по URL.
     * config.local.php такой проблемы не имеет: это исполняемый PHP-файл без вывода.
     */
    final class Env
    {
        /**
         * Получить значение окружения
         *
         * @param string $key     Имя переменной
         * @param mixed  $default Значение по умолчанию
         *
         * @return mixed Значение
         */
        public static function get(string $key, mixed $default = null): mixed
        {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

            if ($value === false || $value === null || $value === '') {
                if (defined($key)) {
                    return constant($key);
                }

                return $default;
            }

            return self::cast($value);
        }

        /**
         * Приведение строкового значения окружения к типу PHP
         *
         * @param mixed $value Значение
         *
         * @return mixed Приведённое значение
         */
        private static function cast(mixed $value): mixed
        {
            if (is_string($value) === false) {
                return $value;
            }

            return match (strtolower($value)) {
                'true', '(true)'   => true,
                'false', '(false)' => false,
                'null', '(null)'   => null,
                'empty', '(empty)' => '',
                default            => $value,
            };
        }
    }
