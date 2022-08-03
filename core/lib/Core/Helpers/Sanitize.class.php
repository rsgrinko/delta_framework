<?php
    namespace Core\Helpers;
    use Core\CoreException;

    class Sanitize {
        /**
         * Санитизация строки
         *
         * @param string $value Значение
         *
         * @return string
         */
        public static function sanitizeString(string $value): string
        {
            return (string)filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
        }

        /**
         * Санитизация целого числа
         *
         * @param mixed $value Значение
         *
         * @return int
         */
        public static function sanitizeNumber($value): int
        {
            return (int)filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS | FILTER_SANITIZE_NUMBER_INT);
        }

        /**
         * Санитизация числа с плавающей точкой
         *
         * @param mixed $value Значение
         *
         * @return float
         */
        public static function sanitizeFloat($value): float
        {
            return (float)filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS | FILTER_SANITIZE_NUMBER_FLOAT);
        }
    }