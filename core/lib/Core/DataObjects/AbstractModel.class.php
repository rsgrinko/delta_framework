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

    namespace Core\DataObjects;
    use Core\CoreException;
    use Core\Helpers\SystemFunctions;
    use Core\Models\DB;
    use \DateTime;
    use \JsonSerializable;
    use \Throwable;


    /**
     * Абстрактный класс модели
     */
    abstract class AbstractModel implements JsonSerializable
    {
        /** @var string Наименование таблицы */
        public const TABLE = null;

        /** @var string Поле первичного ключа таблицы */
        public const ID_NAME = 'id';

        /** @var string Коллекция, необходимая для данной модели */
        protected const COLLECTION = ModelCollection::class;

        /** @var string[] Поля, защищенные от записи */
        protected const WRITE_PROTECTION = [
            'date_created',
            'date_updated',
            'id',
        ];

        /** @var string Тип столбца: булевой */
        protected const COLUMN_TYPE_BOOL = 'tinyint';

        /** @var string Тип столбца: строка */
        protected const COLUMN_TYPE_STRING = 'varchar';

        /** @var string Тип столбца: целочисленное число */
        protected const COLUMN_TYPE_INT = 'int';

        /** @var string Тип столбца: дата и время */
        protected const COLUMN_TYPE_DATETIME = 'datetime';

        /** @var string Тип столбца: json */
        protected const COLUMN_TYPE_JSON = 'json';

        /** @var string Тип столбца: enum */
        protected const COLUMN_TYPE_ENUM = 'enum';

        /** @var int|null Идентификатор элемента */
        protected $id;

        /** @var array Переименование полей, если необходимо DB => MODEL */
        protected $renameProps = [];

        /** @var array Поля, являющиеся флагами true|false */
        protected $boolFields = [];

        /** @var array Список колонок для таблиц */
        private static $columnList = [];

        /** @var array Исходные данные */
        protected $sourceData = [];

        /** @var string Формат времени */
        public const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

        /** @var string Формат времени */
        public const JSON_DATE_TIME_FORMAT = 'd.m.Y';

        /**
         * Конструктор
         *
         * @param array|null $data Данные для заполнения свойств
         */
        public function __construct(?array $data = null)
        {
            // Получаем свойства таблицы
            self::getColumnList(static::TABLE);
            // Наполняем данными
            if (empty($data) === false) {
                $this->setData($data);
            }
        }

        /**
         * Получение идентификатора записи
         *
         * @return int Идентификатор записи
         */
        public function getId(): int
        {
            $id = static::ID_NAME;
            return $this->$id;
        }

        /**
         * Обнулить идентификатор и старые данные объекта
         *
         * @return $this
         */
        public function clearClone(): self
        {
            $id               = static::ID_NAME;
            $this->$id        = null;
            $this->sourceData = [];
            return $this;
        }

        /**
         * Получить столбцы таблицы
         *
         * @param string $table Таблица
         *
         * @return array|null Имена колонок
         */
        private static function getColumnList(string $table): array
        {
            $columnList = self::getColumnsInfo($table);
            return array_keys($columnList);
        }

        /**
         * Получить и сохранить свойства таблицы
         *
         * @param string $table Таблица
         *
         * @return array|null Имена колонок
         */
        private static function getColumnsInfo(string $table): array
        {
            // Получаем свойства таблицы
            if (empty(self::$columnList[$table])) {
                /** @var DB $db */
                $db = DB::getInstance();
                self::$columnList[$table] = $db->getColumnsList($table);
            }
            return self::$columnList[$table];
        }

        /**
         * Установка данных объекта
         *
         * @param array $data Данные из БД
         */
        private function setData(array $data): void
        {
            $this->sourceData = $data;
            foreach ($data as $prop => $value) {
                $key = $prop;
                if (empty($this->renameProps[$prop]) === false) {
                    $key = $this->renameProps[$prop];
                }
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
                $columnsInfo = self::getColumnsInfo(static::TABLE);
                if (empty($columnsInfo[$key]) === false) {
                    switch ($columnsInfo[$key]['type']) {
                        case self::COLUMN_TYPE_BOOL:
                            $this->$key = (bool)$this->$key;
                            break;
                    }
                }
            }
        }

        /**
         * Сохранить элемент
         *
         * @return $this
         * @throws CoreException
         */
        public function save(): self
        {
            /** @var DB $db */
            $db = DB::getInstance();
            $id = static::ID_NAME;
            if (empty($this->$id) === false) {
                $diff = $this->getDataDifference();
                if (empty($diff) === false) {
                    $db->update(static::TABLE, [$id => $this->$id], $this->sanitizeFields($diff));
                }
            } else {
                $this->$id = $db->addItem(static::TABLE, $this->sanitizeFields($this->getDataDifference(true)));
            }
            $this->setData($db->getItem(static::TABLE, [$id => $this->$id]));
            return $this;
        }

        /**
         * Удалить элемент из базы
         *
         * @return bool
         * @throws CoreException
         */
        public function delete(): bool
        {
            /** @var DB $db */
            $db     = DB::getInstance();
            $id     = static::ID_NAME;
            $result = $db->remove(static::TABLE, [$id => $this->$id]);
            if ($result) {
                $this->$id = null;
            }
            return $result;
        }

        /**
         * Получить разность данных
         *
         * @param bool $withoutNull Убирать значения с null
         *
         * @return array
         */
        private function getDataDifference(bool $withoutNull = false): array
        {
            $diff = array_diff_assoc($this->getArray(true, false), $this->sourceData);
            if ($withoutNull) {
                foreach ($diff as $key => $value) {
                    if (is_null($value)) {
                        unset($diff[$key]);
                    }
                }
            }
            return $diff;
        }

        /**
         * Получить свойства объекта в виде массива
         *
         * @param bool $dbKeys                Переименовать ключи, к исходному виду БД
         * @param bool $includeWriteProtected Включать в массив защищенные от записи поля
         * @param bool $camelCaseKeys         Вернуть ключи в camelCase
         * @param bool $jsonDateTimeFormat    Формат даты для json
         *
         * @return array
         * @throws \Exception
         */
        private function getArray(
            bool $dbKeys = false,
            bool $includeWriteProtected = true,
            $camelCaseKeys = false,
            $jsonDateTimeFormat = false
        ): array {
            $result     = [];
            $renameList = [];
            if ($dbKeys) {
                $renameList = array_flip($this->renameProps);
            }
            $columnList = self::getColumnsInfo(static::TABLE);
            foreach (self::getColumnsInfo(static::TABLE) as $prop => $info) {
                if (!$includeWriteProtected && in_array($prop, static::WRITE_PROTECTION, true)) {
                    continue;
                }
                // Переименовываем ключи в соответствии с правилами, если такие заданы
                $key = $prop;
                if ($dbKeys && empty($renameList[$prop]) === false) {
                    $key = $renameList[$prop];
                }
                if ($camelCaseKeys) {
                    // Преобразование имен свойств в camelCase
                    $key = SystemFunctions::stringToCamelCase($key);
                }
                if (empty($this->$prop) === false && $jsonDateTimeFormat && $info['type'] === self::COLUMN_TYPE_DATETIME) {
                    $this->$prop = (new DateTime($this->$prop))->format(self::JSON_DATE_TIME_FORMAT);
                }
                if (property_exists($this, $prop)) {
                    $result[$key] = $this->$prop;
                }
            }
            return $result;
        }

        /**
         * Получить массив для вывода в JSON
         *
         * @return array Массив для вывода в JSON
         * @throws \Exception
         */
        public function getJsonArray(): array
        {
            return $this->getArray(false, true, true, true);
        }

        /**
         * Получить коллекцию элементов по фильтру
         *
         * @param array $filter Фильтр выборки
         *
         * @return ModelCollection
         * @throws CoreException
         */
        public static function select(array $filter): ModelCollection
        {
            /** @var DB $db */
            $db   = DB::getInstance();
            $result          = $db->getItems(static::TABLE, $filter);
            $collectionClass = static::COLLECTION;
            $collection      = new $collectionClass();
            foreach ($result as $element) {
                $object = new static($element);
                $collection->add($object);
            }
            return $collection;
        }

        /**
         * Получить отдельный элемент по ID
         *
         * @param int $id ID элемента
         *
         * @return static|null Объект текущего класса с заполненными данными
         * @throws CoreException
         */
        public static function getItem(int $id): ?self
        {
            /** @var DB $db */
            $db   = DB::getInstance();
            $data = $db->getItem(static::TABLE, [static::ID_NAME => $id]);
            if (empty($data)) {
                return null;
            }
            return new static($data);
        }

        /**
         * Сериализация в JSON
         *
         * @return array Данные для json
         * @throws \Exception
         */
        public function jsonSerialize(): array
        {
            return $this->getArray(false, true, true, true);
        }

        /**
         * Санитизация и проверка значений перед формированием запроса в БД
         *
         * @param array $array Входные данные
         *
         * @return array Результат
         * @throws CoreException
         */
        private function sanitizeFields(array $array): array
        {
            $columnsInfo = self::getColumnsInfo(static::TABLE);
            $result      = [];
            foreach ($array as $key => $value) {
                if (empty($columnsInfo[$key])) {
                    // Если такого столбца нет, то и смысла добавлять его нет
                    continue;
                }
                switch ($columnsInfo[$key]['type']) {
                    case self::COLUMN_TYPE_ENUM:
                        if (in_array($value, $columnsInfo[$key]['enumValues'], true) === false) {
                            throw new CoreException('Недопустимое значение для enum поля ' . $key);
                        }
                        $result[$key] = $value;
                        break;
                    case self::COLUMN_TYPE_BOOL:
                        if (is_bool($value) === false) {
                            throw new CoreException('Значение ' . $key . ' должно быть булевым');
                        }
                        $result[$key] = $value;
                        break;
                    case self::COLUMN_TYPE_STRING:
                        if (is_string($value) === false) {
                            throw new CoreException('Значение ' . $key . ' должно быть строкой');
                        }
                        $result[$key] = mb_substr($value, 0, $columnsInfo[$key]['length']);
                        break;
                    case self::COLUMN_TYPE_INT:
                        if (is_numeric($value) === false) {
                            throw new CoreException('Значение ' . $key . ' должно быть целочисленным числом');
                        }
                        $result[$key] = (int)$value;
                        break;
                    case self::COLUMN_TYPE_DATETIME:
                        $date = strtotime($value);
                        if ($date === false) {
                            throw new CoreException($key . ' имеет некорректное значение даты');
                        }
                        $result[$key] = date(self::DATE_TIME_FORMAT, $date);
                        break;
                    case self::COLUMN_TYPE_JSON:
                        try {
                            json_decode($value, false, 512, JSON_THROW_ON_ERROR);
                        } catch (Throwable $exception) {
                            throw new CoreException($key . ' содержит некорректный json');
                        }
                        $result[$key] = $value;
                        break;
                }
            }
            return $result;
        }
    }
