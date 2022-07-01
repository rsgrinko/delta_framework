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
        const EXECUTION_TASKS_LIMIT = 10;

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
        public static function test2(): int
        {
            sleep(5);
            return Log::logToFile('Тестирование2', 'MQ_test.log', func_get_args());
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
                               'active'     => self::VALUE_Y,
                               'in_progres' => self::VALUE_N,
                               'executed'   => self::VALUE_N,
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
                foreach ($arTasks as $task) {
                    $this->execute($task['id']);
                    SystemFunctions::sendTelegram(
                        'Выполнено задание с ID ' . $task['id'] . PHP_EOL . $task['class'] . '::' . $task['method'] . '()' . PHP_EOL . print_r(
                            $task['params'],
                            true
                        )
                    );
                }
            }
        }

        /**
         * Добавление задания в очередь
         *
         * @param string $class  Класс
         * @param string $method Метод класса
         * @param ?array $params Массив параметров
         *
         * @return int|null Идентификатор созданного задания
         */
        public function add(string $class, string $method, ?array $params = null): ?int
        {
            if (empty($params)) {
                $params = [];
            }
            return $this->DB->addItem(
                self::TABLE, [
                               'active'      => self::VALUE_N,
                               'in_progress' => self::VALUE_N,
                               'attempts'    => '0',
                               'class'       => str_replace('\\', '\\\\', $class),
                               'method'      => $method,
                               'params'      => json_encode($params, JSON_UNESCAPED_UNICODE),
                           ]
            );
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
            $executeStatus    = true;
            $arTask           = $this->DB->getItem(self::TABLE, ['id' => $taskId]);
            $arTask['class']  = str_replace('\\\\', '\\', $arTask['class']);
            $arTask['params'] = json_decode($arTask['params'], true);
            $this->DB->update(
                self::TABLE, ['id' => $taskId], [
                               'active'      => self::VALUE_Y,
                               'in_progress' => self::VALUE_Y,
                               'attempts'    => ((int)$arTask['attempts'] + 1),
                           ]
            );


            try {
                if (!method_exists($arTask['class'], $arTask['method'])) {
                    throw new CoreException('Недействительный класс или метод для выполнения', CoreException::ERROR_CLASS_OR_METHOD_NOT_FOUND);
                }
                $result = call_user_func_array($arTask['class'] . '::' . $arTask['method'], $arTask['params']);
                //$this->DB->remove(self::TABLE, ['id' => $taskId]);
                $this->DB->update(
                    self::TABLE, ['id' => $taskId], [
                                   'active'       => self::VALUE_N,
                                   'in_progress'  => self::VALUE_N,
                                   'executed'     => self::VALUE_Y,
                                   'status'       => self::STATUS_OK,
                                   'date_updated' => date('Y-m-d H:i:s'),
                                   'response'     => json_encode($result, JSON_UNESCAPED_UNICODE),
                               ]
                );
            } catch (\Throwable|CoreException $t) {
                $this->DB->update(
                    self::TABLE, ['id' => $taskId], [
                                   'active'       => self::VALUE_N,
                                   'in_progress'  => self::VALUE_N,
                                   'executed'     => self::VALUE_Y,
                                   'date_updated' => date('Y-m-d H:i:s'),
                                   'status'       => self::STATUS_ERROR,
                                   'response'     => $t->getMessage(),
                               ]
                );
                $executeStatus = false;
                echo $t->getMessage();
            }

            return $executeStatus;
        }
    }