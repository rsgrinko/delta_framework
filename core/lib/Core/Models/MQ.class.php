<?php

    namespace Core\Models;

    use Core\CoreException;
    use Core\Models\DB;
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
        const EXECUTION_TASKS_LIMIT = 100;

        /**
         * @var DB|null $DB Объект базы
         */
        private $DB = null;

        /**
         * Конструктор
         */
        public function __construct()
        {
            $this->DB = DB::getInstance();
        }

        /**
         * Тестовая функция
         *
         * @param $name
         * @param $age
         *
         * @return int
         * @throws \Core\CoreException
         */
        public static function test($name, $age): int
        {
            return Log::logToFile('Тестирование сработало видимо.', 'MQ_test.log', func_get_args());
        }

        /**
         * Тестовая функция 2
         *
         * @return int
         * @throws \Core\CoreException
         */
        public static function test2(): string
        {
            sleep(5);
            return 'Logged ' . Log::logToFile('Тестирование2', 'MQ_test.log', func_get_args()) . ' bytes';
        }

        /**
         * Выборка активных невыполненных заданий из очереди
         *
         * @return array|null
         */
        private function getActiveTasksCount(): int
        {
            return (int)$this->DB->query(
                'SELECT count(id) AS count FROM ' . self::TABLE . ' WHERE active="' . self::VALUE_Y . '" AND executed="' . self::VALUE_N . '"'
            )[0]['count'];
        }

        /**
         * Выборка активных заданий из очереди
         *
         * @return array|null
         */
        private function getActiveTasks(): ?array
        {
            return $this->DB->getItems(
                self::TABLE, [
                               'active'      => self::VALUE_Y,
                               'in_progress' => self::VALUE_N,
                               'executed'    => self::VALUE_N,
                           ]
            );
        }

        /**
         * Помечает задания активными с ограничением по количеству
         *
         * @return void
         */
        private function setTasksActiveStatus()
        {
            $countTasks = $this->getActiveTasksCount();

            // Если активных задач меньше чем возможно
            if ($countTasks < self::EXECUTION_TASKS_LIMIT) {
                // Вычисляем сколько заданий требуется докинуть
                $num     = self::EXECUTION_TASKS_LIMIT - $countTasks;
                $arTasks = $this->DB->query(
                    'SELECT id FROM ' . self::TABLE . ' WHERE active="' . self::VALUE_N . '" AND executed="' . self::VALUE_N . '" AND in_progress="'
                    . self::VALUE_N . '" LIMIT ' . $num
                );
                if (!empty($arTasks)) {
                    $arTaskIds = [];
                    foreach ($arTasks as $task) {
                        $arTaskIds[] = $task['id'];
                    }
                    Log::logToFile(
                        'Взято в работу заданий ' . count($arTaskIds), self::LOG_FILE, ['added' => count($arTaskIds), 'defore' => $countTasks]
                    );
                    $this->DB->query('UPDATE ' . self::TABLE . ' SET active="Y" WHERE id IN (' . implode(',', $arTaskIds) . ')');
                }
            }
        }

        /**
         * Запуск диспетчера очереди
         *
         * @return void
         */
        public function run()
        {
            $this->setTasksActiveStatus();
            $arTasks = $this->getActiveTasks();

            if (!empty($arTasks)) {
                Log::logToFile('Запущено выполнение заданий из очереди', self::LOG_FILE, ['count' => count($arTasks)]);
                foreach ($arTasks as $task) {
                    $this->execute($task['id']);
                }
            }
        }

        /**
         * Добавление задания в очередь
         *
         * @param string|null $class  Класс
         * @param string      $method Метод класса
         * @param ?array      $params Массив параметров
         *
         * @return int|null Идентификатор созданного задания
         * @throws CoreException
         */
        public function createTask(?string $class = null, string $method, ?array $params = null): ?int
        {
            if (empty($params)) {
                $params = [];
            }
            $params = json_encode($params, JSON_UNESCAPED_UNICODE);

            if ($this->checkDuplicates($class, $method, $params)) {
                throw new CoreException('Попытка создания дубликата задания', CoreException::ERROR_DIPLICATE_TASK);
            }

            Log::logToFile('Добавлено новое задание в очередь', self::LOG_FILE, func_get_args());

            return $this->DB->addItem(
                self::TABLE, [
                               'active'      => self::VALUE_N,
                               'in_progress' => self::VALUE_N,
                               'attempts'    => '0',
                               'class'       => !empty($class) ? addslashes($class) : '',
                               'method'      => $method,
                               'params'      => $params,
                           ]
            );
        }


        /**
         * Проверка задания на дубликат
         *
         * @param string|null $class  Класс
         * @param string      $method Метод класса
         * @param string      $params Json параметры
         *
         * @return bool
         */
        private function checkDuplicates(?string $class, string $method, string $params): bool
        {
            $count = $this->DB->query(
                'SELECT count(id) as count FROM ' . self::TABLE . ' WHERE executed="' . self::VALUE_N . '" AND class="' . addslashes($class)
                . '" and method="' . $method . '" and params="' . addslashes($params) . '"'
            )[0]['count'];
            return ($count > 0);
        }

        /**
         * Выполнение конкретного задания
         *
         * @param int $taskId Идентификатор задания
         *
         * @return bool Флаг результата выполнения задания
         */
        public function execute(int $taskId): bool
        {
            $executeStatus = true;
            $arTask        = $this->DB->getItem(self::TABLE, ['id' => $taskId]);
            if ($arTask['in_progress'] === self::VALUE_Y) {
                // Данное задание уже выполняется другим воркером
                SystemFunctions::sendTelegram('Задание ' . $taskId . ' уже выполняется другим воркером');
                return false;
            }

            $arTask['params'] = json_decode($arTask['params'], true);
            $this->DB->update(
                self::TABLE, ['id' => $taskId], [
                               'active'      => self::VALUE_Y,
                               'in_progress' => self::VALUE_Y,
                               'attempts'    => ((int)$arTask['attempts'] + 1),
                           ]
            );


            try {
                $startTime = microtime(true);

                if (empty($arTask['method'])) {
                    throw new CoreException('Метод не задан, невозможно выполнить', CoreException::ERROR_CLASS_OR_METHOD_NOT_FOUND);
                }

                if (!empty($arTask['class']) && !method_exists($arTask['class'], $arTask['method'])) {
                    throw new CoreException('Класс или метод не определен, невозможно выполнить', CoreException::ERROR_CLASS_OR_METHOD_NOT_FOUND);
                }

                if (empty($arTask['class']) && !empty($arTask['method']) && !function_exists($arTask['method'])) {
                    throw new CoreException('Функция не определена, невозможно выполнить', CoreException::ERROR_CLASS_OR_METHOD_NOT_FOUND);
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
                                   'executed'       => self::VALUE_Y,
                                   'execution_time' => $endTime,
                                   'status'         => self::STATUS_OK,
                                   'date_updated'   => date('Y-m-d H:i:s'),
                                   'response'       => json_encode($result, JSON_UNESCAPED_UNICODE),
                               ]
                );

                $this->saveExecutedTask($taskId);
            } catch (\Throwable|CoreException $t) {
                $endTime = round(microtime(true) - $startTime, 4);
                $this->DB->update(
                    self::TABLE, ['id' => $taskId], [
                                   'active'         => self::VALUE_N,
                                   'in_progress'    => self::VALUE_N,
                                   'executed'       => self::VALUE_Y,
                                   'execution_time' => $endTime,
                                   'date_updated'   => date('Y-m-d H:i:s'),
                                   'status'         => self::STATUS_ERROR,
                                   'response'       => $t->getMessage(),
                               ]
                );
                $executeStatus = false;
                echo $t->getMessage();
            }

            /*SystemFunctions::sendTelegram(
                'Выполнено задание с ID ' . $arTask['id'] . PHP_EOL . 'Время выполнения: ' . $endTime . ' cek.' . PHP_EOL . $arTask['class'] . '::'
                . $arTask['method'] . '()' . PHP_EOL . print_r(
                    $arTask['params'],
                    true
                )
            );*/


            return $executeStatus;
        }

        /**
         * @param int $taskId Идентификатор задачи
         *
         * @return int|null Идентификатор задачи в истории
         */
        private function saveExecutedTask(int $taskId): ?int
        {
            $arTask = $this->DB->getItem(self::TABLE, ['id' => $taskId]);

            $taskHistoryId = $this->DB->addItem(
                self::TABLE_HISTORY, [
                                       'task_id'        => $arTask['id'],
                                       'execution_time' => $arTask['execution_time'],
                                       'attempts'       => $arTask['attempts'],
                                       'date_created'   => $arTask['date_created'],
                                       'date_updated'   => $arTask['date_updated'],
                                       'class'          => $arTask['class'],
                                       'method'         => $arTask['method'],
                                       'params'         => $arTask['params'],
                                       'status'         => $arTask['status'],
                                       'response'       => $arTask['response'],
                                   ]
            );
            $this->DB->remove(self::TABLE, ['id' => $taskId]);

            return $taskHistoryId;
        }


        /**
         * Получение списка всех заданий из очереди
         *
         * @param string $limit Лимит
         *
         * @return mixed|null
         */
        public function getTasks(string $limit = '10'): ?array
        {
            return $this->DB->query('SELECT * FROM ' . self::TABLE . ' ORDER BY id DESC LIMIT ' . $limit) ?? [];
        }

        /**
         * Получение количества заданий в очереди
         *
         * @param array|null $filter Фильтр
         *
         * @return int
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
    }