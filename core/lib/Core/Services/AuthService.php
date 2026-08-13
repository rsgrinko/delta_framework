<?php

    declare(strict_types=1);

    namespace Core\Services;

    use Core\Database\DataBase;
    use Core\Database\DatabaseException;
    use Core\Helpers\Log;
    use Delta\Security\PasswordHasher;

    /**
     * Сервис авторизации.
     *
     * Сюда собрано всё, что раньше делала модель `User` помимо работы с данными: проверка пароля,
     * наполнение сессии, выдача и сверка cookie автологина, выход. Модель осталась моделью,
     * а состояние запроса перестало быть её ответственностью.
     *
     * Сервис намеренно не статический: его можно создать с другим соединением или другим хешером,
     * что и делает поведение авторизации проверяемым.
     */
    final class AuthService
    {
        /** @var string[] Ключи сессии, которыми владеет авторизация */
        private const SESSION_KEYS = ['id', 'authorize', 'login', 'password', 'token', 'user'];

        /** @var string[] Имена cookie автологина */
        private const COOKIE_KEYS = ['userId', 'userLogin', 'token'];

        /** @var int Срок жизни cookie автологина, сек. */
        private const COOKIE_LIFETIME = 3600 * 24;

        /**
         * Конструктор
         *
         * @param DataBase       $db     Соединение с базой
         * @param PasswordHasher $hasher Хеширование паролей
         * @param string         $table  Таблица пользователей
         * @param string         $salt   Соль для производных значений сессии и cookie
         */
        public function __construct(
            private readonly DataBase $db,
            private readonly PasswordHasher $hasher,
            private readonly string $table,
            private readonly string $salt,
        ) {
        }

        /**
         * Попытка входа по логину и паролю
         *
         * @param string $login    Логин
         * @param string $password Пароль
         * @param bool   $remember Запомнить вход
         *
         * @return bool Признак успеха
         * @throws DatabaseException
         */
        public function attempt(string $login, string $password, bool $remember = false): bool
        {
            // Искать по хешу пароля нельзя: у bcrypt своя соль на каждую запись
            $user = $this->db->get($this->table, ['login' => $login]);

            if (empty($user) || $this->hasher->verify($password, (string)$user['password']) === false) {
                return false;
            }

            $user['password'] = $this->rehashIfNeeded((int)$user['id'], $password, (string)$user['password']);

            $this->startSession($user, $remember);

            return true;
        }

        /**
         * Вход по идентификатору, без проверки пароля
         *
         * @param int  $id       Идентификатор пользователя
         * @param bool $remember Запомнить вход
         *
         * @return bool Признак успеха
         * @throws DatabaseException
         */
        public function loginById(int $id, bool $remember = false): bool
        {
            $user = $this->db->get($this->table, ['id' => $id]);

            if (empty($user)) {
                return false;
            }

            $this->startSession($user, $remember);

            return true;
        }

        /**
         * Текущий пользователь авторизован
         *
         * @return bool Признак авторизации
         * @throws DatabaseException
         */
        public function check(): bool
        {
            if ($this->checkByCookie()) {
                return true;
            }

            return $this->checkBySession();
        }

        /**
         * Идентификатор текущего пользователя
         *
         * @return int|null Идентификатор
         * @throws DatabaseException
         */
        public function id(): ?int
        {
            return $this->check() ? (int)$_SESSION['id'] : null;
        }

        /**
         * Выход из системы
         *
         * @return void
         */
        public function logout(): void
        {
            foreach (self::SESSION_KEYS as $key) {
                unset($_SESSION[$key]);
            }

            foreach (self::COOKIE_KEYS as $name) {
                $this->writeCookie($name, '', time() - 3600);
            }
        }

        /**
         * Отпечаток пароля для проверки согласованности сессии.
         *
         * В сессии хранится не сам хеш пароля, а производное значение: оно меняется при смене
         * пароля и тем самым делает прежнюю сессию недействительной.
         *
         * @param string $storedHash Хранимый хеш пароля
         *
         * @return string Отпечаток
         */
        public function sessionFingerprint(string $storedHash): string
        {
            return hash('sha256', $this->salt . $storedHash);
        }

        /**
         * Значение cookie-токена автологина
         *
         * @param int    $id         Идентификатор пользователя
         * @param string $login      Логин
         * @param string $storedHash Хранимый хеш пароля
         *
         * @return string Токен
         */
        public function cookieToken(int $id, string $login, string $storedHash): string
        {
            return hash_hmac('sha256', $id . '|' . $login . '|' . $storedHash, $this->salt);
        }

        /**
         * Наполнить сессию данными вошедшего пользователя
         *
         * @param array $user     Данные пользователя
         * @param bool  $remember Запомнить вход
         *
         * @return void
         */
        private function startSession(array $user, bool $remember): void
        {
            $_SESSION['id']        = $user['id'];
            $_SESSION['authorize'] = 'Y';
            $_SESSION['login']     = $user['login'];
            $_SESSION['password']  = $this->sessionFingerprint((string)$user['password']);
            $_SESSION['token']     = $user['token'] ?? null;
            $_SESSION['user']      = $user;

            if ($remember === false) {
                return;
            }

            $expires = time() + self::COOKIE_LIFETIME;
            $this->writeCookie('userId', (string)$user['id'], $expires);
            $this->writeCookie('userLogin', (string)$user['login'], $expires);
            $this->writeCookie(
                'token',
                $this->cookieToken((int)$user['id'], (string)$user['login'], (string)$user['password']),
                $expires,
            );
        }

        /**
         * Проверка авторизации по cookie автологина
         *
         * @return bool Признак успеха
         * @throws DatabaseException
         */
        private function checkByCookie(): bool
        {
            if (empty($_COOKIE['userId']) || empty($_COOKIE['userLogin']) || empty($_COOKIE['token'])) {
                return false;
            }

            $user = $this->db->get($this->table, ['id' => (int)$_COOKIE['userId']]);

            if (empty($user) || (string)$user['login'] !== (string)$_COOKIE['userLogin']) {
                return false;
            }

            $expected = $this->cookieToken((int)$user['id'], (string)$user['login'], (string)$user['password']);

            if (hash_equals($expected, (string)$_COOKIE['token']) === false) {
                return false;
            }

            if (empty($_SESSION['authorize'])) {
                $this->startSession($user, false);
            }

            $this->touch((int)$user['id']);

            return true;
        }

        /**
         * Проверка авторизации по сессии
         *
         * @return bool Признак успеха
         * @throws DatabaseException
         */
        private function checkBySession(): bool
        {
            if (empty($_SESSION['authorize']) || $_SESSION['authorize'] !== 'Y' || empty($_SESSION['login'])) {
                return false;
            }

            $user = $this->db->get($this->table, ['login' => $_SESSION['login']]);

            if (empty($user)) {
                return false;
            }

            // Пароль мог измениться в обход этой сессии — тогда она обязана перестать действовать
            if (hash_equals($this->sessionFingerprint((string)$user['password']), (string)$_SESSION['password']) === false) {
                return false;
            }

            $this->touch((int)$user['id']);
            $_SESSION['id'] = $user['id'];

            return true;
        }

        /**
         * Обновить время последней активности
         *
         * @param int $userId Идентификатор пользователя
         *
         * @return void
         * @throws DatabaseException
         */
        private function touch(int $userId): void
        {
            $this->db->update($this->table, ['id' => $userId], ['last_active' => time()]);
        }

        /**
         * Перехешировать пароль, если хранимый хеш устарел.
         * Вызывается только после успешной проверки: лишь в этот момент открытый пароль доступен.
         *
         * @param int    $userId   Идентификатор пользователя
         * @param string $password Открытый пароль
         * @param string $hash     Текущий хранимый хеш
         *
         * @return string Актуальный хеш
         */
        private function rehashIfNeeded(int $userId, string $password, string $hash): string
        {
            if ($this->hasher->needsRehash($hash) === false) {
                return $hash;
            }

            $newHash = $this->hasher->hash($password);

            try {
                $this->db->update($this->table, ['id' => $userId], ['password' => $newHash]);
            } catch (DatabaseException) {
                // Вход всё равно состоялся, миграция повторится при следующем входе
                Log::logToFile('Не удалось перехешировать пароль', 'User.log', ['userId' => $userId]);

                return $hash;
            }

            return $newHash;
        }

        /**
         * Записать cookie с флагами безопасности.
         *
         * HttpOnly закрывает cookie от JavaScript, SameSite=Lax не даёт браузеру отправлять её
         * при межсайтовых POST-запросах, Secure выставляется только на HTTPS.
         *
         * @param string $name    Имя cookie
         * @param string $value   Значение
         * @param int    $expires Время истечения
         *
         * @return void
         */
        private function writeCookie(string $name, string $value, int $expires): void
        {
            // После отправки вывода заголовки менять уже нельзя: в вебе это ошибка,
            // в CLI (тесты, cron) — обычное состояние, в обоих случаях вызов бессмыслен
            if (headers_sent()) {
                return;
            }

            setcookie($name, $value, [
                'expires'  => $expires,
                'path'     => '/',
                'httponly' => true,
                'secure'   => (($_SERVER['HTTPS'] ?? '') !== '') && $_SERVER['HTTPS'] !== 'off',
                'samesite' => 'Lax',
            ]);
        }
    }
