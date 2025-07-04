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
         * Вспомогательный метод, формирует WHERE из массива
         *
         * @param array  $where Массив условия выборки ['id' => 1] или прямая строка вида owner="admin"
         * @param string $logic Логика выборки AND или OR
         *
         * @return string Результат обработки
         */
        public function createWhere(array $where, string $logic = 'AND'): string
        {
            if (empty($where)) {
                return '1';
            }
            $where_string = '';
            foreach ($where as $where_key => $where_item) {
                if (str_contains($where_item, '>')) {
                    $symbol     = '>';
                    $where_item = str_replace($symbol, '', $where_item);
                } elseif (str_contains($where_item, '<')) {
                    $symbol     = '<';
                    $where_item = str_replace($symbol, '', $where_item);
                } elseif (str_contains($where_item, '<=')) {
                    $symbol     = '<=';
                    $where_item = str_replace($symbol, '', $where_item);
                } elseif (str_contains($where_item, '>=')) {
                    $symbol     = '>=';
                    $where_item = str_replace($symbol, '', $where_item);
                } else {
                    $symbol = '=';
                }
                $where_string .= ' '.$where_key.$symbol.'\''.$where_item.'\' '.$logic;
            }
            $offset = (strlen($logic) + 1);
            return substr($where_string, 0, -$offset);
        }

        /**
         * Вспомогательный метод, формирует SET из массива
         *
         * @param mixed $set Массив установки вида ['email' => "rsgrinko@yandex.ru"] или прямая строка вида email="rsgrinko@yandex.ru"
         *
         * @return string Результат обработки
         */
        private function createSet(array $set): string
        {
            $setString = '';
            foreach ($set as $setKey => $setValue) {
                $setString .= ' ' . $setKey . '=';
                if (is_null($setValue)) {
                    $setString .= ' ' . $setKey . '=null,';
                } else {
                    $setString .= '\'' . $setValue . '\',';
                }

            }
            return substr($setString, 0, -1);
        }

        /**
         * Вспомогательный метод для создания строки сортировки
         *
         * @param mixed $sort Условия сортировки вида ['ID'=> 'DESC']
         *
         * @return string Результат обработки
         */
        private function createSort(array $sort): string
        {
            $sortString = '';
            foreach ($sort as $k => $v) {
                $sortString = ' ORDER BY ' . $k . ' ' . $v;
            }

            return $sortString;
        }


        /**
         * Вспомогательный метод для построения запросов
         *
         * @param array $data Массив данных для вставки вида [['data' => 'test'], ['data2' => 'test2']]
         * @param string $param Ключ key или value
         *
         * @return string Результат обработки
         */
        private function createInsertString(array $data, string $param = 'key'): string
        {
            $result = '';
            foreach ($data as $k => $v) {
                if ($param === 'key') {
                    $result .= $k . ', ';
                } elseif ($param === 'value') {
                    if (is_numeric($v)) {
                        $result .= $v . ', ';
                    } elseif ($v === null) {
                        $result .= 'NULL, ';
                    } else {
                        $result .= '\'' . addslashes($v) . '\', ';
                    }
                }
            }
            return substr($result, 0, -2);
        }

        /**
         * Метод для простого выполнения заданного SQL запроса.
         * Возвращает результат в виде массива или объекта, при неудаче возвращает null
         *
         * @param  string  $sql  SQL запрос
         *
         * @return array|null Результат выполнения запроса
         * @throws DatabaseException Возможные типы исключений
         */
        public function query(string $sql): ?array
        {
            try {
                $stmt = $this->pdoObject->query($sql);
            } catch (\Throwable $t) {
                if(SystemConfig::getValue('DEBUG')) {
                    throw new DatabaseException(
                        'В SQL запросе произошла ошибка: ' .$this->pdoObject->errorInfo()[2]
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
         * @param string       $table Имя таблицы
         * @param array|string $where Массив where
         * @param array|string $set   Данные set
         *
         * @return bool
         * @throws DatabaseException
         */
        public function update(string $table, $where, $set): bool
        {
            $result = $this->query('UPDATE `' . $table . '` SET ' . $this->createSet($set) . ' WHERE ' . $this->createWhere($where));
            return (bool)$result;
        }

        /**
         * @throws DatabaseException
         */
        public function add(string $table, array $data = []): ?int
        {
            $this->query('INSERT INTO `' . $table . '` (' . $this->createInsertString($data, 'key') . ') VALUES (' . $this->createInsertString($data, 'value') . ')');
            return (int)$this->lastInsertId;
        }

        /**
         * @throws DatabaseException
         */
        public function delete(string $table, array $where = []): bool
        {
            $result = $this->query('DELETE FROM `' . $table . '` WHERE ' . $this->createWhere($where));
            return (bool)$result;
        }

        /**
         * @throws DatabaseException
         */
        public function get(string $table, array $where = []): ?array
        {
            $result = $this->query('SELECT * FROM `' . $table . '` WHERE ' . $this->createWhere($where) . ' LIMIT 1');
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
            $result = $this->query('SELECT * FROM `' . $table . '` WHERE ' . $this->createWhere($where) . $this->createSort($sort));
            if ($result) {
                return $result;
            }
            return null;
        }

        public function getCount(string $table, array $where = []): int
        {
            $count = 0;
            try {
                $result = $this->query('SELECT COUNT(*) as count FROM `' . $table . '` WHERE ' . $this->createWhere($where));
            } catch (Throwable $e) {
                $result = false;
            }
            if ($result) {
                $count = (int)$result[0]['count'];
            }
            return $count;
        }
    }