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

    use Core\CoreException;

    class MQResponse
    {
        /**
         * Статус успешно выполненного задания
         */
        const STATUS_OK = 'OK';

        /**
         * Статус неудачно выполненного задания
         */
        const STATUS_ERROR = 'ERROR';

        /**
         * @var int $taskId Идентификатор задания
         */
        private $taskId = 0;

        /**
         * @var ?string $dateCreated Дата создания
         */
        private $dateCreated = null;

        /**
         * @var string $status Статус задания
         */
        private $status = '';

        /**
         * @var array|null $params Параметры задания
         */
        private $params = null;

        /**
         * @var string|null $paramsJson JSON параметры задания
         */
        private $paramsJson = null;

        /**
         * @var string|null $executionTime Время выполнения задания
         */
        private $executionTime = null;

        /**
         * @var string|null $response Результат выполнения задания
         */
        private $response = null;

        /**
         * Конструктор
         */
        public function __construct()
        {
            $this->dateCreated = date(MQ::DATETIME_FORMAT);
        }

        /**
         * Получение идентификатора задачи
         *
         * @return int
         */
        public function getTaskId(): int
        {
            return $this->taskId;
        }

        /**
         * Установка идентификатора задачи
         *
         * @param int $taskId Идентификатор задачи
         *
         * @return $this
         */
        public function setTaskId(int $taskId = 0): self
        {
            $this->taskId = $taskId;
            return $this;
        }

        /**
         * Получение времени выполнения задания
         *
         * @return ?string
         */
        public function getExecutionTime(): ?string
        {
            return $this->executionTime;
        }

        /**
         * Установка времени выполнения задания
         *
         * @param string|null $executionTime Время выполнения
         *
         * @return $this
         */
        public function setExecutionTime(?string $executionTime = null): self
        {
            $this->executionTime = $executionTime;
            return $this;
        }

        /**
         * Получение статуса задания
         *
         * @return string|null
         */
        public function getStatus(): ?string
        {
            return $this->status;
        }

        /**
         * Получение даты создания задания
         *
         * @return string|null
         */
        public function getDateCreated(): ?string
        {
            return $this->dateCreated;
        }

        /**
         * Получение параметров
         *
         * @return array|null
         */
        public function getParams(): ?array
        {
            return $this->params;
        }

        /**
         * Получение JSON параметров
         *
         * @return string|null
         */
        public function getParamsJson(): ?string
        {
            return $this->paramsJson;
        }

        /**
         * Установка текстового кода ответа внешней службы
         *
         * @param string $status Текстовый код ответа
         *
         * @return $this
         */
        public function setStatus(string $status): self
        {
            $status       = strtoupper($status);
            $this->status = $status;
            return $this;
        }


        /**
         * Получение текста запроса
         *
         * @return string|null
         */
        public function getResponse(): ?string
        {
            return $this->response;
        }

        /**
         * Установка текста запроса
         *
         * @param string|null $response Результат работы задания
         *
         * @return $this
         */
        public function setResponse(?string $response = null): self
        {
            $this->response = $response;
            return $this;
        }


        /**
         * Установка массива параметров задания
         *
         * @param array|null $params
         *
         * @return $this
         */
        public function setParams(?array $params): self
        {
            $this->params = $params;
            return $this;
        }

        /**
         * Установка массива параметров задания
         *
         * @param array $params
         *
         * @return $this
         */
        public function setParamsJson(?string $paramsJson): self
        {
            $this->paramsJson = $paramsJson;
            return $this;
        }

    }
