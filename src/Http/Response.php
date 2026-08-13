<?php

    declare(strict_types=1);

    namespace Delta\Http;

    /**
     * HTTP-ответ.
     *
     * Экшен возвращает объект ответа, а не печатает в вывод и не убивает процесс.
     * Благодаря этому ответ можно проверить в тесте, изменить в middleware и отправить
     * ровно один раз — в точке входа.
     */
    class Response
    {
        /**
         * Конструктор
         *
         * @param string                $body    Тело ответа
         * @param int                   $status  HTTP-код
         * @param array<string, string> $headers Заголовки
         */
        public function __construct(
            protected string $body = '',
            protected int $status = 200,
            protected array $headers = [],
        ) {
        }

        /**
         * Ответ с телом HTML
         *
         * @param string $html   Разметка
         * @param int    $status HTTP-код
         *
         * @return self Ответ
         */
        public static function html(string $html, int $status = 200): self
        {
            return new self($html, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        /**
         * Ответ с телом JSON
         *
         * @param array $data   Данные
         * @param int   $status HTTP-код
         *
         * @return self Ответ
         */
        public static function json(array $data, int $status = 200): self
        {
            return new self(
                (string)json_encode($data, JSON_UNESCAPED_UNICODE),
                $status,
                ['Content-Type' => 'application/json; charset=UTF-8'],
            );
        }

        /**
         * Ответ-редирект
         *
         * @param string $location Адрес перехода
         * @param int    $status   HTTP-код
         *
         * @return self Ответ
         */
        public static function redirect(string $location, int $status = 302): self
        {
            return new self('', $status, ['Location' => $location]);
        }

        /**
         * Ответ «страница не найдена»
         *
         * @param string $message Текст
         *
         * @return self Ответ
         */
        public static function notFound(string $message = '404 - Page Not Found'): self
        {
            return new self($message, 404, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        /**
         * Тело ответа
         *
         * @return string Тело
         */
        public function body(): string
        {
            return $this->body;
        }

        /**
         * HTTP-код ответа
         *
         * @return int Код
         */
        public function status(): int
        {
            return $this->status;
        }

        /**
         * Заголовки ответа
         *
         * @return array<string, string> Заголовки
         */
        public function headers(): array
        {
            return $this->headers;
        }

        /**
         * Установить заголовок
         *
         * @param string $name  Имя заголовка
         * @param string $value Значение
         *
         * @return $this Ответ
         */
        public function withHeader(string $name, string $value): static
        {
            $this->headers[$name] = $value;

            return $this;
        }

        /**
         * Установить HTTP-код
         *
         * @param int $status Код
         *
         * @return $this Ответ
         */
        public function withStatus(int $status): static
        {
            $this->status = $status;

            return $this;
        }

        /**
         * Отправить ответ клиенту
         *
         * @return void
         */
        public function send(): void
        {
            if (headers_sent() === false) {
                http_response_code($this->status);
                foreach ($this->headers as $name => $value) {
                    header($name . ': ' . $value);
                }
            }

            echo $this->body;
        }
    }
