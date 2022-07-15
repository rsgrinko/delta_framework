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
    use Core\Models\{DB, MQResponse};
    use Core\Helpers\{SystemFunctions, Log};

    class MQ
    {
        /**
         * Таблица заданий
         */
        const TABLE = 'threads';

        /**
         * Таблица истории заданий
         */
        const TABLE_HISTORY = 'threads_history';

        /**
         * Формат даты и времени
         */
        const DATETIME_FORMAT = 'Y-m-d H:i:s';

        /**
         * Имя файла логов
         */
        const LOG_FILE = 'MQ.log';

        /**
         * Статус успешно выполненного задания
         */
        const STATUS_OK = 'OK';

        /**
         * Статус неудачно выполненного задания
         */
        const STATUS_ERROR = 'ERROR';

        /**
         * Статус занято
         */
        const STATUS_BUSY = 'BUSY';

        /**
         * Значение ДА
         */
        const VALUE_Y = 'Y';

        /**
         * Значение НЕТ
         */
        const VALUE_N = 'N';

        /**
         * Количество задач для обработки
         */
        const EXECUTION_TASKS_LIMIT = 250;

        /**
         * Лимит количества запущенных воркеров
         */
        const WORKERS_LIMIT = 10;

        /**
         * @var DB|null $DB Объект базы
         */
        private $DB = null;

        /**
         * Приоритет по умолчанию
         */
        const DEFAULT_PRIORITY = 5;

        /**
         * Попыток выполнения по умолчанию
         */
        const DEFAULT_ATTEMPTS = 1;

        /**
         * Время, после которого задача считается повисшей (5 минут)
         */
        const STUCK_TIME = 60 * 10;

        /**
         * @var null $priority Приоритет задания
         */
        private $priority = null;

        /**
         * @var null $runNow Флаг немедленного запуска
         */
        private $runNow = false;

        /**
         * @var bool $useCheckDuplicates Флаг проверки задания на дубликат
         */
        private $useCheckDuplicates = true;

        /**
         * @var null $attempts Количество попыток выполнения задания
         */
        private $attempts = self::DEFAULT_ATTEMPTS;

        /**
         * Конструктор
         */
        public function __construct()
        {
            $this->DB = DB::getInstance();
        }

        /**
         * Установка режима немедленного выполнения задания
         *
         * @param bool $runNow Флаг немедленного выполнения
         *
         * @return $this
         */
        public function setRunNow(bool $runNow = true): self
        {
            $this->runNow = $runNow;
            return $this;
        }

        /**
         * Установка режима проверки задания на дубликат при создании
         *
         * @param bool useCheckDuplicates Флаг проверки задания на дубликат
         *
         * @return $this
         */
        public function setCheckDuplicates(bool $useCheckDuplicates = true): self
        {
            $this->useCheckDuplicates = $useCheckDuplicates;
            return $this;
        }

        /**
         * Установка количества попыток выполнения задания
         *
         * @param int $attempts Количество попыток
         *
         * @return $this
         */
        public function setAttempts(int $attempts = self::DEFAULT_PRIORITY): self
        {
            $this->attempts = $attempts;
            return $this;
        }

        /**
         * Тестовая функция 50/50
         *
         * @param mixed ...$params
         *
         * @return string
         * @throws CoreException
         */
        public static function test2(...$params): string
        {
            sleep(rand(1, 5));
            $result = rand(0, 100);
            if (($result % 2) == 0) {
                throw new CoreException('Сервер обмена временно недоступен');
            }
            return 'Пользователь с ID ' . $params['0'] . ' обновлен';
        }

        /**
         * Тестовая функция с управлением
         *
         * @param mixed ...$params
         *
         * @return string
         * @throws CoreException
         */
        public static function testManaged(...$params): string
        {
            sleep(rand(1, 3));
            $status = true;
            if (file_exists(ROOT_PATH . '/allfail.txt')) {
                $data   = file_get_contents(ROOT_PATH . '/allfail.txt');
                $status = (int)$data === 1;
            }
            if (!$status) {
                throw new CoreException('Сервер обмена временно недоступен');
            }
            return 'Клиент с ID ' . $params['0'] . ' создан';
        }

        /**
         * Тестовая функция ok
         *
         * @param mixed ...$params
         *
         * @return string
         * @throws CoreException
         */
        public static function testOk(...$params): string
        {
            sleep(rand(1, 3));
            return 'Задание успешно выполнено';
        }

        /**
         * Тестовая функция fail
         *
         * @param mixed ...$params
         *
         * @return string
         * @throws CoreException
         */
        public static function testFail(...$params): string
        {
            sleep(rand(1, 3));
            throw new CoreException('Сервер обменов недоступен');
        }

        /**
         * Преобразование данных в JSON строку
         *
         * @param $arData Данные
         *
         * @return string|null
         */
        private function convertToJson($arData): ?string
        {
            if (empty($arData)) {
                $arData = [];
            }
            return json_encode($arData, JSON_UNESCAPED_UNICODE);
        }

        /**
         * Преобразование из JSON строки в данные
         *
         * @param string $json Данные
         *
         * @return string
         */
        private function convertFromJson(?string $json): array
        {
            if (empty($json)) {
                return [];
            }
            return json_decode($json, true);
        }

        /**
         * Выборка активных невыполненных заданий из очереди
         *
         * @return int
         */
        private function getActiveTasksCount(): int
        {
            return (int)$this->DB->query(
                'SELECT count(id) AS count FROM ' . self::TABLE . ' WHERE active="' . self::VALUE_Y . '"'
            )[0]['count'];
        }

        /**
         * Выборка активных заданий из очереди
         *
         * @return array|null
         * @throws CoreException
         */
        private function getActiveTasks(): ?array
        {
            return $this->DB->getItems(
                self::TABLE, [
                'active'      => self::VALUE_Y,
                'in_progress' => self::VALUE_N,
            ],  [
                    'priority' => 'ASC',
                ]
            );
        }

        /**
         * Помечает задания активными с ограничением по количеству
         *
         * @return void
         * @throws CoreException
         */
        private function setTasksActiveStatus(): void
        {
            $countTasks = $this->getActiveTasksCount();

            // Если активных задач меньше чем возможно
            if ($countTasks < self::EXECUTION_TASKS_LIMIT) {
                // Вычисляем сколько заданий требуется докинуть
                $num     = self::EXECUTION_TASKS_LIMIT - $countTasks;
                $arTasks = $this->DB->query(
                    'SELECT id FROM ' . self::TABLE . ' WHERE active="' . self::VALUE_N . '" AND in_progress="' . self::VALUE_N
                    . '" ORDER BY priority ASC LIMIT ' . $num
                );
                if (!empty($arTasks)) {
                    $arTaskIds = [];
                    foreach ($arTasks as $task) {
                        $arTaskIds[] = $task['id'];
                    }
                    if (USE_LOG) {
                        Log::logToFile(
                            'Взято в работу заданий ' . count($arTaskIds),
                            self::LOG_FILE,
                            ['added' => count($arTaskIds), 'before' => $countTasks],
                            LOG_DEBUG
                        );
                    }
                    $this->DB->query('UPDATE ' . self::TABLE . ' SET active="' . self::VALUE_Y . '" WHERE id IN (' . implode(',', $arTaskIds) . ')');
                }
            }
        }

        /**
         * Запуск диспетчера очереди
         *
         * @return void
         * @throws CoreException
         */
        public function run(): void
        {
            if (USE_LOG) {
                Log::logToFile(
                    'Запущен новый воркер ' . $this->getCountWorkers() . '/' . self::WORKERS_LIMIT,
                    self::LOG_FILE,
                    [],
                    LOG_DEBUG
                );
            }
            // Поиск и исправление зависших заданий
            $this->searchAndFixStuckTasks();

            // Добор заданий
            $this->setTasksActiveStatus();

            if ($this->hasMaxWorkers()) {
                if (USE_LOG) {
                    Log::logToFile(
                        'Достигнуто максимальное количество воркеров. Работает ' . $this->getCountWorkers() . '/' . self::WORKERS_LIMIT,
                        self::LOG_FILE,
                        [],
                        LOG_DEBUG
                    );
                }
                return;
            }
            $arTasks = $this->getActiveTasks();

            if (!empty($arTasks)) {
                if (USE_LOG) {
                    Log::logToFile(
                        'Запущено выполнение заданий из очереди',
                        self::LOG_FILE,
                        ['count' => count($arTasks)],
                        LOG_DEBUG
                    );
                }
                foreach ($arTasks as $task) {
                    $this->execute($task['id']);
                }
            }
        }

        /**
         * Установка приоритета задания
         *
         * @param int $priority Приоритет
         *
         * @return MQ
         */
        public function setPriority(int $priority): self
        {
            $this->priority = $priority;
            return $this;
        }

        /**
         * Очистка очереди
         *
         * @return void
         * @throws CoreException
         */
        public function clearThreads(): void
        {
            $this->DB->query('DELETE FROM ' . self::TABLE);
        }

        /**
         * Очистка истории очереди
         *
         * @return void
         * @throws CoreException
         */
        public function clearThreadsHistory(): void
        {
            $this->DB->query('DELETE FROM ' . self::TABLE_HISTORY);
        }

        /**
         * Массовое создание заданий
         *
         * @param int         $count  Количество заданий
         * @param string|null $class  Класс
         * @param string      $method Метод класса
         * @param ?array      $params Массив параметров
         *
         * @return \Core\Models\MQResponse
         * @throws CoreException
         */
        public function massCreateTasks(int $count = 1, ?string $class = null, string $method, ?array $params = null): MQResponse
        {
            if (empty($params)) {
                $params = [];
            }
            if (!empty($this->priority)) {
                $priority = $this->priority;
            } else {
                $priority = self::DEFAULT_PRIORITY;
            }

            $params = $this->convertToJson($params);

            if (USE_LOG) {
                Log::logToFile(
                    'Запущено массовое создание заданий',
                    self::LOG_FILE,
                    func_get_args(),
                    LOG_DEBUG
                );
            }


            $arData = [];
            for ($i = 0; $i < $count; $i++) {
                $arData[] = [
                    'active'         => self::VALUE_N,
                    'in_progress'    => self::VALUE_N,
                    'attempts'       => '0',
                    'attempts_limit' => $this->attempts,
                    'class'          => !empty($class) ? addslashes($class) : '',
                    'method'         => $method,
                    'priority'       => $priority,
                    'params'         => $params,
                ];
            }

            $this->DB->addItems(self::TABLE, $arData);

            $this->priority = null;

            $response = new MQResponse();
            $response->setTaskId(0)->setStatus(self::STATUS_OK)->setParams(self::convertFromJson($params))->setParamsJson($params)->setResponse(
                $count . ' tasks were created'
            );

            if (USE_LOG) {
                Log::logToFile(
                    'Массовое создание заданий завершено',
                    self::LOG_FILE,
                    func_get_args(),
                    LOG_DEBUG
                );
            }

            return $response;
        }

        /**
         * Добавление задания в очередь
         *
         * @param string|null $class  Класс
         * @param string      $method Метод класса
         * @param ?array      $params Массив параметров
         *
         * @return \Core\Models\MQResponse Идентификатор созданного задания
         * @throws CoreException
         */
        public function createTask(?string $class = null, string $method, ?array $params = null): MQResponse
        {
            if (empty($params)) {
                $params = [];
            }
            $params = $this->convertToJson($params);

            if ($this->useCheckDuplicates === true
                && $this->checkDuplicates($class, $method, $params)) {
                throw new CoreException('Попытка создания дубликата задания', CoreException::ERROR_DIPLICATE_TASK);
            }

            if (USE_LOG) {
                Log::logToFile(
                    'Добавлено новое задание в очередь',
                    self::LOG_FILE,
                    func_get_args(),
                    LOG_DEBUG
                );
            }
            if (!empty($this->priority)) {
                $priority       = $this->priority;
                $this->priority = null;
            } else {
                $priority = self::DEFAULT_PRIORITY;
            }


            $taskId = $this->DB->addItem(
                self::TABLE, [
                               'active'         => self::VALUE_N,
                               'in_progress'    => self::VALUE_N,
                               'attempts'       => '0',
                               'attempts_limit' => $this->runNow ? '1' : $this->attempts, // Если запускаем немедленно, то попытка одна
                               'class'          => !empty($class) ? $class : '',
                               'method'         => $method,
                               'priority'       => $priority,
                               'params'         => $params,
                           ]
            );

            if ($this->runNow) {
                return $this->execute($taskId);
            }

            $response = new MQResponse();
            $response->setTaskId($taskId)->setStatus(self::STATUS_OK)->setParams(self::convertFromJson($params))->setParamsJson($params)->setResponse(
                'Task ' . $taskId . ' created'
            );


            return $response;
        }

        /**
         * Удаления задания из очереди
         *
         * @param int|null $taskId Идентификатор задания
         *
         * @throws CoreException
         */
        public function removeTask(?int $taskId = null): bool
        {
            if (empty($taskId)) {
                throw new CoreException('Не передан идентификатор задания');
            }
            return $this->DB->remove(self::TABLE, ['id' => $taskId]);
        }

        /**
         * Проверка задания на дубликат
         *
         * @param string|null $class  Класс
         * @param string      $method Метод класса
         * @param string      $params Json параметры
         *
         * @return bool
         * @throws CoreException
         */
        private function checkDuplicates(?string $class, string $method, string $params): bool
        {
            $count = $this->DB->query(
                'SELECT count(id) as count FROM ' . self::TABLE . ' WHERE class="' . addslashes($class) . '" and method="' . $method
                . '" and params="' . addslashes($params) . '"'
            )[0]['count'];
            return ($count > 0);
        }

        /**
         * Выполнение конкретного задания
         *
         * @param int $taskId Идентификатор задания
         *
         * @return \Core\Models\MQResponse Флаг результата выполнения задания
         * @throws CoreException
         */
        public function execute(int $taskId): MQResponse
        {
            $arTask = $this->DB->getItem(self::TABLE, ['id' => $taskId]);

            if (USE_LOG) {
                Log::logToFile(
                    'Задание ' . $taskId . ' взято в работу',
                    self::LOG_FILE,
                    ['taskId' => $taskId],
                    LOG_DEBUG
                );
            }

            $response = new MQResponse();
            $response->setTaskId($taskId);

            if (empty($arTask)) {
                // Если задание не найдено
                $response->setStatus(self::STATUS_ERROR)->setResponse('Task ' . $taskId . ' not found');

                if (USE_LOG) {
                    Log::logToFile(
                        'Задания ' . $taskId . ' не найдено',
                        self::LOG_FILE,
                        ['taskId' => $taskId],
                        LOG_ERR
                    );
                }

                return $response;
            }

            $response->setParamsJson($arTask['params']);
            $arTask['params'] = $this->convertFromJson($arTask['params']);
            $response->setParams($arTask['params']);

            if ($arTask['in_progress'] === self::VALUE_Y) {
                // Данное задание уже выполняется другим воркером
                $response->setStatus(self::STATUS_BUSY)->setResponse('Task ' . $taskId . ' already work');

                if (USE_LOG) {
                    Log::logToFile(
                        'Задание ' . $taskId . ' уже выполняется другим воркером',
                        self::LOG_FILE,
                        ['taskId' => $taskId],
                        LOG_DEBUG
                    );
                }

                return $response;
            }

            // Увеличиваем количество попыток выполнения
            $arTask['attempts'] = ((int)$arTask['attempts'] + 1);


            $this->DB->update(
                self::TABLE, ['id' => $taskId], [
                               'active'       => self::VALUE_Y,
                               'in_progress'  => self::VALUE_Y,
                               'date_updated' => date(self::DATETIME_FORMAT),
                               'attempts'     => $arTask['attempts'],
                           ]
            );

            try {
                $startTime = microtime(true);

                if (empty($arTask['method'])) {
                    throw new CoreException('Метод не задан, невозможно выполнить', CoreException::ERROR_CLASS_OR_METHOD_NOT_FOUND);
                }

                if (!empty($arTask['class']) && !method_exists($arTask['class'], $arTask['method'])) {
                    throw new CoreException('Класс или метод не существует, невозможно выполнить', CoreException::ERROR_CLASS_OR_METHOD_NOT_FOUND);
                }

                if (empty($arTask['class']) && !empty($arTask['method']) && !function_exists($arTask['method'])) {
                    throw new CoreException('Функция не существует, невозможно выполнить', CoreException::ERROR_CLASS_OR_METHOD_NOT_FOUND);
                }
                if (empty($arTask['class'])) {
                    $result = call_user_func_array($arTask['method'], $arTask['params']);
                } else {
                    $result = call_user_func_array($arTask['class'] . '::' . $arTask['method'], $arTask['params']);
                }

                $endTime = round(microtime(true) - $startTime, 4);

                $this->DB->update(
                    self::TABLE, ['id' => $taskId], [
                                   'active'         => self::VALUE_N,
                                   'in_progress'    => self::VALUE_N,
                                   'execution_time' => $endTime,
                                   'status'         => self::STATUS_OK,
                                   'date_updated'   => date(self::DATETIME_FORMAT),
                                   'response'       => addslashes($this->convertToJson($result)),
                               ]
                );
                $response->setStatus(self::STATUS_OK)->setExecutionTime($endTime)->setResponse($this->convertToJson($result));
                $this->saveTaskToHistory($taskId);

                if (USE_LOG) {
                    Log::logToFile(
                        'Выполнено задание с ID ' . $taskId . ', попытка ' . $arTask['attempts'] . ' из ' . $arTask['attempts_limit'],
                        self::LOG_FILE,
                        ['response' => $this->convertToJson($result)],
                        LOG_DEBUG
                    );
                }
            } catch (\Throwable|CoreException $t) {
                $endTime = round(microtime(true) - $startTime, 4);

                $this->DB->update(
                    self::TABLE, ['id' => $taskId], [
                                   'active'         => self::VALUE_Y, // Не снимаем активность пока не израсходуем все попытки
                                   'in_progress'    => self::VALUE_N,
                                   'execution_time' => $endTime,
                                   'date_updated'   => date(self::DATETIME_FORMAT),
                                   'status'         => self::STATUS_ERROR,
                                   'response'       => addslashes($t->getMessage()),
                               ]
                );


                if ($arTask['attempts'] >= (int)$arTask['attempts_limit']) {
                    // Если достигнуто максимальное количество попыток выполнения
                    $this->saveTaskToHistory($taskId);
                }

                $response->setStatus(self::STATUS_ERROR)->setExecutionTime($endTime)->setResponse($t->getMessage());

                if (USE_LOG) {
                    Log::logToFile(
                        'Ошибка выполнения задания с ID ' . $taskId . ', попытка ' . $arTask['attempts'] . ' из ' . $arTask['attempts_limit'],
                        self::LOG_FILE,
                        ['response' => json_encode($result, JSON_UNESCAPED_UNICODE)],
                        LOG_WARNING
                    );
                }
            }

            return $response;
        }

        /**
         * Сохранение выполненной задачи в историю
         *
         * @param int $taskId Идентификатор задачи
         *
         * @return int|null Идентификатор задачи в истории
         * @throws CoreException
         */
        private function saveTaskToHistory(int $taskId): ?int
        {
            $arTask = $this->DB->getItem(self::TABLE, ['id' => $taskId]);
            if (!empty($arTask)) {
                $taskHistoryId = $this->DB->addItem(
                    self::TABLE_HISTORY, [
                                           'task_id'        => $arTask['id'],
                                           'execution_time' => $arTask['execution_time'],
                                           'attempts'       => $arTask['attempts'],
                                           'date_created'   => $arTask['date_created'],
                                           'date_updated'   => $arTask['date_updated'],
                                           'class'          => addslashes($arTask['class']),
                                           'method'         => $arTask['method'],
                                           'params'         => $arTask['params'],
                                           'status'         => $arTask['status'],
                                           'response'       => $arTask['response'],
                                       ]
                );
                $this->removeTask($taskId);
                return $taskHistoryId;
            }


            return null;
        }


        /**
         * Получение списка всех заданий из очереди
         *
         * @param string $limit Лимит
         *
         * @return mixed|null
         * @throws CoreException
         */
        public function getTasks(string $limit = '10', string $orderBy = 'id', string $sort = 'DESC'): ?array
        {
            return $this->DB->query('SELECT * FROM ' . self::TABLE . ' ORDER BY ' . $orderBy . ' ' . $sort . ' LIMIT ' . $limit) ?? [];
        }

        /**
         * Получение списка истории заданий из очереди
         *
         * @param string $limit Лимит
         *
         * @return mixed|null
         * @throws CoreException
         */
        public function getTasksHistory(string $limit = '10', string $orderBy = 'id', string $sort = 'DESC'): ?array
        {
            return $this->DB->query('SELECT * FROM ' . self::TABLE_HISTORY . ' ORDER BY ' . $orderBy . ' ' . $sort . ' LIMIT ' . $limit) ?? [];
        }

        /**
         * Получение количества заданий в очереди
         *
         * @param array|null $filter Фильтр
         *
         * @return int
         * @throws CoreException
         */
        public function getCountTasks(?array $filter = null): int
        {
            $filterString = '';
            if (!empty($filter)) {
                $filterString = ' WHERE ' . $this->DB->createWhere($filter);
            }
            return (int)$this->DB->query('SELECT count(id) as count FROM ' . self::TABLE . $filterString)[0]['count'];
        }

        /**
         * Получение количества заданий в очереди
         *
         * @param array|null $filter Фильтр
         *
         * @return int
         */
        public function getCountTasksHistory(?array $filter = null): int
        {
            $filterString = '';
            if (!empty($filter)) {
                $filterString = ' WHERE ' . $this->DB->createWhere($filter);
            }
            return (int)$this->DB->query('SELECT count(id) as count FROM ' . self::TABLE_HISTORY . $filterString)[0]['count'];
        }

        /**
         * Получение количества запущенных воркеров
         *
         * @return int
         */
        public function getCountWorkers(): int
        {
            return (int)exec('ps -awx | grep \'threadsWorker.php\' | grep -v \'grep\' | wc -l');
        }

        /**
         * Принудительная остановка всех запущенных воркеров
         *
         * @return void
         */
        public function stopAllWorkers(): void
        {
            exec('pkill -f threadsWorker.php');
            $this->DB->update(self::TABLE, [], ['active' => 'N', 'in_progress' => 'N']);
        }


        /**
         * Проверка на максимальное количество работающих воркеров
         *
         * @return bool
         */
        private function hasMaxWorkers(): bool
        {
            return $this->getCountWorkers() > self::WORKERS_LIMIT;
        }

        /**
         * Поиск зависших заданий и возврат их в работу
         *
         * @return void
         * @throws CoreException
         */
        private function searchAndFixStuckTasks(): void
        {
            $arStuckTasks = [];
            $runTasks     = $this->DB->query(
                'SELECT * FROM ' . self::TABLE . ' WHERE active="' . self::VALUE_Y . '" AND in_progress="' . self::VALUE_Y . '"'
            );
            if (!empty($runTasks)) {
                foreach ($runTasks as $task) {
                    if ((!empty($task['date_updated']) && (time() - strtotime($task['date_updated'])) > self::STUCK_TIME) // Повисшие надолго
                        || empty($task['date_updated']) // Криво запущенные
                    ) {
                        $arStuckTasks[] = (int)$task['id'];
                        if (USE_LOG) {
                            Log::logToFile(
                                'Задание ' . $task['id'] . ' зависло',
                                self::LOG_FILE,
                                ['task' => json_encode($task, JSON_UNESCAPED_UNICODE)],
                                LOG_WARNING
                            );
                        }
                    }
                }

                if (!empty($arStuckTasks)) {
                    $this->DB->query(
                        'UPDATE ' . self::TABLE . ' SET active="' . self::VALUE_N . '", in_progress="' . self::VALUE_N . '" WHERE id in(' . implode(
                            ',',
                            $arStuckTasks
                        ) . ')'
                    );

                    sendTelegram('Задания были возвращены в работу (' . count($arStuckTasks) . ' шт)' . PHP_EOL . implode(', ', $arStuckTasks));

                    if (USE_LOG) {
                        Log::logToFile(
                            'Задания были возвращены в работу (' . count($arStuckTasks) . ')',
                            self::LOG_FILE,
                            ['tasks' => json_encode($arStuckTasks, JSON_UNESCAPED_UNICODE)],
                            LOG_DEBUG
                        );
                    }
                }
            }
        }
    }