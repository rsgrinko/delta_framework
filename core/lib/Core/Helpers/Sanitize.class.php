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
         * @throws CoreException
         */
        public static function sanitizeNumber($value): int
        {
            $intValue = (int)filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS | FILTER_SANITIZE_NUMBER_INT);
            if ($intValue === 0) {
                throw new CoreException($value . ' не является числом');
            }
            return $intValue;
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

        /**
         * Санитизация E-Mail
         *
         * @param mixed $email E-Mail
         *
         * @return string
         */
        public static function sanitizeEmail($email) {
            $isValidEmail = filter_var($email, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE);
            $emailTmp = filter_var($email, FILTER_SANITIZE_EMAIL);

            if ($email !== $emailTmp || !$isValidEmail || empty($email)) {
                throw new CoreException('Email "' . $email . '" некорректен');
            }
            return $email;
        }
    }