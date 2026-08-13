<?php

    declare(strict_types=1);

    namespace Tests\Support;

    use Core\Database\DataBase;
    use PDO;

    /**
     * Управление схемой тестовой базы.
     *
     * Таблицы создаются с префиксом `test_` в той же базе, что и рабочие: прав на создание
     * отдельной базы у учётной записи нет, а отдельный префикс изолирует данные не хуже.
     * Схема повторяет рабочую в той части, которая нужна сценариям входа и профиля.
     */
    final class TestDatabase
    {
        /** @var PDO|null Прямое соединение для управления схемой */
        private static ?PDO $pdo = null;

        /**
         * Признак доступности тестовой базы
         *
         * @return bool Доступность
         */
        public static function isAvailable(): bool
        {
            if (DB_USER === '' || DB_NAME === '') {
                return false;
            }

            try {
                self::pdo();

                return true;
            } catch (\Throwable) {
                return false;
            }
        }

        /**
         * Соединение для управления схемой
         *
         * @return PDO Соединение
         */
        private static function pdo(): PDO
        {
            if (self::$pdo === null) {
                self::$pdo = new PDO(
                    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
                    DB_USER,
                    DB_PASSWORD,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5],
                );
            }

            return self::$pdo;
        }

        /**
         * Создать схему заново
         *
         * @return void
         */
        public static function createSchema(): void
        {
            self::dropSchema();

            $pdo = self::pdo();

            $pdo->exec('
                CREATE TABLE `test_users` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `active` enum("Y","N") NOT NULL DEFAULT "Y",
                    `login` varchar(100) NOT NULL,
                    `password` varchar(100) NOT NULL,
                    `name` varchar(150) NOT NULL,
                    `email` text DEFAULT NULL,
                    `email_confirmed` enum("Y","N") NOT NULL DEFAULT "N",
                    `verification_code` varchar(255) DEFAULT NULL,
                    `image_id` int(11) DEFAULT NULL,
                    `token` varchar(255) DEFAULT NULL,
                    `last_active` varchar(100) NOT NULL,
                    `date_created` datetime NOT NULL DEFAULT current_timestamp(),
                    `date_updated` datetime DEFAULT NULL ON UPDATE current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `login_2` (`login`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
            ');

            $pdo->exec('
                CREATE TABLE `test_roles` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` text DEFAULT NULL,
                    `description` text DEFAULT NULL,
                    `date_created` datetime NOT NULL DEFAULT current_timestamp(),
                    `date_updated` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
            ');

            $pdo->exec('
                CREATE TABLE `test_user_roles` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) NOT NULL,
                    `role_id` int(11) NOT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
            ');

            $pdo->exec('
                CREATE TABLE `test_files` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) DEFAULT NULL,
                    `size` int(10) unsigned DEFAULT NULL,
                    `path` text DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
            ');

            $pdo->exec('
                CREATE TABLE `test_user_meta` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) NOT NULL,
                    `name` varchar(255) NOT NULL,
                    `value` text DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
            ');

            // Роли, на которые опирается код: обычный пользователь и администратор
            $pdo->exec('INSERT INTO `test_roles` (`id`, `name`) VALUES (1, "Администратор"), (2, "Пользователь")');
        }

        /**
         * Удалить схему
         *
         * @return void
         */
        public static function dropSchema(): void
        {
            $pdo = self::pdo();

            foreach (['test_user_roles', 'test_user_meta', 'test_files', 'test_users', 'test_roles'] as $table) {
                $pdo->exec('DROP TABLE IF EXISTS `' . $table . '`');
            }
        }

        /**
         * Очистить данные, сохранив схему
         *
         * @return void
         */
        public static function truncate(): void
        {
            $pdo = self::pdo();

            foreach (['test_user_roles', 'test_user_meta', 'test_files', 'test_users'] as $table) {
                $pdo->exec('DELETE FROM `' . $table . '`');
            }
        }

        /**
         * Вставить пользователя напрямую, минуя модель
         *
         * @param string $login    Логин
         * @param string $password Готовый хеш пароля
         * @param array  $fields   Дополнительные поля
         *
         * @return int Идентификатор пользователя
         */
        public static function insertUser(string $login, string $password, array $fields = []): int
        {
            $data = array_merge([
                'login'       => $login,
                'password'    => $password,
                'name'        => 'Тестовый пользователь',
                'email'       => $login . '@example.test',
                'last_active' => (string)time(),
            ], $fields);

            $columns      = array_keys($data);
            $placeholders = array_map(static fn(string $c): string => ':' . $c, $columns);

            $statement = self::pdo()->prepare(
                'INSERT INTO `test_users` (`' . implode('`, `', $columns) . '`) VALUES (' . implode(', ', $placeholders) . ')'
            );
            $statement->execute($data);

            return (int)self::pdo()->lastInsertId();
        }

        /**
         * Прочитать пользователя напрямую, минуя модель и кэш
         *
         * @param int $id Идентификатор
         *
         * @return array|null Данные пользователя
         */
        public static function findUser(int $id): ?array
        {
            $statement = self::pdo()->prepare('SELECT * FROM `test_users` WHERE `id` = :id');
            $statement->execute(['id' => $id]);

            return $statement->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        /**
         * Сбросить состояние, влияющее на тесты между сценариями
         *
         * @return void
         */
        public static function resetRuntimeState(): void
        {
            $_SESSION = [];
            $_COOKIE  = [];

            // Синглтон соединения переиспользуется между тестами намеренно:
            // пересоздание на каждый тест утроило бы время прогона
            DataBase::getInstance();
        }
    }
