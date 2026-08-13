<?php

    declare(strict_types=1);

    namespace Delta\Security;

    /**
     * Хеширование паролей.
     *
     * Основной алгоритм — bcrypt через password_hash(): у каждой записи своя соль, а стоимость
     * подбора настраивается. Прежняя схема — md5 с одной статической солью на все аккаунты —
     * не защищает от офлайн-перебора и радужных таблиц при утечке базы.
     *
     * Старые хеши продолжают приниматься при входе, но каждый успешный вход с легаси-хешем
     * прозрачно перезаписывает его на bcrypt: миграция происходит сама, без сброса паролей.
     */
    final class PasswordHasher
    {
        /** @var string Алгоритм хеширования */
        private const ALGO = PASSWORD_BCRYPT;

        /**
         * Конструктор
         *
         * @param string $legacySalt Статическая соль прежней схемы md5
         */
        public function __construct(private readonly string $legacySalt = '')
        {
        }

        /**
         * Захешировать пароль
         *
         * @param string $password Пароль
         *
         * @return string Хеш
         */
        public function hash(string $password): string
        {
            return password_hash($password, self::ALGO);
        }

        /**
         * Проверить пароль против хранимого хеша
         *
         * @param string $password Пароль
         * @param string $hash     Хранимый хеш
         *
         * @return bool Признак совпадения
         */
        public function verify(string $password, string $hash): bool
        {
            if ($hash === '') {
                return false;
            }

            if ($this->isLegacy($hash)) {
                return hash_equals($hash, $this->legacyHash($password));
            }

            return password_verify($password, $hash);
        }

        /**
         * Нужно ли перехешировать хранимый хеш
         *
         * @param string $hash Хранимый хеш
         *
         * @return bool Признак необходимости
         */
        public function needsRehash(string $hash): bool
        {
            if ($this->isLegacy($hash)) {
                return true;
            }

            return password_needs_rehash($hash, self::ALGO);
        }

        /**
         * Признак хеша прежней схемы
         *
         * @param string $hash Хранимый хеш
         *
         * @return bool Признак легаси-хеша
         */
        public function isLegacy(string $hash): bool
        {
            return strlen($hash) === 32 && ctype_xdigit($hash);
        }

        /**
         * Хеш по прежней схеме — нужен только для проверки старых паролей
         *
         * @param string $password Пароль
         *
         * @return string Хеш
         */
        public function legacyHash(string $password): string
        {
            return md5($this->legacySalt . $password);
        }
    }
