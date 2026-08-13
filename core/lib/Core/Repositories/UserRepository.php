<?php

    declare(strict_types=1);

    namespace Core\Repositories;

    use Core\Database\DataBase;
    use Core\Database\DatabaseException;

    /**
     * Доступ к данным пользователей.
     *
     * Собирает в одном месте SQL, который был размазан по модели `User`. Ценность не в самих
     * запросах, а в границе: модель перестаёт знать про таблицы и параметры выборки, а подменить
     * источник данных в тесте становится вопросом передачи другого соединения в конструктор.
     */
    final class UserRepository
    {
        /**
         * Конструктор
         *
         * @param DataBase $db    Соединение с базой
         * @param string   $table Таблица пользователей
         */
        public function __construct(
            private readonly DataBase $db,
            private readonly string $table,
        ) {
        }

        /**
         * Найти пользователя по идентификатору
         *
         * @param int $id Идентификатор
         *
         * @return array|null Данные пользователя
         * @throws DatabaseException
         */
        public function findById(int $id): ?array
        {
            $result = $this->db->get($this->table, ['id' => $id]);

            return empty($result) ? null : $result;
        }

        /**
         * Найти пользователя по логину
         *
         * @param string $login Логин
         *
         * @return array|null Данные пользователя
         * @throws DatabaseException
         */
        public function findByLogin(string $login): ?array
        {
            $result = $this->db->get($this->table, ['login' => $login]);

            return empty($result) ? null : $result;
        }

        /**
         * Найти пользователя по токену
         *
         * @param string $token Токен
         *
         * @return array|null Данные пользователя
         * @throws DatabaseException
         */
        public function findByToken(string $token): ?array
        {
            $result = $this->db->get($this->table, ['token' => $token]);

            return empty($result) ? null : $result;
        }

        /**
         * Найти пользователя по произвольному набору условий
         *
         * @param array $where Условия выборки
         *
         * @return array|null Данные пользователя
         * @throws DatabaseException
         */
        public function findBy(array $where): ?array
        {
            $result = $this->db->get($this->table, $where);

            return empty($result) ? null : $result;
        }

        /**
         * Найти пользователя по коду верификации
         *
         * @param string $code Код верификации
         *
         * @return array|null Данные пользователя
         * @throws DatabaseException
         */
        public function findByVerificationCode(string $code): ?array
        {
            $result = $this->db->get($this->table, ['verification_code' => $code]);

            return empty($result) ? null : $result;
        }

        /**
         * Признак существования пользователя
         *
         * @param array $where Условия выборки
         *
         * @return bool Признак существования
         * @throws DatabaseException
         */
        public function exists(array $where): bool
        {
            return $this->findBy($where) !== null;
        }

        /**
         * Постраничный список пользователей
         *
         * @param string $limit Ограничение выборки в формате SQL LIMIT
         * @param string $sort  Направление сортировки по идентификатору
         *
         * @return array Список пользователей
         * @throws DatabaseException
         */
        public function paginate(string $limit, string $sort = 'ASC'): array
        {
            // Значения не могут попасть в плейсхолдеры: LIMIT и направление сортировки —
            // часть синтаксиса запроса, поэтому они приводятся к безопасному виду здесь
            $safeSort  = strtoupper($sort) === 'DESC' ? 'DESC' : 'ASC';
            $safeLimit = preg_replace('/[^0-9,\s]/', '', $limit);

            $result = $this->db->query(
                'SELECT * FROM `' . $this->table . '` ORDER BY `id` ' . $safeSort
                . ($safeLimit !== '' ? ' LIMIT ' . $safeLimit : '')
            );

            return $result ?? [];
        }

        /**
         * Все пользователи
         *
         * @return array Список пользователей
         * @throws DatabaseException
         */
        public function all(): array
        {
            return $this->db->getList($this->table, ['id' => '>0']) ?? [];
        }

        /**
         * Количество пользователей
         *
         * @param array $where Условия выборки
         *
         * @return int Количество
         */
        public function count(array $where = []): int
        {
            return $this->db->getCount($this->table, $where);
        }

        /**
         * Время последней активности пользователя
         *
         * @param int $id Идентификатор
         *
         * @return int Отметка времени
         * @throws DatabaseException
         */
        public function lastActive(int $id): int
        {
            $result = $this->db->query(
                'SELECT last_active FROM `' . $this->table . '` WHERE id = :id',
                ['id' => $id]
            );

            return (int)($result[0]['last_active'] ?? 0);
        }

        /**
         * Количество пользователей, находящихся онлайн
         *
         * @param int $onlineTime Порог активности, сек.
         *
         * @return int Количество
         * @throws DatabaseException
         */
        public function countOnline(int $onlineTime): int
        {
            $result = $this->db->query(
                'SELECT COUNT(*) AS `count` FROM `' . $this->table . '` WHERE `last_active` > :threshold',
                ['threshold' => time() - $onlineTime]
            );

            return (int)($result[0]['count'] ?? 0);
        }

        /**
         * Создать пользователя
         *
         * @param array $fields Поля
         *
         * @return int|null Идентификатор созданной записи
         * @throws DatabaseException
         */
        public function insert(array $fields): ?int
        {
            return $this->db->add($this->table, $fields);
        }

        /**
         * Обновить пользователя
         *
         * @param int   $id     Идентификатор
         * @param array $fields Поля
         *
         * @return void
         * @throws DatabaseException
         */
        public function update(int $id, array $fields): void
        {
            $this->db->update($this->table, ['id' => $id], $fields);
        }

        /**
         * Удалить пользователя
         *
         * @param int $id Идентификатор
         *
         * @return void
         * @throws DatabaseException
         */
        public function delete(int $id): void
        {
            $this->db->delete($this->table, ['id' => $id]);
        }
    }
