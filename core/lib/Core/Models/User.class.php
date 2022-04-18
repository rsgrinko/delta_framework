<?php
    /**
     * Класс для работы с пользователями
     *
     * @author Roman Grinko <rsgrinko@gmail.com>
     */

    namespace Core\Models;

    use Core\CoreException;
    use Core\Models\DB;
    use Core\Helpers\Cache;

    class User
    {

        /**
         * ID текущего пользователя
         *
         * @var int
         */
        public $id;

        /**
         * Таблица с пользователями
         *
         * @var string $table
         */
        const TABLE = 'd_users';

        /**
         * Уровень доступа - админ
         */
        const ACCESS_ADMIN = 'admin';

        /**
         * Уровень доступа - пользователь
         */
        const ACCESS_USER = 'user';

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
                throw new CoreException('Передан некорректный идентификатор пользователя');
            }

            $this->id = $id;
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
            return self::getAllUserData() ? self::getAllUserData()['login'] : null;
        }

        /**
         * Получение E-Mail'а текущего пользователя
         *
         * @return string|null
         */
        public function getEmail(): ?string
        {
            return self::getAllUserData() ? self::getAllUserData()['email'] : null;
        }

        /**
         * Получение имени текущего пользователя
         *
         * @return string|null
         */
        public function getName(): ?string
        {
            return self::getAllUserData() ? self::getAllUserData()['name'] : null;
        }

        /**
         * Получение аватарки текущего пользователя
         *
         * @return string|null
         */
        public function getImage(): ?string
        {
            return self::getAllUserData() ? self::getAllUserData()['image'] : null;
        }

        /**
         * Получение токена текущего пользователя
         *
         * @return string|null
         */
        public function getToken(): ?string
        {
            return self::getAllUserData() ? self::getAllUserData()['token'] : null;
        }

        /**
         * Получение имени текущего пользователя
         *
         * @return string|null
         */
        public function getLastActive(): ?string
        {
            return self::getAllUserData() ? self::getAllUserData()['last_active'] : null;
        }

        /**
         * Получение всех пользователей панели
         *
         * @param string $limit Лимит
         * @param string $sort  Сортировка
         *
         * @return array
         */
        public static function getUsers(string $limit = '10', $sort = 'ASC')
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
         */
        public function createToken(): string
        {
            $newToken = self::generateGUID();
            (DB::getInstance())->update(self::TABLE, ['id' => $this->id], ['token' => $newToken]);

            return $newToken;
        }

        /**
         * Генерация GUID
         *
         * @return string
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
         * Выполняет регистрацию пользователя в системе
         *
         * @param string $login    Логин
         * @param string $password Пароль
         * @param string $level    Права
         * @param string $name     Имя
         * @param string $image    Аватар
         *
         * @throws CoreException
         */
        public static function registration(string $login, string $password, string $email, string $level = 'user', string $name = '', string $image = ''): int
        {
            $userId = (DB::getInstance())->addItem(self::TABLE, [
                'login'        => $login,
                'password'     => md5(self::$cryptoSalt . $password),
                'access_level' => $level,
                'name'         => $name,
                'image'        => $image,
                'token'        => '',
                'email'        => $email,
                'last_active'  => time(),
            ]);
            //self::authorize($userId);
            return $userId;
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
            $result = (DB::getInstance())->getItem(self::TABLE, ['login' => $login]);

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
            $result = (DB::getInstance())->getItems(self::TABLE, ['id' => '>0']);

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

            $result = (DB::getInstance())->getItem(self::TABLE, ['id' => $id], true);

            if ($result) {
                $_SESSION['id']        = $result['id'];
                $_SESSION['authorize'] = CODE_VALUE_Y;
                $_SESSION['login']     = $result['login'];
                $_SESSION['password']  = md5(self::$cryptoSalt . $result['password']);
                $_SESSION['token']     = $result['token'];
                $_SESSION['user']      = $result;
                if ($remember) {
                    setcookie('userId', $result['id'], time() + 3600 * 24);
                    setcookie('userLogin', $result['login'], time() + 3600 * 24);
                    setcookie('token', md5(self::$cryptoSalt . $result['id'] . $result['login'] . $result['password']), time() + 3600 * 24);
                }
                return true;
            } else {
                return false;
            }
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
            $result = (DB::getInstance())->getItem(self::TABLE, ['login' => $login, 'password' => md5(self::$cryptoSalt . $password)], true);
            if ($result) {
                $_SESSION['id']        = $result['id'];
                $_SESSION['authorize'] = 'Y';
                $_SESSION['login']     = $result['login'];
                $_SESSION['password']  = md5(self::$cryptoSalt . $result['password']);
                $_SESSION['token']     = $result['token'];
                $_SESSION['user']      = $result;
                if ($remember) {
                    setcookie('userId', $result['id'], time() + 3600 * 24);
                    setcookie('userLogin', $result['login'], time() + 3600 * 24);
                    setcookie('token', md5(self::$cryptoSalt . $result['id'] . $result['login'] . $result['password']), time() + 3600 * 24);
                }
                return true;
            } else {
                return false;
            }
        }

        /**
         * Получение идентификатора текущего пользователя
         *
         * @return int|null
         */
        public static function getUserId(): ?int
        {
            if (self::isUser()) {
                return $_SESSION['id'];
            } else {
                return null;
            }
        }

        /**
         * Проверка на пользователя
         *
         * @return bool
         */
        public static function isUser(): bool
        {
            if (!empty($_COOKIE['userId'])
                && !empty($_COOKIE['userLogin'])
                && self::isUserExists($_COOKIE['userLogin'])) {
                $arUser = (DB::getInstance())->getItem(self::TABLE, ['id' => $_COOKIE['userId']]);
                if ($_COOKIE['token'] == md5(self::$cryptoSalt . $arUser['id'] . $arUser['login'] . $arUser['password'])) {
                    if (empty($_SESSION['authorize'])) {
                        self::authorize($arUser['id']);
                    }
                    return true;
                }
            }
            if (!isset($_SESSION['authorize']) or empty($_SESSION['authorize']) or $_SESSION['authorize'] !== 'Y') {
                return false;
            }
            $result = (DB::getInstance())->getItem(self::TABLE, ['login' => $_SESSION['login']]);
            if ($result) {
                if (md5(self::$cryptoSalt . $result['password']) == $_SESSION['password']) {
                    (DB::getInstance())->update(self::TABLE, ['id' => $result['id']], ['last_active' => time()]);
                    $_SESSION['id'] = $result['id'];
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

        /**
         * Проверка на админа
         *
         * @return bool
         */
        public function isAdmin(): bool
        {
            if(empty($this->getAllUserData())) {
                return false;
            }
            return $this->getAllUserData()['access_level'] == self::ACCESS_ADMIN;
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


    }