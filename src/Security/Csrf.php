<?php

    declare(strict_types=1);

    namespace Delta\Security;

    /**
     * Защита от подделки межсайтовых запросов.
     *
     * Синхронайзер-токен: значение хранится в сессии и обязано присутствовать в каждом
     * изменяющем состояние POST-запросе. Нужен потому, что авторизация в проекте держится
     * на cookie, а значит браузер отправит их и при запросе, инициированном чужим сайтом.
     */
    final class Csrf
    {
        /** @var string Имя поля формы и заголовка запроса */
        public const FIELD  = '_token';
        public const HEADER = 'X-CSRF-Token';

        /** @var string Ключ хранения токена в сессии */
        private const SESSION_KEY = '_csrf_token';

        /**
         * Получить токен текущей сессии, создав его при необходимости
         *
         * @return string Токен
         */
        public static function token(): string
        {
            if (empty($_SESSION[self::SESSION_KEY])) {
                $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
            }

            return (string)$_SESSION[self::SESSION_KEY];
        }

        /**
         * Проверить присланный токен
         *
         * Сравнение через hash_equals: обычное сравнение строк уязвимо к атаке по времени.
         *
         * @param string|null $token Присланный токен
         *
         * @return bool Признак корректности
         */
        public static function isValid(?string $token): bool
        {
            if (empty($token) || empty($_SESSION[self::SESSION_KEY])) {
                return false;
            }

            return hash_equals((string)$_SESSION[self::SESSION_KEY], $token);
        }

        /**
         * Сбросить токен (например, после смены пользователя)
         *
         * @return void
         */
        public static function reset(): void
        {
            unset($_SESSION[self::SESSION_KEY]);
        }

        /**
         * Готовое скрытое поле формы
         *
         * @return string HTML-разметка поля
         */
        public static function field(): string
        {
            return '<input type="hidden" name="' . self::FIELD . '" value="'
                . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
        }
    }
