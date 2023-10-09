<?php

    /**
     * Copyright (c) 2023 Roman Grinko <rsgrinko@gmail.com>
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

    namespace Core\ExternalServices;
    
    /**
     * Класс запросов к внешним системам
     */
    class Request
    {
        /** @var string $baseUrl Базовый адрес */
        private string $baseUrl;

        /** @var int $httpCode Код ответа */
        private int $httpCode = 0;

        /** @var string $responseBody Тело ответа */
        private string $responseBody = '';

        /** @var string $responseHeader Заголовки ответа */
        private string $responseHeader = '';

        /** @var int[] Успешные коды ответов */
        private const SUCCESS_HTTP_CODES = [
            200,
            202,
            203,
            204,
            205,
            206,
            207,
            226,
        ];


        /**
         * Коструктор
         *
         * @param string $baseUrl Базовый адрес
         */
        public function __construct(string $baseUrl)
        {
            $this->baseUrl = $baseUrl;
            return $this;
        }

        /**
         * POST запрос
         *
         * @param array $data Данные
         *
         * @return $this
         */
        public function post(array $data = []): self
        {
            $ch = curl_init($this->baseUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, true);
            $result = curl_exec($ch);

            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $this->responseHeader = substr($result, 0, $headerSize);
            $this->responseBody   = substr($result, $headerSize);
            $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $this;
        }

        /**
         * Проверка запроса на успешность выполнения
         *
         * @return bool Результат
         */
        public function isSuccess(): bool
        {
            return in_array($this->httpCode, self::SUCCESS_HTTP_CODES, true);
        }

        /**
         * Получить код ответа
         *
         * @return int Код ответа
         */
        public function getHttpCode(): int
        {
            return $this->httpCode;
        }

        /**
         * Получить тело ответа
         *
         * @return string Тело ответа
         */
        public function getBody(): string
        {
            return $this->responseBody;
        }

        /**
         * Получить заголовки ответа
         *
         * @return string Заголовки ответа
         */
        public function getHeaders(): string
        {
            return $this->responseHeader;
        }

    }