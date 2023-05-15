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

    /**
     * Класс для управления удаленными хостами
     *
     * @version 1.0.1
     * @author  Roman Grinko <rsgrinko@gmail.com>
     */

    namespace Core\ExternalServices;

    use Core\CoreException;
    use Core\DataBases\DB;

    class RemoteHosts
    {
        private const TABLE = 'remote_hosts';

        private ?int  $selectedHostId;

        private array $selectedHostParams = [];

        /** @var string $method Метод */
        private string $method;

        /** @var array $params Параметры */
        private array $params = [];

        /** @var int $httpCode Код ответа */
        private int $httpCode = 0;

        /** @var array $response Ответ */
        private array $response = [];


        public function __construct()
        {
        }

        /**
         * @throws CoreException
         */
        public static function getHosts(string $limit = '10', string $sort = 'ASC'): array
        {
            /** @var DB $DB Объект БД */
            $DB  = DB::getInstance();
            $res = $DB->query('SELECT * FROM `' . self::TABLE . '` ORDER BY `id` ' . $sort . ' LIMIT ' . $limit);
            return $res ?? [];
        }

        public static function getAllCount(): int
        {
            /** @var DB $DB Объект БД */
            $DB  = DB::getInstance();
            $res = $DB->query('SELECT count(id) as count FROM ' . self::TABLE);
            if ($res !== null) {
                return (int)$res[0]['count'];
            }
            return 0;
        }

        public function getHostData(): array
        {
            /** @var DB $DB Объект БД */
            $DB  = DB::getInstance();
            $res = $DB->getItem(self::TABLE, ['id' => $this->selectedHostId]);
            if ($res !== null) {
                return $res;
            }
            return [];
        }

        public function selectHost(int $id): self
        {
            /** @var DB $DB Объект БД */
            $DB                       = DB::getInstance();
            $this->selectedHostParams = $DB->getItem(self::TABLE, ['id' => $id]);
            $this->selectedHostId     = $id;
            return $this;
        }

        private function sendRequest()
        {
            $queryFields = array_merge(['cmd' => $this->method], $this->params);

            $ch = curl_init($this->selectedHostParams['url']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $queryFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $res            = curl_exec($ch);
            $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $result = json_decode($res, true);
            if (!empty($result)) {
                $this->response = $result;
            } else {
                $this->response = [];
            }
        }

        /**
         * Установка метода
         *
         * @param string $method Метод
         *
         * @return self
         */
        public function setMethod(string $method): self
        {
            $this->method = $method;
            return $this;
        }

        /**
         * Установка параметров
         *
         * @param array $params Параметры
         *
         * @return self
         */
        public function setParams(array $params): self
        {
            $this->params = $params;
            return $this;
        }

        /**
         * Выполнить
         *
         * @return $this
         */
        public function execute(): self
        {
            $this->sendRequest();
            return $this;
        }

        /**
         * Получить ответ
         *
         * @return array
         */
        public function getResponse(): array
        {
            return $this->response;
        }

        /**
         * Получить результат выполнения
         *
         * @return mixed
         */
        public function getResponseData()
        {
            return $this->response['data'];
        }

        /**
         * Получить код ответа
         *
         * @return int
         */
        public function getHttpCode(): int
        {
            return $this->httpCode;
        }

        /**
         * Проверка площадки на доступность
         */
        public function isOnline(): bool
        {
            $this->method = 'ping';
            $this->execute();
            return $this->getResponseData() === 'pong';
        }

        /**
         * Выполнение произвольного кода
         *
         * @param string $code Код
         */
        public function eval(string $code)
        {
            $this->method = 'eval';
            $this->params = [
                'evalString' => base64_encode($code),
            ];
            $this->execute();
            return $this->getResponseData();
        }

        /**
         * Выполнение произвольного кода
         */
        public function ping()
        {
            $this->method = 'ping';
            $this->execute();
            return $this->getResponseData();
        }

        /**
         * Получение информации о площадке
         */
        public function getHostInfo()
        {
            $this->method = 'getHostInfo';
            $this->execute();
            return $this->getResponseData();
        }

        /**
         * Получение hostname площадки
         */
        public function getHostname()
        {
            $this->method = 'getHostInfo';
            $this->execute();
            return $this->getResponseData()['hostname'];
        }

        /**
         * Получение uptime площадки
         */
        public function getUptime()
        {
            $this->method = 'getHostInfo';
            $this->execute();
            return $this->getResponseData()['uptime'];
        }

        /**
         * Получение uname площадки
         */
        public function getUname()
        {
            $this->method = 'getHostInfo';
            $this->execute();
            return $this->getResponseData()['uname'];
        }

    }
