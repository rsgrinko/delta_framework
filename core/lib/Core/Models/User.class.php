<?php
    /**
     * Класс для работы с пользователями
     *
     * @author Roman Grinko <rsgrinko@gmail.com>
     */
use Core\Models\DB;

    class User
    {

        /**
         * ID текущего пользователя
         *
         * @var int
         */
        public static $id;

        /**
         * Таблица с пользователями
         *
         * @var string $table
         */
        public static $table;

        /**
         * @var string $cryptoSalt Соль для шифрования
         */
        private static $cryptoSalt = 'BKH92FdiQvEW2aOy0giywXasYAMl0pFvIrlop8Sz';

        /**
         * Инициализация класса
         *
         * @param string $table
         */
        public static function init( string $table = TABLE_PREFIX . 'users'): void
        {
            self::$table = $table;
            if (isset($_SESSION['authorize']) and $_SESSION['authorize'] == 'Y') {
                self::$id = $_SESSION['id'];
            }
        }

        /**
         * Получение всех полей пользователя
         *
         * @param int $id
         *
         * @return array|bool
         */
        public static function getFields($id = false)
        {
            if (empty($id) or $id === false) {
                $id = self::$id;
            }

            if (is_array($id)) {
                $where = $id;
            } else {
                $where = ['id' => $id];
            }
            $result = (DB::getInstance())->getItem(self::$table, $where);
            if ($result) {
                return $result;
            } else {
                return false;
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
            $res = (DB::getInstance())->query('SELECT * FROM `' . self::$table . '` ORDER BY `id` ' . $sort . ' LIMIT ' . $limit);
            return $res;
        }

        /**
         * Возвращает токен пользователя
         */
        public static function getUserToken(int $userId): string
        {
            $result = (DB::getInstance())->getItem(self::$table, ['id' => $userId]);

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
            (DB::getInstance())->update(self::$table, ['id' => $userId], ['token' => $newToken]);

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
            $result = (DB::getInstance())->getItem(self::$table, ['token' => $token]);

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
            $result = (DB::getInstance())->getItem(self::$table, ['token' => $token]);

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
            $res         = (DB::getInstance())->query('SELECT last_active FROM `' . self::$table . '` WHERE id=' . $id);
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
        public static function registration($login, $password, $email, $level = 'user', $name = '', $image = ''): void
        {
            (DB::getInstance())->addItem(self::$table,
                               [
                                   'login'        => $login,
                                   'password'     => md5(self::$cryptoSalt . $password),
                                   'access_level' => $level,
                                   'name'         => $name,
                                   'image'        => $image,
                                   'token'        => '',
                                   'email'        => $email,
                                   'last_active'  => time(),
                               ]
            );
            $result = (DB::getInstance())->getItem(self::$table, ['login' => $login, 'password' => $password]);

            self::$id              = $result['id'];
            $_SESSION['id']        = $result['id'];
            $_SESSION['authorize'] = 'Y';
            $_SESSION['login']     = $result['login'];
            $_SESSION['password']  = $result['password'];
            $_SESSION['token']     = $result['token'];
            $_SESSION['user']      = $result;
            return;
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
            $result = (DB::getInstance())->getItem(self::$table, ['login' => $login]);

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
            $result = (DB::getInstance())->getItems(self::$table, ['id' => '>0']);

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
         */
        public static function authorize($id, $remember = false): bool
        {
            $result = (DB::getInstance())->getItem(self::$table, ['id' => $id], true);

            if ($result) {
                self::$id              = $result['id'];
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
            $result = (DB::getInstance())->getItem(self::$table, ['login' => $login, 'password' => md5(self::$cryptoSalt . $password)], true);
            if ($result) {
                self::$id              = $result['id'];
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
         * Проверка на пользователя
         *
         * @return bool
         */
        public static function isUser(): bool
        {
            if (!empty($_COOKIE['userId'])
                && !empty($_COOKIE['userLogin'])
                && self::isUserExists($_COOKIE['userLogin'])) {
                $arUser = (DB::getInstance())->getItem(self::$table, ['id' => $_COOKIE['userId']]);
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
            $result = (DB::getInstance())->getItem(self::$table, ['login' => $_SESSION['login']]);
            if ($result) {
                if (md5(self::$cryptoSalt . $result['password']) == $_SESSION['password']) {
                    (DB::getInstance())->update(self::$table, ['id' => $result['id']], ['last_active' => time()]);
                    self::$id       = $result['id'];
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
        public static function isAdmin(): bool
        {
            if (self::isUser()) {
                if (self::getFields(self::$id)['access_level'] == 'admin') {
                    return true;
                } else {
                    return false;
                }
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