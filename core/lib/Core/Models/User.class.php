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
    use Core\Models\{DB, Roles, UserMeta};
    use Core\Helpers\{Cache, Log, Mail, SystemFunctions, Sanitize};

    class User
    {

        /**
         * ID текущего пользователя
         *
         * @var int
         */
        public $id;

        /**
         * Объект групп
         *
         * @var Roles
         */
        public $rolesObject = null;

        /**
         * Объект Mail
         *
         * @var Roles
         */
        public $mailObject = null;

        /**
         * Объект мета данных
         *
         * @var Roles
         */
        public $metaObject = null;

        /**
         * Таблица с пользователями
         */
        const TABLE = 'users';

        /**
         * @var string $cryptoSalt Соль для шифрования
         */
        private static $cryptoSalt = 'BKH92FdiQvEW2aOy0giywXasYAMl0pFvIrlop8Sz';

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
         * @return array|null
         */
        public function getAllUserData(): ?array
        {
            $cacheId = md5('User_getAllUserData_' . $this->id);
            if (Cache::check($cacheId)) {
                $result = Cache::get($cacheId);
            } else {
                $result = (DB::getInstance())->getItem(self::TABLE, ['id' => $this->id]);
                Cache::set($cacheId, $result);
            }

            if (!empty($result)) {
                return $result;
            } else {
                return null;
            }
        }

        /**
         * Получение логина текущего пользователя
         *
         * @return string|null
         */
        public function getLogin(): ?string
        {
            return $this->getAllUserData() ? $this->getAllUserData()['login'] : null;
        }

        /**
         * Получение E-Mail'а текущего пользователя
         *
         * @return string|null
         */
        public function getEmail(): ?string
        {
            return $this->getAllUserData() ? $this->getAllUserData()['email'] : null;
        }

        /**
         * Получение имени текущего пользователя
         *
         * @return string|null
         */
        public function getName(): ?string
        {
            return $this->getAllUserData() ? $this->getAllUserData()['name'] : null;
        }

        /**
         * Получение токена текущего пользователя
         *
         * @return string|null
         */
        public function getToken(): ?string
        {
            return $this->getAllUserData() ? $this->getAllUserData()['token'] : null;
        }

        /**
         * Получение времени ппоследней активности
         *
         * @return string|null
         */
        public function getLastActive(): ?string
        {
            return $this->getAllUserData() ? $this->getAllUserData()['last_active'] : null;
        }

        /**
         * Получение идентификатора текущего пользователя
         *
         * @return int|null
         */
        public function getId(): ?int
        {
            return $this->getAllUserData() ? $this->getAllUserData()['id'] : null;
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
            $cacheId = md5('User_getUsers_' . $limit . '_' . $sort);
            if (Cache::check($cacheId)) {
                $res = Cache::get($cacheId);
            } else {
                $res = (DB::getInstance())->query('SELECT * FROM `' . self::TABLE . '` ORDER BY `id` ' . $sort . ' LIMIT ' . $limit);
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
            (DB::getInstance())->update(self::TABLE, ['id' => $this->id], ['token' => $newToken]);
            Log::logToFile('Создан токен ' . $newToken, 'User.log', ['userId' => $this->id]);
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
            $result = (DB::getInstance())->getItem(self::TABLE, ['token' => $token]);
            if ($result) {
                return true;
            } else {
                return false;
            }
        }

        /**
         * Получение пользователя по токену
         *
         * @param string $token Токен
         *
         * @return User|null
         * @throws CoreException
         */
        public static function getUserByToken(string $token): ?self
        {
            $result = (DB::getInstance())->getItem(self::TABLE, ['token' => $token]);
            if ($result) {
                return (new self($result['id']));
            } else {
                return null;
            }
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
            $result = (DB::getInstance())->getItem(self::TABLE, $where);
            if (!empty($result)) {
                return true;
            } else {
                return false;
            }
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
            $res         = (DB::getInstance())->query('SELECT last_active FROM `' . self::TABLE . '` WHERE id=' . $id);
            $last_active = $res[0]['last_active'];
            $timeNow     = time();
            if ($last_active > ($timeNow - USER_ONLINE_TIME)) {
                return true;
            } else {
                return false;
            }
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
            return md5(self::$cryptoSalt . $password);
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
            $email = Sanitize::sanitizeString($email);
            $name  = Sanitize::sanitizeString($name);

            Log::logToFile('Создание нового пользователя', 'User.log', func_get_args());
            $verificationCode = md5(self::$cryptoSalt . $email . $login . time());

            /** @var  $DB DB */
            $DB     = DB::getInstance();
            $userId = $DB->addItem(self::TABLE, [
                'login'             => $login,
                'password'          => self::passwordEncryption($password),
                'name'              => $name,
                'image_id'          => 0,
                'token'             => $verificationCode,
                'email'             => $email,
                'email_confirmed'   => CODE_VALUE_N,
                'verification_code' => md5(self::$cryptoSalt . $email . $login . time()),
                'last_active'       => time(),
            ]);
            $objectUser = null;

            try {
                $objectUser = (new self($userId));
            } catch (CoreException $e) {
                Log::logToFile('Ошибка создания объекта пользователя', 'User.log', func_get_args());
                throw new CoreException('Ошибка создания объекта пользователя', CoreException::ERROR_CREATE_USER);
            }

            try {
                $objectUser->getRolesObject()->addRole(Roles::USER_ROLE_ID);
            } catch (CoreException $e) {
                Log::logToFile('Ошибка добавления ролей пользователю', 'User.log', func_get_args());
                throw new CoreException('Ошибка добавления ролей пользователю', CoreException::ERROR_ADD_USER_ROLES);
            }

            try {
                $objectUser->sendVerificationCode();
            } catch (CoreException $e) {
                Log::logToFile('Ошибка отправки кода верификации пользователю', 'User.log', func_get_args());
                throw new CoreException('Ошибка отправки кода верификации пользователю', CoreException::ERROR_SEND_VERIFICATION_CODE);
            }

            return $userId;
        }

        public function update(array $fields): bool
        {
            foreach($fields as $key => $value) {
                $fields[$key] = Sanitize::sanitizeString($value);
            }
            $cacheId = md5('User_getAllUserData_' . $this->id);
            /** @var  $DB DB */
            $DB = DB::getInstance();
            Log::logToFile(
                'Изменение данных пользователя c ID ' . $this->id,
                'User.log',
                ['before' => $this->getAllUserData(), 'after' => func_get_args()]
            );
            Cache::delete($cacheId);
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
                /** @var  $DB DB */
                $DB     = DB::getInstance();
                $result = $DB->getItem(self::TABLE, ['login' => $login]);
                Cache::set($cacheId, $result);
            }

            if ($result) {
                return true;
            } else {
                return false;
            }
        }

        /**
         * Считает количество пользователей
         *
         * @return int
         */
        public static function countUsers(): int
        {
            $cacheId = md5('countUsers');
            if (Cache::check($cacheId) && Cache::getAge($cacheId) < 300) {
                $result = Cache::get($cacheId);
            } else {
                /** @var  $DB DB */
                $DB     = DB::getInstance();
                $result = $DB->getItems(self::TABLE, ['id' => '>0']);
                Cache::set($cacheId, $result);
            }

            if ($result) {
                return count($result);
            } else {
                return 0;
            }
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
            if (empty($id)) {
                throw new CoreException('Передан некорректный идентификатор пользователя');
            }
            self::logout();
            /** @var  $DB DB */
            $DB     = DB::getInstance();
            $result = $DB->getItem(self::TABLE, ['id' => $id], true);

            if ($result) {
                $_SESSION['id']        = $result['id'];
                $_SESSION['authorize'] = CODE_VALUE_Y;
                $_SESSION['login']     = $result['login'];
                $_SESSION['password']  = self::passwordEncryption($result['password']);
                $_SESSION['token']     = $result['token'];
                $_SESSION['user']      = $result;
                if ($remember) {
                    setcookie('userId', $result['id'], time() + 3600 * 24);
                    setcookie('userLogin', $result['login'], time() + 3600 * 24);
                    setcookie('token', md5(self::$cryptoSalt . $result['id'] . $result['login'] . $result['password']), time() + 3600 * 24);
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
            /** @var  $DB DB */
            $DB     = DB::getInstance();
            $result = $DB->getItem(self::TABLE, ['login' => $login, 'password' => self::passwordEncryption($password)], true);
            if ($result) {
                $_SESSION['id']        = $result['id'];
                $_SESSION['authorize'] = 'Y';
                $_SESSION['login']     = $result['login'];
                $_SESSION['password']  = self::passwordEncryption($result['password']);
                $_SESSION['token']     = $result['token'];
                $_SESSION['user']      = $result;
                if ($remember) {
                    setcookie('userId', $result['id'], time() + 3600 * 24);
                    setcookie('userLogin', $result['login'], time() + 3600 * 24);
                    setcookie('token', md5(self::$cryptoSalt . $result['id'] . $result['login'] . $result['password']), time() + 3600 * 24);
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
            /** @var  $DB DB */
            $DB = DB::getInstance();

            if (!empty($_COOKIE['userId'])
                && !empty($_COOKIE['userLogin'])
                && self::isUserExists($_COOKIE['userLogin'])) {
                $arUser = $DB->getItem(self::TABLE, ['id' => $_COOKIE['userId']]);
                if ($_COOKIE['token'] == md5(self::$cryptoSalt . $arUser['id'] . $arUser['login'] . $arUser['password'])) {
                    if (empty($_SESSION['authorize'])) {
                        self::authorize($arUser['id']);
                    }
                    return true;
                }
            }
            if (empty($_SESSION['authorize']) || $_SESSION['authorize'] !== 'Y') {
                return false;
            }
            $result = $DB->getItem(self::TABLE, ['login' => $_SESSION['login']]);
            if ($result) {
                if (self::passwordEncryption($result['password']) == $_SESSION['password']) {
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
         */
        public function isAdmin(): bool
        {
            return in_array(Roles::ADMIN_ROLE_ID, $this->getRolesObject()->getRoles(), true);
        }

        /**
         * Метод выхода из системы
         */
        public static function logout(): void
        {
            $_SESSION['id']        = '';
            $_SESSION['authorize'] = '';
            $_SESSION['login']     = '';
            $_SESSION['password']  = '';
            $_SESSION['token']     = '';
            $_SESSION['user']      = '';

            setcookie('userId', '', time() - 3600);
            setcookie('userLogin', '', time() - 3600);
            setcookie('token', '', time() - 3600);
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
         * Экспорт всех данных из таблицы пользователей
         *
         * @return string XML данные
         * @throws CoreException
         */
        public static function exportUsers(): string
        {
            /** @var  $DB DB */
            $DB  = DB::getInstance();
            $res = $DB->query('SELECT * FROM ' . self::TABLE);
            foreach ($res as $key => $element) {
                $res[$key]['roles'] = (new self($element['id']))->getRolesObject()->getRoles();
            }
            return SystemFunctions::arrayToXml($res, self::TABLE);
        }

        /**
         * Проверка электронной почты на подтвержденность
         *
         * @return bool
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

            /** @var  $DB DB */
            $DB  = DB::getInstance();
            $res = $DB->getItem(self::TABLE, ['verification_code' => $verificationCode]);
            if ($res) {
                (new self($res['id']))->update(['email_confirmed' => CODE_VALUE_Y]);
                return true;
            }
            return false;
        }

        /**
         * Отправка кода верификации пользователю на почту
         *
         * @return void
         */
        public function sendVerificationCode(): void
        {
            $this->getMailObject()
                 ->setSubject('Подтверждение E-Mail')
                 ->setBody('Для подтверждения E-Mail перейдите по ссылке: ' . SITE_URL_CORE . '/verification.php?code=' . $this->getAllUserData()['verification_code'])
                 ->send();
        }
    }