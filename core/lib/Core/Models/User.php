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
     * Класс для работы с пользователями
     */

    namespace Core\Models;

    use Core\CoreException;
    use Core\Helpers\{Cache, Log, Mail, Sanitize, SystemFunctions};
    use Core\Database\DataBase;
    use Core\Database\DatabaseException;
    use Core\DTO\User\FilterUser;
    use Delta\Security\PasswordHasher;
    use Throwable;

    class User
    {

        /**
         * ID текущего пользователя
         *
         * @var int
         */
        public int $id;

        /**
         * Объект групп
         *
         * @var Roles|null
         */
        public ?Roles $rolesObject = null;

        /**
         * Объект Mail
         *
         * @var Mail|null
         */
        public ?Mail $mailObject = null;

        /**
         * Объект Dialog
         *
         * @var Dialog|null
         */
        public ?Dialog $dialogObject = null;

        /**
         * Объект мета данных
         *
         * @var UserMeta|null
         */
        public ?UserMeta $metaObject = null;

        /**
         * Таблица с пользователями
         */
        private const TABLE = DB_TABLE_PREFIX . 'users';

        /**
         * @var string $cryptoSalt Соль для шифрования
         */
        private static string $cryptoSalt = 'BKH92FdiQvEW2aOy0giywXasYAMl0pFvIrlop8Sz';

        /**
         * Конструктор класса
         *
         * @param int|null $id Идентификатор пользователя
         *
         * @throws CoreException
         */
        public function __construct(?int $id)
        {
            if (empty($id)) {
                throw new CoreException('Передан некорректный идентификатор пользователя', CoreException::ERROR_INCORRECT_USER_ID);
            }
            if (!self::isUserExistsByParams(['id' => $id])) {
                throw new CoreException('Пользователь с идентификатором ' . $id . ' отсутствует в базе', CoreException::ERROR_USER_NOT_FOUND);
            }

            $this->id = $id;
        }

        /**
         * Получение аватара пользователя
         *
         * @return array|null
         * @throws CoreException
         */
        public function getImage(): ?array
        {
            $result  = null;
            $imageId = $this->getAllUserData()['image_id'];

            if (!empty($imageId)) {
                try {
                    $result = (new File($imageId))->getAllProps();
                } catch (CoreException $e) {
                }
            }

            return $result;
        }

        /**
         * Получение всех полей пользователя
         *
         * @param bool $onlySecureData Флаг получения только безопасных данных
         *
         * @return array|null
         * @throws CoreException
         */
        public function getAllUserData(bool $onlySecureData = false): ?array
        {
            $onlySecureDataCacheKey = (int)$onlySecureData;
            $cacheId = md5('User_getAllUserData_' . $onlySecureDataCacheKey . '_' . $this->id);
            if (Cache::check($cacheId)) {
                $result = Cache::get($cacheId);
            } else {
                $result = (DataBase::getInstance())->get(self::TABLE, ['id' => $this->id]);
                $result = array_merge($result, ['roles' => $this->getRolesObject()->getRoles()]);
                $result['nameForDisplay'] = '[' . $result['id'] . '] (' . $result['login'] . ') ' . $result['name'];

                // В случае запроса безопасных данных убираем лишнее
                if ($onlySecureData) {
                    foreach($result as $key => $value) {
                        if (in_array($key, ['password', 'email_confirmed', 'verification_code', 'token', 'roles', ''], true)) {
                            unset($result[$key]);
                        }
                    }
                }

                Cache::set($cacheId, $result);
            }

            if (!empty($result)) {
                return $result;
            }

            return null;
        }

        /**
         * Получение логина текущего пользователя
         *
         * @return string|null
         * @throws CoreException
         */
        public function getLogin(): ?string
        {
            return $this->getAllUserData() ? $this->getAllUserData()['login'] : null;
        }

        /**
         * Получение E-Mail'а текущего пользователя
         *
         * @return string|null
         * @throws CoreException
         */
        public function getEmail(): ?string
        {
            return $this->getAllUserData() ? $this->getAllUserData()['email'] : null;
        }

        /**
         * Получение имени текущего пользователя
         *
         * @return string|null
         * @throws CoreException
         */
        public function getName(): ?string
        {
            return $this->getAllUserData() ? $this->getAllUserData()['name'] : null;
        }

        /**
         * Получение токена текущего пользователя
         *
         * @return string|null
         * @throws CoreException
         */
        public function getToken(): ?string
        {
            return $this->getAllUserData() ? $this->getAllUserData()['token'] : null;
        }

        /**
         * Получение времени последней активности
         *
         * @return int|null
         * @throws CoreException
         */
        public function getLastActive(): ?int
        {
            return $this->getAllUserData() ? (int)$this->getAllUserData()['last_active'] : null;
        }

        /**
         * Получение идентификатора текущего пользователя
         *
         * @return int|null
         */
        public function getId(): ?int
        {
            return $this->id;
        }

        /**
         * Получение кода верификации
         *
         * @return string|null
         * @throws CoreException
         */
        public function getVerificationCode(): ?string
        {
            return $this->getAllUserData() ? $this->getAllUserData()['verification_code'] : null;
        }

        /**
         * Получение всех пользователей панели
         *
         * @param string $limit Лимит
         * @param string $sort  Сортировка
         *
         * @return array
         * @throws CoreException
         */
        public static function getUsers(string $limit = '10', string $sort = 'ASC'): array
        {
            // ORDER BY/LIMIT — это не значения, а часть синтаксиса запроса, поэтому плейсхолдер
            // подготовленного выражения тут не применим: направление сортировки идёт через
            // белый список, а лимит (может быть строкой вида "0, 10" от Pagination::getLimit())
            // разбирается на числа и приводится к int перед подстановкой.
            $safeSort  = strtoupper($sort) === 'DESC' ? 'DESC' : 'ASC';
            $safeLimit = implode(', ', array_map('intval', explode(',', $limit)));
            $cacheId = md5('User_getUsers_' . $safeLimit . '_' . $safeSort);
            if (Cache::check($cacheId)) {
                $res = Cache::get($cacheId);
            } else {
                $res = (DataBase::getInstance())->query('SELECT * FROM `'.self::TABLE.'` ORDER BY `id` '.$safeSort.' LIMIT '.$safeLimit);
                Cache::set($cacheId, $res);
            }
            return $res;
        }

        /**
         * Создание пользовательского токена
         *
         * @return string
         * @throws CoreException
         */
        public function createToken(): string
        {
            $newToken = self::generateGUID();
            (DataBase::getInstance())->update(self::TABLE, ['id' => $this->id], ['token' => $newToken]);
            Log::logToFile('Создан токен', 'User.log', ['userId' => $this->id, 'token' => $newToken]);
            return $newToken;
        }

        /**
         * Генерация GUID
         *
         * @return string
         * @throws \Exception
         */
        private static function generateGUID(): string
        {
            $uid  = dechex(microtime(true) * 1000) . bin2hex(random_bytes(8));
            $guid = vsprintf('RG%s-1000-%s-8%.3s-%s%s%s0', str_split($uid, 4));
            Log::logToFile('Сгенерирован GUID', 'User.log', ['GUID' => $guid]);
            return strtoupper($guid);
        }


        /**
         * Проверка существования токена
         *
         * @param string $token Токен
         *
         * @return bool
         */
        public static function isTokenExists(string $token): bool
        {
            $result = (DataBase::getInstance())->get(self::TABLE, ['token' => $token]);
            if ($result) {
                return true;
            }

            return false;
        }

        /**
         * Получение пользователя по токену
         *
         * @param string $token Токен
         *
         * @return User|null
         * @throws CoreException
         */
        public static function getByToken(string $token): ?self
        {
            $result = (DataBase::getInstance())->get(self::TABLE, ['token' => $token]);
            if ($result) {
                return (new self((int)$result['id']));
            }

            return null;
        }

        /**
         * Проверка пользователя на существование по параметрам
         *
         * @param array $where Массив параметров фильтра
         *
         * @return bool
         */
        public static function isUserExistsByParams(array $where): bool
        {
            $result = (DataBase::getInstance())->get(self::TABLE, $where);
            if (!empty($result)) {
                return true;
            }

            return false;
        }

        /**
         * Получение объекта пользователя по параметрам
         *
         * @param array $where Массив параметров фильтра
         *
         * @return self
         * @throws CoreException
         */
        public static function getByParams(array $where): ?self
        {
            $result = (DataBase::getInstance())->get(self::TABLE, $where);
            if (!empty($result)) {
                return (new self((int)$result['id']));
            }

            return null;
        }

        /**
         * Получить объект пользователя по ID при его существовании
         *
         * @throws CoreException
         */
        public static function getById(int $id): ?self
        {
            if (self::isUserExistsByParams(['id' => $id])) {
                return new self($id);
            }
            return null;
        }

        /**
         * Получение строки с данными о пользователе по ID
         *
         * @return string [123] (admin) Роман
         * @throws CoreException
         */
        public static function getInfoById(int $id): string
        {
            if (self::isUserExistsByParams(['id' => $id])) {
                $objectUser = new self($id);
                return '[' . $id . '] (' . $objectUser->getLogin() . ') ' . $objectUser->getName();
            }
            return 'Неизвестно';
        }

        /**
         * Проверка пользователя на онлайн
         *
         * @param int $id
         *
         * @return bool
         */
        public static function isOnline(int $id): bool
        {
            $res         = (DataBase::getInstance())->query('SELECT last_active FROM `'.self::TABLE.'` WHERE id=:id', ['id' => $id]);
            $last_active = $res[0]['last_active'];
            $timeNow     = time();
            if ($last_active > ($timeNow - USER_ONLINE_TIME)) {
                return true;
            }

            return false;
        }

        /**
         * Получение количества пользователей, находящихся сейчас онлайн
         *
         * @return int
         */
        public static function getOnlineCount(): int
        {
            $res = (DataBase::getInstance())->query(
                'SELECT COUNT(*) as count FROM `' . self::TABLE . '` WHERE last_active > :threshold',
                ['threshold' => time() - USER_ONLINE_TIME]
            );
            return (int)$res[0]['count'];
        }

        /**
         * Шифрование пароля
         *
         * @param string $password Пароль
         *
         * @return string
         */
        public static function passwordEncryption(string $password): string
        {
            return self::hasher()->hash($password);
        }

        /**
         * Объект хеширования паролей
         *
         * @return PasswordHasher Хешер
         */
        private static function hasher(): PasswordHasher
        {
            return new PasswordHasher(self::$cryptoSalt);
        }

        /**
         * Проверить пароль против хранимого хеша.
         * Принимаются как новые bcrypt-хеши, так и старые md5 — последние мигрируются при входе.
         *
         * @param string $password Пароль
         * @param string $hash     Хранимый хеш
         *
         * @return bool Признак совпадения
         */
        public static function verifyPassword(string $password, string $hash): bool
        {
            return self::hasher()->verify($password, $hash);
        }

        /**
         * Перехешировать пароль, если хранимый хеш устарел.
         *
         * Вызывается после успешной проверки пароля: только в этот момент открытый пароль
         * доступен, а значит можно бесшовно перевести запись на bcrypt.
         *
         * @param int    $userId   Идентификатор пользователя
         * @param string $password Открытый пароль
         * @param string $hash     Текущий хранимый хеш
         *
         * @return string Актуальный хеш
         */
        private static function rehashIfNeeded(int $userId, string $password, string $hash): string
        {
            $hasher = self::hasher();

            if ($hasher->needsRehash($hash) === false) {
                return $hash;
            }

            $newHash = $hasher->hash($password);

            try {
                DataBase::getInstance()->update(self::TABLE, ['id' => $userId], ['password' => $newHash]);
            } catch (DatabaseException $e) {
                // Не удалось обновить — вход всё равно состоялся, миграция повторится позже
                Log::logToFile('Не удалось перехешировать пароль', 'User.log', ['userId' => $userId]);

                return $hash;
            }

            return $newHash;
        }

        /**
         * Отпечаток пароля для проверки согласованности сессии.
         *
         * Хранить в сессии сам хеш пароля не нужно — достаточно производного значения,
         * которое меняется при смене пароля и тем самым инвалидирует старую сессию.
         *
         * @param string $storedHash Хранимый хеш пароля
         *
         * @return string Отпечаток
         */
        public static function sessionFingerprint(string $storedHash): string
        {
            return hash('sha256', self::$cryptoSalt . $storedHash);
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
        private static function authCookieToken(int $id, string $login, string $storedHash): string
        {
            return hash_hmac('sha256', $id . '|' . $login . '|' . $storedHash, self::$cryptoSalt);
        }


        /**
         * Выполняет регистрацию пользователя в системе
         *
         * @param string $login    Логин
         * @param string $password Пароль
         * @param string $email    E-Mail
         * @param string $name     Имя
         *
         * @return int
         * @throws CoreException
         */
        public static function create(string $login, string $password, string $email, string $name = ''): int
        {
            $login = Sanitize::sanitizeString($login);
            $email = Sanitize::sanitizeEmail($email);
            $name  = Sanitize::sanitizeString($name);

            if (self::isUserExists($login)) {
                throw new CoreException('Пользователь с данным логином уже существует', CoreException::ERROR_CREATE_USER);
            }

            if (self::isUserExistsByParams(['email' => $email])) {
                throw new CoreException('Пользователь с данным E-Mail уже существует', CoreException::ERROR_CREATE_USER);
            }

            Log::logToFile('Создание нового пользователя', 'User.log', func_get_args());
            $verificationCode = bin2hex(random_bytes(16));

            /** @var $DB DataBase Объект базы данных */
            $DB         = DataBase::getInstance();
            $objectUser = null;

            /**
             * Вставка пользователя и назначение роли — одна неделимая операция.
             * Без транзакции падение на втором шаге оставляло в базе пользователя без роли,
             * и откатить уже вставленную строку было некому.
             */
            $DB->startTransaction();

            try {
                $userId = $DB->add(self::TABLE, [
                    'login'             => $login,
                    'password'          => self::passwordEncryption($password),
                    'name'              => $name,
                    'image_id'          => 0,
                    'token'             => null,
                    'email'             => $email,
                    'email_confirmed'   => CODE_VALUE_N,
                    'verification_code' => $verificationCode,
                    'last_active'       => time(),
                ]);

                $objectUser = new self($userId);
                $objectUser->getRolesObject()->addRole(Roles::USER_ROLE_ID);

                $DB->commitTransaction();
            } catch (Throwable $e) {
                $DB->rollbackTransaction();
                Log::logToFile('Ошибка создания пользователя, транзакция откачена', 'User.log', [
                    'login' => $login,
                    'error' => $e->getMessage(),
                ]);

                throw new CoreException('Ошибка создания пользователя', CoreException::ERROR_CREATE_USER);
            }

            try {
                $objectUser->sendVerificationCode();
            } catch (CoreException $e) {
                Log::logToFile('Ошибка отправки кода верификации пользователю', 'User.log', func_get_args());
                throw new CoreException('Ошибка отправки кода верификации пользователю', CoreException::ERROR_SEND_VERIFICATION_CODE);
            }
            Log::logToFile('Пользователь успешно создан', 'User.log', ['userId' => $userId]);

            $objectUser->getMailObject()->setSubject('Регистрация на сайте')->setBody(
                    '<b>Логин:</b> ' . $objectUser->getLogin() . PHP_EOL . '<b>Пароль:</b> ' . $password
                )->setTemplateVars(['TITLE' => 'Создана учетная запись'])->send();

            return $userId;
        }

        /**
         * Обновление данных пользователя
         *
         * @param array $fields Массив полей для обновления
         *
         * @return bool
         * @throws CoreException
         */
        public function update(array $fields): bool
        {
            foreach ($fields as $key => $value) {
                $fields[$key] = Sanitize::sanitizeString($value);
            }
            $beforeData = [];
            foreach($fields as $key => $value) {
                $beforeData[$key] = $this->getAllUserData()[$key];
            }
            /** @var $DB DataBase Объект базы данных */
            $DB = DataBase::getInstance();
            Log::logToFile(
                'Данные пользователя изменены', 'User.log', ['userId' => $this->id, 'before' => $beforeData, 'after' => $fields]
            );
            /**
             * Данные пользователя кэшируются под двумя ключами — с флагом onlySecureData и без него.
             * Прежний код удалял ключ без этого флага, то есть не совпадающий ни с одним реально
             * используемым, и кэш никогда не инвалидировался. Пока USE_CACHE выключен, это было
             * незаметно, но при включении отдавало бы устаревшие пароль, email и роли.
             */
            foreach ([0, 1] as $onlySecureDataCacheKey) {
                Cache::delete(md5('User_getAllUserData_' . $onlySecureDataCacheKey . '_' . $this->id));
            }

            return $DB->update(self::TABLE, ['id' => $this->id], $fields);
        }

        /**
         * Проверяет логин на существование
         *
         * @param string $login
         *
         * @return bool
         */
        public static function isUserExists(string $login): bool
        {
            $cacheId = md5('isUserExists_' . $login);
            if (Cache::check($cacheId)) {
                $result = Cache::get($cacheId);
            } else {
                /** @var $DB DataBase Объект базы данных */
                $DB     = DataBase::getInstance();
                $result = $DB->get(self::TABLE, ['login' => $login]);
                Cache::set($cacheId, $result);
            }

            if ($result) {
                return true;
            }

            return false;
        }

        /**
         * Получение количества пользователей
         *
         * @return int
         */
        public static function getAllCount(): int
        {
            $cacheId = md5('User::getAllCount');
            if (Cache::check($cacheId) && Cache::getAge($cacheId) < 300) {
                $result = Cache::get($cacheId);
            } else {
                /** @var $DB DataBase Объект базы данных */
                $DB     = DataBase::getInstance();
                $result = $DB->getList(self::TABLE, ['id' => '>0']);
                Cache::set($cacheId, $result);
            }

            if ($result) {
                return count($result);
            }

            return 0;
        }

        /**
         * Выполняет авторизацию пользователя в системе по ID
         *
         * @param int|null $id       Идентификатор пользователя
         * @param bool     $remember Запомнить
         *
         * @return bool
         * @throws CoreException
         */
        public static function authorize(?int $id = null, bool $remember = false): bool
        {
            if ($id === null) {
                throw new CoreException('Передан некорректный идентификатор пользователя');
            }
            self::logout();
            /** @var $DB DataBase Объект базы данных */
            $DB     = DataBase::getInstance();
            $result = $DB->get(self::TABLE, ['id' => $id], true);

            if ($result) {
                $_SESSION['id']        = $result['id'];
                $_SESSION['authorize'] = CODE_VALUE_Y;
                $_SESSION['login']     = $result['login'];
                $_SESSION['password']  = self::sessionFingerprint((string)$result['password']);
                $_SESSION['token']     = $result['token'];
                $_SESSION['user']      = $result;
                if ($remember) {
                    self::setAuthCookie('userId', (string)$result['id']);
                    self::setAuthCookie('userLogin', (string)$result['login']);
                    self::setAuthCookie('token', self::authCookieToken((int)$result['id'], (string)$result['login'], (string)$result['password']));
                }
                return true;
            }

            return false;
        }

        /**
         * Выполняет авторизацию пользователя в системе по логину и паролю
         *
         * @param string $login    Логин
         * @param string $password Пароль
         * @param bool   $remember Запомнить
         *
         * @return bool
         */
        public static function securityAuthorize(string $login, string $password, bool $remember = false): bool
        {
            /** @var $DB DataBase Объект базы данных */
            $DB     = DataBase::getInstance();

            // Искать по хешу пароля больше нельзя: у bcrypt своя соль на каждую запись,
            // поэтому пользователь ищется по логину, а пароль проверяется отдельно
            $result = $DB->get(self::TABLE, ['login' => $login], true);

            if ($result && self::verifyPassword($password, (string)$result['password'])) {
                $result['password'] = self::rehashIfNeeded(
                    (int)$result['id'],
                    $password,
                    (string)$result['password'],
                );
                $_SESSION['id']        = $result['id'];
                $_SESSION['authorize'] = 'Y';
                $_SESSION['login']     = $result['login'];
                $_SESSION['password']  = self::sessionFingerprint((string)$result['password']);
                $_SESSION['token']     = $result['token'];
                $_SESSION['user']      = $result;
                if ($remember) {
                    self::setAuthCookie('userId', (string)$result['id']);
                    self::setAuthCookie('userLogin', (string)$result['login']);
                    self::setAuthCookie('token', self::authCookieToken((int)$result['id'], (string)$result['login'], (string)$result['password']));
                }
                return true;
            }

            return false;
        }

        /**
         * Получение идентификатора текущего пользователя
         *
         * @return int|null
         * @throws CoreException
         */
        public static function getCurrentUserId(): ?int
        {
            if (self::isAuthorized()) {
                return $_SESSION['id'];
            }

            return null;
        }

        /**
         * Проверка на пользователя
         *
         * @return bool
         * @throws CoreException
         */
        public static function isAuthorized(): bool
        {
            /** @var $DB DataBase Объект базы данных */
            $DB = DataBase::getInstance();

            if (!empty($_COOKIE['userId'])
                && !empty($_COOKIE['userLogin'])
                && self::isUserExists($_COOKIE['userLogin'])) {
                $arUser = $DB->get(self::TABLE, ['id' => $_COOKIE['userId']]);
                if (hash_equals(self::authCookieToken((int)$arUser['id'], (string)$arUser['login'], (string)$arUser['password']), (string)$_COOKIE['token'])) {
                    if (empty($_SESSION['authorize'])) {
                        self::authorize($arUser['id']);
                    }
                    $DB->update(self::TABLE, ['id' => $arUser['id']], ['last_active' => time()]);
                    return true;
                }
            }
            if (empty($_SESSION['authorize']) || $_SESSION['authorize'] !== 'Y') {
                return false;
            }
            $result = $DB->get(self::TABLE, ['login' => $_SESSION['login']]);
            if ($result) {
                if (hash_equals(self::sessionFingerprint((string)$result['password']), (string)$_SESSION['password'])) {
                    $DB->update(self::TABLE, ['id' => $result['id']], ['last_active' => time()]);
                    $_SESSION['id'] = $result['id'];
                    return true;
                }
                return false;
            }
            return false;
        }

        /**
         * Проверка на админа
         *
         * @return bool
         * @throws CoreException
         */
        public function isAdmin(): bool
        {
            return in_array(Roles::ADMIN_ROLE_ID, $this->getRolesObject()->getRoles(), true);
        }

        /**
         * Проверка на менеджера
         *
         * @return bool
         * @throws CoreException
         */
        public function isManager(): bool
        {
            return in_array(Roles::MANAGER_ROLE_ID, $this->getRolesObject()->getRoles(), true);
        }

        /**
         * Проверка на доступ в админку
         *
         * @return bool
         * @throws CoreException
         */
        public function haveAccessToAdminPanel(): bool
        {
            return in_array(Roles::ADMIN_PANEL_ROLE_ID, $this->getRolesObject()->getRoles(), true);
        }

        /**
         * Установить cookie авторизации с флагами безопасности.
         *
         * HttpOnly закрывает cookie от чтения из JavaScript, что ограничивает ущерб от любой XSS.
         * SameSite=Lax не даёт браузеру отправлять её при межсайтовых POST-запросах — базовая
         * защита от CSRF в дополнение к токену. Secure выставляется только на HTTPS, иначе
         * cookie не установится на локальном HTTP-окружении.
         *
         * @param string $name  Имя cookie
         * @param string $value Значение
         *
         * @return void
         */
        private static function setAuthCookie(string $name, string $value): void
        {
            setcookie($name, $value, [
                'expires'  => time() + 3600 * 24,
                'path'     => '/',
                'httponly' => true,
                'secure'   => (($_SERVER['HTTPS'] ?? '') !== '') && $_SERVER['HTTPS'] !== 'off',
                'samesite' => 'Lax',
            ]);
        }

        /**
         * Удалить cookie авторизации
         *
         * @param string $name Имя cookie
         *
         * @return void
         */
        private static function forgetAuthCookie(string $name): void
        {
            setcookie($name, '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'httponly' => true,
                'secure'   => (($_SERVER['HTTPS'] ?? '') !== '') && $_SERVER['HTTPS'] !== 'off',
                'samesite' => 'Lax',
            ]);
        }

        /**
         * Метод выхода из системы
         */
        public static function logout(): void
        {
            unset($_SESSION['id']);
            unset($_SESSION['authorize']);
            unset($_SESSION['login']);
            unset($_SESSION['password']);
            unset($_SESSION['token']);
            unset($_SESSION['user']);

            self::forgetAuthCookie('userId');
            self::forgetAuthCookie('userLogin');
            self::forgetAuthCookie('token');
        }

        /**
         * Получить объект для работы с ролями
         *
         * @return Roles
         */
        public function getRolesObject(): Roles
        {
            if (empty($this->rolesObject)) {
                $this->rolesObject = (new Roles($this));
            }
            return $this->rolesObject;
        }

        /**
         * Получить объект для работы с ролями
         *
         * @return UserMeta
         */
        public function getMetaObject(): UserMeta
        {
            if (empty($this->metaObject)) {
                $this->metaObject = (new UserMeta($this));
            }
            return $this->metaObject;
        }

        /**
         * Получить объект для работы с почтой
         *
         * @return Mail
         */
        public function getMailObject(): Mail
        {
            if (empty($this->mailObject)) {
                $this->mailObject = (new Mail($this));
            }
            return $this->mailObject;
        }

        /**
         * Получить объект для работы с диалогами
         *
         * @return Dialog
         */
        public function getDialogObject(): Dialog
        {
            if (empty($this->dialogObject)) {
                $this->dialogObject = (new Dialog($this));
            }
            return $this->dialogObject;
        }

        /**
         * Экспорт всех данных из таблицы пользователей
         *
         * @return string XML данные
         * @throws CoreException
         */
        public static function exportUsers(): string
        {
            /** @var $DB DataBase Объект базы данных */
            $DB  = DataBase::getInstance();
            $res = $DB->query('SELECT * FROM ' . self::TABLE);
            foreach ($res as $key => $element) {
                $res[$key]['roles'] = (new self($element['id']))->getRolesObject()->getRoles();
            }
            return SystemFunctions::arrayToXml($res, self::TABLE);
        }

        /**
         * Выборка пользователей по роли
         *
         * @param int $roleId Идентификатор роли
         *
         * @return self[]
         * @throws CoreException
         */
        public static function getListByRole(int $roleId): array
        {
            $result = [];
            /** @var $DB DataBase Объект базы данных */
            $DB  = DataBase::getInstance();
            $res = $DB->getList(Roles::USER_ROLES_TABLE, ['id' => $roleId]);
            foreach ($res as $element) {
                $result[] = new self((int)$element['user_id']);
            }
            return $result;
        }

        /**
         * Проверка электронной почты на подтвержденность
         *
         * @return bool
         * @throws CoreException
         */
        public function isEmailConfirmed(): bool
        {
            return $this->getAllUserData()['email_confirmed'] === CODE_VALUE_Y;
        }

        /**
         * Верификация E-Mail
         *
         * @throws CoreException
         */
        public static function verification(string $verificationCode): bool
        {
            $verificationCode = Sanitize::sanitizeString($verificationCode);

            /** @var $DB DataBase Объект базы данных */
            $DB  = DataBase::getInstance();
            $res = $DB->get(self::TABLE, ['verification_code' => $verificationCode]);
            if ($res) {
                if ($res['email_confirmed'] === CODE_VALUE_Y) {
                    Log::logToFile('E-Mail уже верифицирован', 'User.log', ['userId' => $res['id'], 'code' => $verificationCode]);
                    return false;
                }
                (new self($res['id']))->update(['email_confirmed' => CODE_VALUE_Y]);
                Log::logToFile('E-Mail успешно верифицирован', 'User.log', ['userId' => $res['id'], 'code' => $verificationCode]);
                return true;
            }
            Log::logToFile('Ошибка верификации E-Mail', 'User.log', ['code' => $verificationCode]);
            return false;
        }

        /**
         * Отправка кода верификации пользователю на почту
         *
         * @return bool Результат отправки
         * @throws CoreException
         */
        public function sendVerificationCode(): bool
        {
            if (empty($this->getAllUserData()['verification_code'])) {
                Log::logToFile('Ошибка отправки кода верификации: код отсутствует', 'User.log', ['userId' => $this->id]);
                throw new CoreException('Ошибка отправки кода верификации: код отсутствует');
            }
            Log::logToFile('Выслан код верификаци', 'User.log', ['userId' => $this->id, 'code' => $this->getAllUserData()['verification_code']]);
            return (bool)$this->getMailObject()->setSubject('Подтверждение E-Mail')->setBody(
                    'Код верификации: <b>' . $this->getVerificationCode() . '</b>' . PHP_EOL . 'Для подтверждения E-Mail перейдите по <a href="'
                    . SITE_URL_CORE . '/verification.php?code=' . $this->getVerificationCode() . '">ссылке</a>'
                )->setTemplateVars(['TITLE' => 'Подтверждение E-Mail'])->send()[0];
        }

        /**
         * Смена пароля пользователем (с проверкой текущего пароля)
         *
         * @param string $currentPassword Текущий пароль
         * @param string $newPassword     Новый пароль
         *
         * @return bool
         * @throws CoreException
         */
        public function changePassword(string $currentPassword, string $newPassword): bool
        {
            $currentData = $this->getAllUserData();
            if (self::verifyPassword($currentPassword, (string)$currentData['password']) === false) {
                return false;
            }

            $newHash = self::passwordEncryption($newPassword);
            $this->update(['password' => $newHash]);
            Log::logToFile('Пользователь сменил пароль', 'User.log', ['userId' => $this->id]);

            // Обновляем отпечаток пароля, сохранённый в сессии при авторизации, иначе isAuthorized()
            // разлогинит пользователя на следующем же запросе, так как пароль в БД уже другой
            if (isset($_SESSION['id']) && (int)$_SESSION['id'] === $this->id) {
                $_SESSION['password'] = self::sessionFingerprint($newHash);
            }

            return true;
        }

        /**
         * Смена E-Mail пользователем (с обязательной повторной верификацией)
         *
         * @param string $newEmail Новый E-Mail
         *
         * @return bool
         * @throws CoreException
         */
        public function changeEmail(string $newEmail): bool
        {
            $newEmail = Sanitize::sanitizeEmail($newEmail);
            if ($newEmail === $this->getEmail()) {
                return true;
            }
            if (self::isUserExistsByParams(['email' => $newEmail])) {
                throw new CoreException('Пользователь с данным E-Mail уже существует', CoreException::ERROR_CREATE_USER);
            }

            $verificationCode = bin2hex(random_bytes(16));
            $this->update([
                'email'             => $newEmail,
                'email_confirmed'   => CODE_VALUE_N,
                'verification_code' => $verificationCode,
            ]);
            Log::logToFile('Пользователь сменил E-Mail', 'User.log', ['userId' => $this->id, 'newEmail' => $newEmail]);
            $this->sendVerificationCode();
            return true;
        }

        public function resetPassword(): bool
        {
            $newPassword = SystemFunctions::generatePassword();

            try {
                $this->update(['password' => self::passwordEncryption($newPassword)]);
            } catch (CoreException $e) {
                return false;
            }

            Log::logToFile('Произведен сброс пароля', 'User.log', ['userId' => $this->id, 'password' => $newPassword]);

            $this->getMailObject()->setSubject('Произведен сброс пароля')->setBody(
                    '<b>Логин:</b> ' . $this->getLogin() . PHP_EOL . '<b>Пароль:</b> ' . $newPassword
                )->setTemplateVars(['TITLE' => 'Ваши учетные данные'])->send();
            return true;
        }

        /**
         * Получение количества пользователей
         *
         * @param bool $onlyConfirmed Только с подтвержденным E-Mail
         *
         * @return int Количество
         */
        public static function getUsersCount($onlyConfirmed = false): int
        {
            /** @var $DB DataBase Объект базы данных */
            $DB  = DataBase::getInstance();
            $sql    = 'SELECT COUNT(*) as count FROM ' . self::TABLE;
            $params = [];
            if ($onlyConfirmed) {
                $sql .= ' WHERE email_confirmed=:emailConfirmed';
                $params['emailConfirmed'] = CODE_VALUE_Y;
            }
            $res = $DB->query($sql, $params);
            return (int)$res[0]['count'];
        }

        /**
         * Получение диалогов
         *
         * @return array
         * @throws CoreException
         */
        public function getDialogs(): array
        {
            $dialogs = $this->getDialogObject()->getDialogs();
            foreach ($dialogs as $key => $dialog) {
                $dialogs[$key]['companionId'] = $this->getDialogObject()->getDialogCompanionId((int)$dialog['id']);
                $dialogs[$key]['companionData'] = (new self((int)$dialogs[$key]['companionId']))->getAllUserData(true);
                $dialogs[$key]['unviewedCount'] = $this->getDialogObject()->getDialogUnviewedMessagesCount((int)$dialog['id']);
            }

            return $dialogs;
        }

        /**
         * Получение диалогов
         *
         * @param int  $dialogId   Идентификатор диалога
         * @param bool $markViewed Пометить прочитанным
         *
         * @return array
         * @throws CoreException
         */
        public function getMessages(int $dialogId, bool $markViewed = false): array
        {
            $messages = $this->getDialogObject()->getMessages($dialogId, $markViewed);
            foreach ($messages as $key => $message) {
                $messages[$key]['user_from_data'] = (new self((int)$message['user_from']))->getAllUserData(true);
                $messages[$key]['user_to_data'] = (new self((int)$message['user_to']))->getAllUserData(true);
                if ($message['type'] !== Dialog::MESSAGE_TYPE_TEXT) {
                    $messages[$key]['file'] = (new File((int)$message['text']))->getAllProps();
                    if ($messages[$key]['file'] === null) {
                        $messages[$key]['file']['error'] = true;
                    } else {
                        $messages[$key]['file']['error'] = false;
                    }
                }
            }

            return $messages;
        }
    }