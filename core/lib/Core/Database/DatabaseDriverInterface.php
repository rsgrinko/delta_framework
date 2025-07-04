<?php

    namespace Core\Database;

    use Core\Database\DatabaseException;
    use PDO;

    /**
     * Интерфейс для работы с базой данных
     */
    interface DatabaseDriverInterface
    {
        /**
         * Конструктор драйвера
         *
         * @param  array  $params Параметры драйвера
         */
        public function __construct(array $params);
        /**
         * Метод для простого выполнения заданного SQL запроса
         *
         * @param string $sql SQL запрос
         *
         * @return array|null        Результат выполнения запроса
         * @throws DatabaseException Возможные типы исключений
         */
        public function query(string $sql): ?array;

        /**
         * Добавить элемент в базу
         *
         * @param string $table Таблица
         * @param array  $data  Данные
         *
         * @return int|null
         * @throws DatabaseException Возможные типы исключений
         */
        public function add(string $table, array $data = []): ?int;

        /**
         * Метод для изменения записи в таблице
         *
         * @param string $table Имя таблицы
         * @param array  $where Массив where
         * @param array  $set   Данные set
         *
         * @return bool
         * @throws DatabaseException Возможные типы исключений
         */
        public function update(string $table, array $where, array $set): bool;

        /**
         * Удалить элемент из базы
         *
         * @param string $table Таблица
         * @param array  $where Условие выборки
         *
         * @return bool
         * @throws DatabaseException Возможные типы исключений
         */
        public function delete(string $table, array $where = []): bool;

        /**
         * Получить элемент из базы
         *
         * @param  string $table Таблица
         * @param  array  $where Условие выборки
         *
         * @return array|null
         * @throws DatabaseException Возможные типы исключений
         */
        public function get(string $table, array $where = []): ?array;

        /**
         * Получить список элементов из базы
         *
         * @param  string $table Таблица
         * @param  array  $where Условие выборки
         * @param  array  $sort  Сортировка
         *
         * @return array|null
         * @throws DatabaseException Возможные типы исключений
         */
        public function getList(string $table, array $where = [], array $sort = ['id' => 'ASC']): ?array;

        /**
         * Получение количества элементов
         *
         * @param string $table Имя таблицы
         * @param array  $where Условие выборки
         *
         * @return int Количество элементов
         */
        public function getCount(string $table, array $where = []): int;

        /**
         * Старт транзакции
         *
         * @return self
         * @throws DatabaseException Возможные типы исключений
         */
        public function startTransaction(): self;

        /**
         * Коммит транзакции
         *
         * @return self
         * @throws DatabaseException Возможные типы исключений
         */
        public function commitTransaction(): self;

        /**
         * Откат транзакции
         *
         * @return self
         * @throws DatabaseException Возможные типы исключений
         */
        public function rollbackTransaction(): self;
    }
