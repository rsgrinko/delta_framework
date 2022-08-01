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

    namespace Core\Models;

    use PDO;
    use Core\CoreException;

    /**
     * Класс для работы с базой данных
     *
     * @author Roman Grinko <rsgrinko@gmail.com>
     */
    class DB
    {
        /**
         * @var object $db Объект базы
         */
        public object $db;

        /**
         * @var object|null $instance Объект класса
         */
        private static ?object $instance = null;

        /**
         * @var int $quantity Количество обращений к базе
         */
        public static int $quantity = 0;

        /**
         * @var int $workingTime Время выполнения запросов
         */
        public static int $workingTime = 0;

        /**
         * ID последней добавленной записи
         *
         * @var string|null
         */
        private ?string $lastInsertId = null;

        /**
         * @var string|null $dbServer Сервер
         */
        private ?string $dbServer = null;

        /**
         * @var string|null $dbUser Пользователь
         */
        private ?string $dbUser = null;

        /**
         * @var string|null $dbPass Пароль
         */
        private ?string $dbPass = null;

        /**
         * @var string|null $dbName Имя базы
         */
        private ?string $dbName = null;


        /**
         * Подключение к базе данных
         *
         * @param string      $dbServer Сервер
         * @param string      $dbUser   Пользователь
         * @param string|null $dbPass   Пароль
         * @param string      $dbName   База
         */
        public function __construct(string $dbServer = DB_HOST, string $dbUser = DB_USER, ?string $dbPass = DB_PASSWORD, string $dbName = DB_NAME)
        {
            $this->dbServer = $dbServer;
            $this->dbUser   = $dbUser;
            $this->dbPass   = $dbPass;
            $this->dbName   = $dbName;

            $dsn      = 'mysql:host=' . $dbServer . ';dbname=' . $dbName . ';charset=utf8';
            $opt      = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->db = new \PDO($dsn, $dbUser, $dbPass, $opt);
        }

        /**
         * Деструктор
         */
        public function __destruct()
        {
            unset($this->instance);
            unset($this->db);
        }


        /**
         * Получить объект класса
         *
         * @return object
         */
        public static function getInstance(): object
        {
            if (empty(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Вспомогательный метод, формирует WHERE из массива
         *
         * @param        $where
         * @param string $logic
         *
         * @return string
         */
        public function createWhere($where, string $logic = 'AND'): string
        {
            if (!is_array($where)) {
                return $where;
            } elseif (empty($where)) {
                return '1';
            }
            $where_string = '';
            foreach ($where as $where_key => $where_item) {
                if (stristr($where_item, '>')) {
                    $symbol     = '>';
                    $where_item = str_replace($symbol, '', $where_item);
                } elseif (stristr($where_item, '<')) {
                    $symbol     = '<';
                    $where_item = str_replace($symbol, '', $where_item);
                } elseif (stristr($where_item, '<=')) {
                    $symbol     = '<=';
                    $where_item = str_replace($symbol, '', $where_item);
                } elseif (stristr($where_item, '>=')) {
                    $symbol     = '>=';
                    $where_item = str_replace($symbol, '', $where_item);
                } else {
                    $symbol = '=';
                }
                $where_string = $where_string . ' ' . $where_key . $symbol . '\'' . $where_item . '\' ' . $logic;
            }
            $offset = (strlen($logic) + 1);
            return substr($where_string, 0, -$offset);
        }

        /**
         * Вспомогательный метод, формирует SET из массива
         *
         * @param $set
         *
         * @return string
         */
        private function createSet($set): string
        {
            if (!is_array($set)) {
                return $set;
            }
            $set_string = '';
            foreach ($set as $set_key => $set_item) {
                $set_string = $set_string . ' ' . $set_key . '=\'' . $set_item . '\',';
            }
            return substr($set_string, 0, -1);
        }

        /**
         * Вспомогательный метод для создания строки сортировки
         *
         * @param $sort
         *
         * @return string
         */
        private function createSort($sort): string
        {
            $sort_string = '';
            if (is_array($sort)) {
                foreach ($sort as $k => $v) {
                    $sort_string = ' ORDER BY ' . $k . ' ' . $v;
                }
            }

            return $sort_string;
        }


        /**
         * Вспомогательный метод для построения запросов
         *
         * @param        $data
         * @param string $param
         *
         * @return string
         */
        private function createInsertString($data, $param = 'key'): string
        {
            $result = '';
            foreach ($data as $k => $v) {
                if ($param == 'key') {
                    $result = $result . $k . ', ';
                } elseif ($param == 'value') {
                    $result = $result . '\'' . addslashes($v) . '\', ';
                }
            }
            return substr($result, 0, -2);
        }

        /**
         * Метод для простого выполнения заданного SQL запроса.
         * Возвращает результат в виде массива или объекта, при неудаче возвращает null
         *
         * @param string $sql          SQL запрос
         * @param bool   $returnObject Вернуть объект после выполнения
         *
         * @return mixed
         */
        public function query(string $sql, bool $returnObject = false)
        {
            $startTime = microtime(true);
            self::$quantity++;
            try {
                $stmt = $this->db->query($sql);
            } catch (\Throwable $t) {
                sendTelegram(
                    '<b><u>' . date('d.m.Y H:i:s') . '</u></b>' . PHP_EOL . '</b><b>SQL ERROR:</b> ' . PHP_EOL . $this->db->errorInfo()[2] . PHP_EOL
                    . '<b>QUERY:</b> ' . PHP_EOL . $sql
                );
                if(DEBUG) {
                    throw new CoreException('В SQL запросе произошла ошибка. '.$this->db->errorInfo()[2].' Запрос: ' . $sql, CoreException::ERROR_SQL_QUERY);
                } else {
                    throw new CoreException('В SQL запросе произошла ошибка', CoreException::ERROR_SQL_QUERY);
                }

            }
            $this->lastInsertId = $this->db->lastInsertId() ?? null;


            $endTime           = microtime(true);
            self::$workingTime += ($endTime - $startTime);

            if ($returnObject) {
                return $stmt;
            } else {
                $result = $stmt->fetchAll();
                return $result ?: null;
            }
        }

        /**
         * Метод для обновления записи в таблице
         * Принимает 3 аргумента: имя таблицы, массив для WHERE и массив значений для обновления (ключ-значение)
         *
         * @param string $table
         * @param array  $where
         * @param        $set
         *
         * @return array|false
         */
        public function update(string $table, $where, $set)
        {
            self::$quantity++;
            $result = $this->query('UPDATE `' . $table . '` SET ' . $this->createSet($set) . ' WHERE ' . $this->createWhere($where));
            return (bool)$result;
        }

        /**
         * Получить элемент из базы
         *
         * @param $table
         * @param $where
         *
         * @return array|null
         */
        public function getItem($table, $where): ?array
        {
            self::$quantity++;
            $result = $this->query('SELECT * FROM `' . $table . '` WHERE ' . $this->createWhere($where) . ' LIMIT 1');
            if ($result) {
                return $result[0];
            } else {
                return null;
            }
        }


        /**
         * Добавить элемент в базу
         *
         * @param string $table   Таблица
         * @param array  $data    Данные <pre>
         *                        [
         *                        'name' => 'Roman',
         *                        'age' => 27
         *                        ]
         *                        </pre>
         *
         * @return int|null
         * @throws CoreException
         */
        public function addItem($table, $data): ?int
        {
            self::$quantity++;
            $this->query(
                'INSERT INTO `' . $table . '` (' . $this->createInsertString($data, 'key') . ') VALUES (' . $this->createInsertString($data, 'value')
                . ')'
            );
            return (int)$this->lastInsertId;
        }

        /**
         * Добавить несколько элементов в базу
         *
         * @param string $table   Таблица
         * @param array  $data    Данные <pre>
         *                        [
         *                        ['name' => 'Roman', 'age' => 27],
         *                        ['name' => 'Dmitry', 'age' => 31],
         *                        ]
         *                        </pre>
         *
         * @return int|null
         * @throws CoreException
         */
        public function addItems($table, $data)
        {
            self::$quantity++;

            if (empty($data)) {
                return null;
            }

            $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', array_keys(reset($data))) . ') VALUES ';

            $arValues = [];
            foreach ($data as $element) {
                $element    = array_map(function ($el) {
                    return '"' . $el . '"';
                }, $element);
                $arValues[] = '(' . implode(', ', $element) . ')';
            }

            $sql .= implode(', ', $arValues) . ';';

            return $this->query($sql);
        }

        /**
         * Удалить элемент из базы
         *
         * @param $table
         * @param $where
         *
         * @return bool
         */
        public function remove($table, $where): bool
        {
            self::$quantity++;
            $result = $this->query('DELETE FROM `' . $table . '` WHERE ' . $this->createWhere($where));
            return (bool)$result;
        }

        /**
         * Получить элементЫ из базы
         *
         * @param        $table
         * @param        $where
         * @param string $sort
         *
         * @return array|null
         */
        public function getItems($table, $where, $sort = []): ?array
        {
            self::$quantity++;
            $result = $this->query('SELECT * FROM `' . $table . '` WHERE ' . $this->createWhere($where) . $this->createSort($sort));

            if ($result) {
                return $result;
            } else {
                return null;
            }
        }


        /**
         * Получить все данные из таблицы
         *
         * @param        $table
         * @param string $sort
         *
         * @return array|null
         */
        public function getAll($table, $sort = ''): ?array
        {
            self::$quantity++;
            $result = $this->query('SELECT * FROM `' . $table . '`' . $this->createSort($sort));

            if ($result) {
                return $result;
            } else {
                return null;
            }
        }

        /**
         * Получение строки из таблицы
         *
         * @param        $table
         * @param string $sql
         * @param array  $params
         * @param false  $force
         *
         * @return false|mixed
         */
        public function getRow($table, $sql = '', $params = [])
        {
            self::$quantity++;
            $result = $this->query('SELECT * FROM `' . $table . '` ' . $sql . ' LIMIT 1', $params);

            if ($result) {
                return $result[0];
            } else {
                return false;
            }
        }

        /**
         * Получение размера текущей базы данных
         *
         * @return int Размер в байтах
         * @throws CoreException
         */
        public function getDatabaseSize(): int
        {

            $size = 0;
            $res = $this->query('SHOW TABLE STATUS FROM ' . $this->dbName);

            if ($res) {
                foreach($res as $item) {
                    $size += ((int)$item['Index_length'] + (int)$item['Data_length']);
                }
            }
            return $size;
        }

    }