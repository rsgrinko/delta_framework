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

    namespace Core\Database\Drivers;

    use Core\Database\DatabaseException;
    use Core\SystemConfig;
    use Core\Database\DatabaseDriverInterface;
    use PDO;
    use Throwable;

    /**
     * Драйвер для MySQL
     *
     * @author Roman Grinko <rsgrinko@gmail.com>
     */
    class MySqlDriver implements DatabaseDriverInterface
    {
        /**
         * @var PDO $pdoObject Объект базы
         */
        public PDO $pdoObject;

        /**
         * ID последней добавленной записи
         *
         * @var string|null
         */
        private ?string $lastInsertId = null;

        /**
         * Подключение к базе данных
         *
         * @param  array  $params
         */
        public function __construct(array $params)
        {
            $this->pdoObject = new \PDO(
                'mysql:host=' . $params['server'] . ';dbname=' . $params['database'] . ';charset=utf8', $params['user'], $params['password'],
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        /**
         * Экранирование имени таблицы/колонки (идентификатора). Значения так не экранируются —
         * для значений используются плейсхолдеры подготовленного выражения, а не эта функция.
         *
         * @param string $identifier Имя таблицы или колонки
         *
         * @return string
         */
        private function quoteIdentifier(string $identifier): string
        {
            return '`' . str_replace('`', '', $identifier) . '`';
        }

        /**
         * Генерирует уникальное имя плейсхолдера на основе имени колонки
         *
         * @param string $prefix Префикс группы плейсхолдеров (чтобы не пересекались WHERE/SET/INSERT)
         * @param string $column Имя колонки
         * @param int    $index  Порядковый номер (для уникальности при повторе имени колонки)
         *
         * @return string
         */
        private function makePlaceholder(string $prefix, string $column, int $index): string
        {
            $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '_', $column);
            return ':' . $prefix . '_' . $safeColumn . '_' . $index;
        }

        /**
         * Вспомогательный метод, формирует WHERE из массива в виде подготовленного выражения.
         * Значение элемента может начинаться с оператора сравнения (>, <, >=, <=), иначе используется '='.
         * Пример: ['id' => 5, 'age' => '>=18']
         *
         * @param array  $where Массив условия выборки ['id' => 1] или ['id' => '>0']
         * @param string $logic Логика выборки AND или OR
         *
         * @return array{0: string, 1: array} [SQL-фрагмент с плейсхолдерами, параметры для execute()]
         */
        private function createWhere(array $where, string $logic = 'AND'): array
        {
            if (empty($where)) {
                return ['1=1', []];
            }

            $parts  = [];
            $params = [];
            $index  = 0;
            foreach ($where as $column => $value) {
                $operator = '=';
                if (is_string($value)) {
                    foreach (['>=', '<=', '>', '<'] as $possibleOperator) {
                        if (str_starts_with($value, $possibleOperator)) {
                            $operator = $possibleOperator;
                            $value    = substr($value, strlen($possibleOperator));
                            break;
                        }
                    }
                }

                $placeholder            = $this->makePlaceholder('w', (string)$column, $index++);
                $parts[]                = $this->quoteIdentifier((string)$column) . $operator . $placeholder;
                $params[$placeholder]   = $value;
            }

            $logic = strtoupper($logic) === 'OR' ? 'OR' : 'AND';
            return [implode(' ' . $logic . ' ', $parts), $params];
        }

        /**
         * Вспомогательный метод, формирует SET из массива в виде подготовленного выражения
         *
         * @param array $set Массив установки вида ['email' => "rsgrinko@yandex.ru"]
         *
         * @return array{0: string, 1: array} [SQL-фрагмент с плейсхолдерами, параметры для execute()]
         */
        private function createSet(array $set): array
        {
            $parts  = [];
            $params = [];
            $index  = 0;
            foreach ($set as $column => $value) {
                $placeholder          = $this->makePlaceholder('s', (string)$column, $index++);
                $parts[]              = $this->quoteIdentifier((string)$column) . '=' . $placeholder;
                $params[$placeholder] = $value;
            }

            return [implode(', ', $parts), $params];
        }

        /**
         * Вспомогательный метод для создания строки сортировки.
         * Имена колонок и направление сортировки нельзя передать через плейсхолдер (это идентификаторы,
         * а не значения), поэтому они проходят через белый список вместо экранирования.
         *
         * @param array $sort Условия сортировки вида ['id' => 'DESC']
         *
         * @return string Результат обработки
         */
        private function createSort(array $sort): string
        {
            $sortString = '';
            foreach ($sort as $column => $direction) {
                $safeColumn    = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$column);
                $safeDirection = strtoupper((string)$direction) === 'DESC' ? 'DESC' : 'ASC';
                $sortString    = ' ORDER BY ' . $this->quoteIdentifier($safeColumn) . ' ' . $safeDirection;
            }

            return $sortString;
        }

        /**
         * Метод для простого выполнения заданного SQL запроса через подготовленное выражение.
         * Возвращает результат в виде массива или объекта, при неудаче возвращает null
         *
         * @param  string $sql    SQL запрос, может содержать именованные плейсхолдеры (:name)
         * @param  array  $params Параметры для подготовленного выражения (плейсхолдер => значение)
         *
         * @return array|null Результат выполнения запроса
         * @throws DatabaseException Возможные типы исключений
         */
        public function query(string $sql, array $params = []): ?array
        {
            try {
                if (empty($params)) {
                    // Без параметров выполняем как раньше, через PDO::query() — часть административных
                    // запросов (SET NAMES, TRUNCATE, SHOW COLUMNS и т.п.) не нуждается в подготовленном
                    // выражении и это сохраняет прежнее поведение для всех вызовов без плейсхолдеров.
                    $stmt = $this->pdoObject->query($sql);
                } else {
                    $stmt = $this->pdoObject->prepare($sql);
                    $stmt->execute($params);
                }
            } catch (\Throwable $t) {
                if(SystemConfig::getValue('DEBUG')) {
                    throw new DatabaseException(
                        'В SQL запросе произошла ошибка: ' . $t->getMessage()
                        . '. Запрос: ' . $sql, DatabaseException::ERROR_SQL_QUERY);
                }
                throw new DatabaseException('В SQL запросе произошла ошибка', DatabaseException::ERROR_SQL_QUERY);
            }
            $this->lastInsertId = $this->pdoObject->lastInsertId();

            $result = $stmt->fetchAll();
            return $result ?: null;

        }

        /**
         * Старт транзакции
         *
         * @return $this
         * @throws DatabaseException
         */
        public function startTransaction(): self
        {
            $this->query('START TRANSACTION;');
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
            $this->query('COMMIT;');
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
            $this->query('ROLLBACK;');
            return $this;
        }

        /**
         * Метод для обновления записи в таблице.
         * Принимает 3 аргумента: имя таблицы, массив для WHERE и массив значений для обновления (ключ-значение)
         *
         * @param string $table Имя таблицы
         * @param array  $where Массив where
         * @param array  $set   Данные set
         *
         * @return bool
         * @throws DatabaseException
         */
        public function update(string $table, array $where, array $set): bool
        {
            [$setSql, $setParams]     = $this->createSet($set);
            [$whereSql, $whereParams] = $this->createWhere($where);

            $result = $this->query(
                'UPDATE ' . $this->quoteIdentifier($table) . ' SET ' . $setSql . ' WHERE ' . $whereSql,
                $setParams + $whereParams
            );
            return (bool)$result;
        }

        /**
         * @throws DatabaseException
         */
        public function add(string $table, array $data = []): ?int
        {
            $columns      = [];
            $placeholders = [];
            $params       = [];
            $index        = 0;
            foreach ($data as $column => $value) {
                $placeholder            = $this->makePlaceholder('i', (string)$column, $index++);
                $columns[]              = $this->quoteIdentifier((string)$column);
                $placeholders[]         = $placeholder;
                $params[$placeholder]   = $value;
            }

            $this->query(
                'INSERT INTO ' . $this->quoteIdentifier($table) . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')',
                $params
            );
            return (int)$this->lastInsertId;
        }

        /**
         * @throws DatabaseException
         */
        public function delete(string $table, array $where = []): bool
        {
            [$whereSql, $whereParams] = $this->createWhere($where);
            $result = $this->query('DELETE FROM ' . $this->quoteIdentifier($table) . ' WHERE ' . $whereSql, $whereParams);
            return (bool)$result;
        }

        /**
         * @throws DatabaseException
         */
        public function get(string $table, array $where = []): ?array
        {
            [$whereSql, $whereParams] = $this->createWhere($where);
            $result = $this->query(
                'SELECT * FROM ' . $this->quoteIdentifier($table) . ' WHERE ' . $whereSql . ' LIMIT 1',
                $whereParams
            );
            if ($result) {
                return array_shift($result);
            }
            return null;
        }

        /**
         * @throws DatabaseException
         */
        public function getList(string $table, array $where = [], array $sort = ['id' => 'ASC']): ?array
        {
            [$whereSql, $whereParams] = $this->createWhere($where);
            $result = $this->query(
                'SELECT * FROM ' . $this->quoteIdentifier($table) . ' WHERE ' . $whereSql . $this->createSort($sort),
                $whereParams
            );
            if ($result) {
                return $result;
            }
            return null;
        }

        public function getCount(string $table, array $where = []): int
        {
            $count = 0;
            try {
                [$whereSql, $whereParams] = $this->createWhere($where);
                $result = $this->query(
                    'SELECT COUNT(*) as count FROM ' . $this->quoteIdentifier($table) . ' WHERE ' . $whereSql,
                    $whereParams
                );
            } catch (Throwable $e) {
                $result = false;
            }
            if ($result) {
                $count = (int)$result[0]['count'];
            }
            return $count;
        }
    }
