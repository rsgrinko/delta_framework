<?php

    namespace Core\Api;

    /**
     * Класс обработки запросов
     */
    class Request
    {
        /** @var string Метод POST */
        public const METHOD_POST = 'POST';

        /** @var string Метод GET */
        public const METHOD_GET = 'GET';

        /** @var array Свойства запроса */
        protected $properties = [];

        /** @var string Тип запроса */
        protected $method;

        /**
         * Конструктор
         */
        public function __construct()
        {
            $this->init();
        }

        /**
         * Получить строковое свойство
         *
         * @param string $key Имя свойства
         *
         * @return string|null Значение
         */
        public function getProperty(string $key): ?string
        {
            if (is_string($this->properties[$key]) === false) {
                return null;
            }
            return $this->properties[$key] ?? null;
        }

        /**
         * Получить параметры запроса в виде массива
         *
         * @return array Данные
         */
        public function getArray(): array
        {
            return $this->properties;
        }

        /**
         * Получить свойство-массив
         *
         * @param string $key Имя свойства
         *
         * @return array|null Значение
         */
        public function getArrayProperty(string $key): ?array
        {
            if (is_array($this->properties[$key]) === false) {
                return null;
            }
            return $this->properties[$key] ?? null;
        }

        /**
         * Проверка существования свойства
         *
         * @param string $key Имя свойство
         *
         * @return bool Результат проверки
         */
        public function isSetProperty(string $key): bool
        {
            return !empty($this->properties[$key]);
        }

        /**
         * Получить метод, которым был отправлен запрос
         *
         * @return string Метод, которым был отправлен запрос
         */
        public function getRequestMethod(): string
        {
            return $this->method;
        }

        /**
         * Инициализация объекта запроса
         */
        protected function init(): void
        {
            $this->method = $_SERVER['REQUEST_METHOD'];
            switch ($_SERVER['REQUEST_METHOD']) {
                case self::METHOD_POST:
                    $this->setProperties($_POST);
                    break;
                case self::METHOD_GET:
                    $this->setProperties($_GET);
                    break;
            }
        }

        /**
         * Установка свойств полей запроса
         *
         * @param array $array Входной массив данных
         */
        private function setProperties(array $array): void
        {
            array_walk_recursive(
                $array,
                static function (&$input) {
                    $input = strip_tags(trim($input));
                    if($input === 'undefined') {
                        $input = null;
                    }
                }
            );
            $this->properties = $array;
        }
    }
