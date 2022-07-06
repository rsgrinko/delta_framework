<?php

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
         * @var $response Результат выполнения задания
         */
        private $response = null;

        /**
         * Конструктор
         */
        public function __construct()
        {
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
         * Получение статуса задания
         *
         * @return string|null
         */
        public function getStatus(): ?string
        {
            return $this->status;
        }

        /**
         * Установка текстового кода ответа внешней службы
         *
         * @param string $status Текстовый код ответа
         *
         * @return $this
         * @throws CoreException
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
         * @return Response
         */
        public function setResponse(?string $response = null): self
        {
            $this->response = $response;
            return $this;
        }


        /**
         * Установка массива парамтров задания
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
         * Установка массива парамтров задания
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
