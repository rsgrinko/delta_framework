<?php

    declare(strict_types=1);

    namespace Delta\Http;

    /**
     * HTTP-запрос.
     *
     * Объект собирается из суперглобалей один раз на входе в приложение. Контроллеры и
     * middleware работают только с ним: это то, что делает их вызываемыми из теста без
     * веб-сервера и позволяет подставить любой запрос вручную.
     */
    final class Request
    {
        /** @var array Параметры маршрута и произвольные атрибуты, добавленные middleware */
        private array $attributes = [];

        /**
         * Конструктор
         *
         * @param string                       $method  HTTP-метод
         * @param string                       $path    Путь без строки запроса
         * @param array                        $query   Параметры строки запроса
         * @param array                        $request Параметры тела запроса
         * @param array<string, UploadedFile>  $files   Загруженные файлы
         * @param array                        $cookies Cookies
         * @param array                        $server  Параметры сервера
         */
        public function __construct(
            private readonly string $method = 'GET',
            private readonly string $path = '/',
            private readonly array $query = [],
            private readonly array $request = [],
            private readonly array $files = [],
            private readonly array $cookies = [],
            private readonly array $server = [],
        ) {
        }

        /**
         * Собрать запрос из суперглобалей
         *
         * @return self Объект запроса
         */
        public static function capture(): self
        {
            $files = [];
            foreach ($_FILES as $name => $file) {
                $uploaded = UploadedFile::fromArray($file);
                if ($uploaded !== null) {
                    $files[$name] = $uploaded;
                }
            }

            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

            return new self(
                strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')),
                is_string($path) && $path !== '' ? $path : '/',
                $_GET,
                $_POST,
                $files,
                $_COOKIE,
                $_SERVER,
            );
        }

        /**
         * HTTP-метод запроса
         *
         * @return string Метод
         */
        public function method(): string
        {
            return $this->method;
        }

        /**
         * Проверить HTTP-метод
         *
         * @param string $method Ожидаемый метод
         *
         * @return bool Признак совпадения
         */
        public function isMethod(string $method): bool
        {
            return $this->method === strtoupper($method);
        }

        /**
         * Признак POST-запроса
         *
         * @return bool Признак
         */
        public function isPost(): bool
        {
            return $this->isMethod('POST');
        }

        /**
         * Путь запроса без строки запроса
         *
         * @return string Путь
         */
        public function path(): string
        {
            return $this->path;
        }

        /**
         * Значение параметра строки запроса
         *
         * @param string $key     Имя параметра
         * @param mixed  $default Значение по умолчанию
         *
         * @return mixed Значение
         */
        public function query(string $key, mixed $default = null): mixed
        {
            return $this->query[$key] ?? $default;
        }

        /**
         * Значение параметра тела запроса
         *
         * @param string $key     Имя параметра
         * @param mixed  $default Значение по умолчанию
         *
         * @return mixed Значение
         */
        public function post(string $key, mixed $default = null): mixed
        {
            return $this->request[$key] ?? $default;
        }

        /**
         * Значение параметра из тела запроса либо строки запроса
         *
         * @param string $key     Имя параметра
         * @param mixed  $default Значение по умолчанию
         *
         * @return mixed Значение
         */
        public function input(string $key, mixed $default = null): mixed
        {
            return $this->request[$key] ?? $this->query[$key] ?? $default;
        }

        /**
         * Строковое значение параметра с обрезкой пробелов
         *
         * @param string $key     Имя параметра
         * @param string $default Значение по умолчанию
         *
         * @return string Значение
         */
        public function string(string $key, string $default = ''): string
        {
            $value = $this->input($key, $default);

            return is_scalar($value) ? trim((string)$value) : $default;
        }

        /**
         * Целочисленное значение параметра
         *
         * @param string $key     Имя параметра
         * @param int    $default Значение по умолчанию
         *
         * @return int Значение
         */
        public function integer(string $key, int $default = 0): int
        {
            $value = $this->input($key, $default);

            return is_numeric($value) ? (int)$value : $default;
        }

        /**
         * Все параметры запроса
         *
         * @return array Параметры
         */
        public function all(): array
        {
            return $this->request + $this->query;
        }

        /**
         * Загруженный файл
         *
         * @param string $key Имя поля
         *
         * @return UploadedFile|null Файл либо null
         */
        public function file(string $key): ?UploadedFile
        {
            return $this->files[$key] ?? null;
        }

        /**
         * Значение cookie
         *
         * @param string $key     Имя cookie
         * @param mixed  $default Значение по умолчанию
         *
         * @return mixed Значение
         */
        public function cookie(string $key, mixed $default = null): mixed
        {
            return $this->cookies[$key] ?? $default;
        }

        /**
         * Значение заголовка запроса
         *
         * @param string      $name    Имя заголовка
         * @param string|null $default Значение по умолчанию
         *
         * @return string|null Значение
         */
        public function header(string $name, ?string $default = null): ?string
        {
            $key = 'HTTP_' . str_replace('-', '_', strtoupper($name));

            return $this->server[$key] ?? $default;
        }

        /**
         * Признак AJAX-запроса
         *
         * @return bool Признак
         */
        public function isAjax(): bool
        {
            return $this->header('X-Requested-With') === 'XMLHttpRequest';
        }

        /**
         * IP-адрес клиента
         *
         * @return string IP-адрес
         */
        public function ip(): string
        {
            return (string)($this->server['REMOTE_ADDR'] ?? '');
        }

        /**
         * Получить атрибут запроса
         *
         * @param string $key     Имя атрибута
         * @param mixed  $default Значение по умолчанию
         *
         * @return mixed Значение
         */
        public function attribute(string $key, mixed $default = null): mixed
        {
            return $this->attributes[$key] ?? $default;
        }

        /**
         * Установить атрибут запроса
         *
         * @param string $key   Имя атрибута
         * @param mixed  $value Значение
         *
         * @return void
         */
        public function setAttribute(string $key, mixed $value): void
        {
            $this->attributes[$key] = $value;
        }
    }
