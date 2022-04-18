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
         * Получение всех пользователей панели
         *
         * @param string $limit
         * @param string $sort
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
         * Возвращает токен пользователя
         */
        public static function getUserToken(int $userId): string
        {
            $result = (DB::getInstance())->getItem(self::TABLE, ['id' => $userId]);

            if ($result) {
                return $result['token'];
            } else {
                return '';
            }
        }

        /**
         * Создание пользовательского токена
         */
        public static function createUserToken($userId): string
        {
            $newToken = self::generateGUID();
            (DB::getInstance())->update(self::TABLE, ['id' => $userId], ['token' => $newToken]);

            return $newToken;
        }

        /**
         * Генерация GUID
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
         * @param string $token
         *
         * @return bool
         */
        public static function isTokenExists($token): bool
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
         */
        public static function getUserByToken($token)
        {
            $result = (DB::getInstance())->getItem(self::TABLE, ['token' => $token]);

            if ($result) {
                return $result;
            } else {
                return false;
            }
        }

        /**
         * Проверка пользователя на онлайн
         *
         * @param $int $id
         */
        public static function isOnline($id = false)
        {
            if (!$id) {
                return false;
            }
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
         * @param string $login
         * @param string $password
         * @param string $level
         * @param string $name
         * @param string $image
         */
        public static function registration($login, $password, $email, $level = 'user', $name = '', $image = ''): int
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
            self::authorize($userId);
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
         * @param int $id
         *
         * @return bool
         * @throws CoreException
         */
        public static function authorize(?int $id = null, $remember = false): bool
        {
            if (empty($id)) {
                throw new CoreException('Передан некорректный идентификатор пользователя');
            }
            self::logout();

            $result = (DB::getInstance())->getItem(self::TABLE, ['id' => $id], true);

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
         * Выполняет авторизацию пользователя в системе по логину и паролю
         *
         * @param string $login
         * @param string $password
         *
         * @return bool
         */
        public static function securityAuthorize($login, $password, $remember = false): bool
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
            if ($this->getAllUserData()['access_level'] == self::ACCESS_ADMIN) {
                return true;
            } else {
                return false;
            }
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