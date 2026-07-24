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

    namespace Core\Database;

    use Core\Database\Drivers\MySqlDriver;

    /**
     * Класс для работы с базой данных
     *
     * @author Roman Grinko <rsgrinko@gmail.com>
     */
    class DataBase
    {
        /** @var DatabaseDriverInterface $db Объект базы */
        public DatabaseDriverInterface $db;

        /** @var self|null $instance Объект класса */
        private static ?self $instance = null;

        /**
         * Подключение к базе данных
         *
         * @param  string|null $driver Драйвер
         * @param  array|null  $params Параметры
         */
        public function __construct(?string $driver = 'mysql', ?array $params = null)
        {
            $this->db = match ($driver) {
                'mysql' => new MySqlDriver($params ?? ['server' => DB_HOST, 'database' => DB_NAME, 'user' => DB_USER, 'password' => DB_PASSWORD]),
                default => new MySqlDriver($params ?? ['server' => DB_HOST, 'database' => DB_NAME, 'user' => DB_USER, 'password' => DB_PASSWORD]),
            };
        }

        /**
         * Получить объект класса
         *
         * @return self
         */
        public static function getInstance(?string $driver = null): object
        {
            if (empty(self::$instance)) {
                self::$instance = new self($driver);
            }
            return self::$instance;
        }

        /**
         * Метод для простого выполнения заданного SQL запроса.
         * Возвращает результат в виде массива или объекта, при неудаче возвращает null.
         * $sql может содержать именованные плейсхолдеры (:name) со значениями в $params —
         * запрос выполняется через подготовленное выражение, значения в текст SQL не подставляются.
         *
         * @param string $sql    SQL запрос
         * @param array  $params Параметры для подготовленного выражения (плейсхолдер => значение)
         *
         * @return mixed Результат выполнения запроса
         * @throws DatabaseException Возможные типы исключений
         */
        public function query(string $sql, array $params = []): ?array
        {
            return $this->db->query($sql, $params);
        }

        /**
         * @throws DatabaseException
         */
        public function add(string $table, array $data = []): ?int
        {
            return $this->db->add($table, $data);
        }

        /**
         * Метод для обновления записи в таблице.
         * Принимает 3 аргумента: имя таблицы, массив для WHERE и массив значений для обновления (ключ-значение)
         *
         * @param string       $table Имя таблицы
         * @param array|string $where Массив where
         * @param array|string $set   Данные set
         *
         * @return bool
         * @throws DatabaseException
         */
        public function update(string $table, array $where, array $set): bool
        {
            return $this->db->update($table, $where, $set);
        }

        /**
         * @throws DatabaseException
         */
        public function delete(string $table, array $where = []): bool
        {
            return $this->db->delete($table, $where);
        }

        /**
         * @throws DatabaseException
         */
        public function get(string $table, array $where = []): ?array
        {
            return $this->db->get($table, $where);
        }

        /**
         * @throws DatabaseException
         */
        public function getList(string $table, array $where = [], array $sort = ['id' => 'ASC']): ?array
        {
            return $this->db->getList($table, $where, $sort);
        }

        public function getCount(string $table, array $where = []): int
        {
            return $this->db->getCount($table, $where);
        }

        /**
         * Старт транзакции
         *
         * @return $this
         * @throws DatabaseException
         */
        public function startTransaction(): self
        {
            $this->db->startTransaction();
            return $this;
        }

        /**
         * Коммит транзакции
         *
         * @return $this
         * @throws DatabaseException
         */
        public function commitTransaction(): self
        {
            $this->db->commitTransaction();
            return $this;
        }

        /**
         * Откат транзакции
         *
         * @return $this
         * @throws DatabaseException
         */
        public function rollbackTransaction(): self
        {
            $this->db->rollbackTransaction();
            return $this;
        }
    }
