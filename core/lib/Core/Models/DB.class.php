<?php

    namespace Core\Models;


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
         * Подключение к базе данных
         *
         * @param string      $db_server Сервер
         * @param string      $db_user   Пользователь
         * @param string|null $db_pass   Пароль
         * @param string      $db_name   База
         */
        public function __construct(string $db_server = DB_HOST, string $db_user = DB_USER, ?string $db_pass = DB_PASSWORD, string $db_name = DB_NAME)
        {
            if (DB_TYPE === DB_TYPE_SQL) {
                $dsn      = 'mysql:host=' . $db_server . ';dbname=' . $db_name . ';charset=utf8';
                $opt      = [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                $this->db = new \PDO($dsn, $db_user, $db_pass, $opt);
            } elseif (DB_TYPE === DB_TYPE_SQLITE) {
                $this->db = new \SQLite3(SQLITE_DB_FILE);
            }
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
                    $result = $result . '\'' . $v . '\', ';
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

            if(DB_TYPE === DB_TYPE_SQL) {
                try {
                    $stmt = $this->db->query($sql);
                } catch (\Throwable $t) {
                    throw new CoreException('В SQL запросе произошла ошибка', CoreException::ERROR_SQL_QUERY);
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
            } elseif(DB_TYPE === DB_TYPE_SQLITE) {
                $result = $this->db->query($sql);
                return $result->fetchArray(SQLITE3_ASSOC);
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
         * @param $table
         * @param $data
         *
         * @return int|null
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

    }