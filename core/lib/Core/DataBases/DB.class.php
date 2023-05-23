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

    namespace Core\DataBases;

    use Core\CoreException;
    use PDO;
    use Throwable;

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
         * @var self|null $instance Объект класса
         */
        private static ?self $instance = null;

        /**
         * @var int $quantity Количество обращений к базе
         */
        public static int $quantity = 0;

        /**
         * @var float $workingTime Время выполнения запросов
         */
        public static float $workingTime = 0;

        /**
         * ID последней добавленной записи
         *
         * @var string|null
         */
        private ?string $lastInsertId = null;

        /**
         * @var string|null $dbServer Сервер
         */
        private ?string $dbServer;

        /**
         * @var string|null $dbUser Пользователь
         */
        private ?string $dbUser;

        /**
         * @var string|null $dbPass Пароль
         */
        private ?string $dbPass;

        /**
         * @var string|null $dbName Имя базы
         */
        private ?string $dbName;


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
            // TODO: на случай чего то стоящего...
        }

        /**
         * Отправка уведомления о критическом событии
         * // TODO: метод-заглушка для возможности оповещения о критических событиях
         *
         * @param string $text Текст
         *
         * @return void
         * @deprecated Вероятно, будет выпилено в дальнейшем
         */
        public static function sendAlarm(string $text): void
        {
            if (function_exists('sendTelegram')) {
                sendTelegram($text);
            }
        }

        /**
         * Получить объект класса
         *
         * @return self
         */
        public static function getInstance(): object
        {
            if (empty(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Получить информацию по таблице
         *
         * @param string $table Таблица
         *
         * @return array|null Информация по таблице
         * @throws Throwable Возможный тип исключения
         */
        public function getColumnsList(string $table): array
        {
            $columns = $this->query('SHOW COLUMNS FROM `' . $table . '`');
            $result  = [];
            foreach ($columns as $info) {
                $result[$info['Field']]['field'] = $info['Field'];
                $typeData            = [];
                preg_match_all('/^(\w+)(\(([\w,\']+)\))?(\s\w+)?$/m', $info['Type'], $typeData, PREG_SET_ORDER);

                // Задаём тип колонки
                if(empty($typeData[0][1]) === false) {
                    $result[$info['Field']]['type'] = $typeData[0][1];
                }

                // Если это enum, то в скобках идут возможные значения, иначе там указана длинна указанного значения
                if (empty($typeData[0][3]) === false) {
                    if ($result[$info['Field']]['type'] === 'enum') {
                        // Заменяем кавычки в начале и конце строки
                        $typeData[0][3] = preg_replace('/^\'|\'$/m', '', $typeData[0][3]);
                        // Разбиваем значения
                        $result[$info['Field']]['enumValues'] = explode('\',\'', $typeData[0][3]);
                    } else {
                        $result[$info['Field']]['length'] = (int)$typeData[0][3];
                    }
                }

            }
            return $result;
        }

        /**
         * Вспомогательный метод, формирует WHERE из массива
         *
         * @param mixed  $where Массив условия выборки ['id' => 1] или прямая строка вида owner="admin"
         * @param string $logic Логика выборки AND или OR
         *
         * @return string Результат обработки
         */
        public function createWhere($where, string $logic = 'AND'): string
        {
            if (!is_array($where)) {
                return $where;
            }
            if (empty($where)) {
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
         * @param mixed $set Массив установки вида ['email' => "rsgrinko@yandex.ru"] или прямая строка вида email="rsgrinko@yandex.ru"
         *
         * @return string Результат обработки
         */
        private function createSet($set): string
        {
            if (!is_array($set)) {
                return $set;
            }
            $set_string = '';
            foreach ($set as $set_key => $set_item) {
                $set_string .= ' ' . $set_key . '=\'' . $set_item . '\',';
            }
            return substr($set_string, 0, -1);
        }

        /**
         * Вспомогательный метод для создания строки сортировки
         *
         * @param mixed $sort Условия сортировки вида ['ID'=> 'DESC']
         *
         * @return string Результат обработки
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
                        $result .= '\'' . ($v === null ? null : addslashes($v)) . '\', ';
                    }
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
         * @return mixed Результат выполнения запроса
         * @throws CoreException Возможные типы исключений
         */
        public function query(string $sql, bool $returnObject = false)
        {
            $startTime = microtime(true);
            self::$quantity++;
            try {
                $stmt = $this->db->query($sql);
            } catch (\Throwable $t) {
                self::sendAlarm(
                    '<b><u>' . date('d.m.Y H:i:s') . '</u></b>' . PHP_EOL . '</b><b>SQL ERROR:</b> ' . PHP_EOL . $this->db->errorInfo()[2] . PHP_EOL
                    . '<b>QUERY:</b> ' . PHP_EOL . $sql
                );
                if(DEBUG) {
                    throw new CoreException(
                        'В SQL запросе произошла ошибка: ' . $this->db->errorInfo()[2]
                        . '. Запрос: ' . $sql, CoreException::ERROR_SQL_QUERY);
                }
                throw new CoreException('В SQL запросе произошла ошибка', CoreException::ERROR_SQL_QUERY);
            }
            $this->lastInsertId = $this->db->lastInsertId() ?? null;

            $endTime           = microtime(true);
            self::$workingTime += ($endTime - $startTime);

            if ($returnObject) {
                return $stmt;
            }
            $result = $stmt->fetchAll();
            return $result ?: null;

        }

        /**
         * Старт транзакции
         *
         * @return $this
         * @throws CoreException
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
         * @throws CoreException
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
         * @throws CoreException
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
         * @throws CoreException
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
         * @throws CoreException
         */
        public function getItem($table, $where): ?array
        {
            self::$quantity++;
            $result = $this->query('SELECT * FROM `' . $table . '` WHERE ' . $this->createWhere($where) . ' LIMIT 1');
            if ($result) {
                return array_shift($result);
            }

            return null;
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
        public function addItem(string $table, array $data): ?int
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
        public function addItems(string $table, array $data)
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
         * @param string $table
         * @param        $where
         *
         * @return bool
         * @throws CoreException
         */
        public function remove(string $table, $where): bool
        {
            self::$quantity++;
            $result = $this->query('DELETE FROM `' . $table . '` WHERE ' . $this->createWhere($where));
            return (bool)$result;
        }

        /**
         * Получить элементЫ из базы
         *
         * @param string $table
         * @param        $where
         * @param array  $sort
         *
         * @return array|null
         * @throws CoreException
         */
        public function getItems(string $table, $where, $sort = []): ?array
        {
            self::$quantity++;
            $result = $this->query('SELECT * FROM `' . $table . '` WHERE ' . $this->createWhere($where) . $this->createSort($sort));

            if ($result) {
                return $result;
            }

            return null;
        }


        /**
         * Получить все данные из таблицы
         *
         * @param string $table
         * @param string $sort
         *
         * @return array|null
         * @throws CoreException
         */
        public function getAll(string $table, $sort = ''): ?array
        {
            self::$quantity++;
            $result = $this->query('SELECT * FROM `' . $table . '`' . $this->createSort($sort));

            if ($result) {
                return $result;
            }

            return null;
        }

        /**
         * Получение строки из таблицы
         *
         * @param string $table
         * @param string $sql
         *
         * @return array|null
         * @throws CoreException
         */
        public function getRow(string $table, string $sql = ''): ?array
        {
            self::$quantity++;
            $result = $this->query('SELECT * FROM `' . $table . '` ' . $sql . ' LIMIT 1');

            if ($result) {
                return $result[0];
            }

            return null;
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