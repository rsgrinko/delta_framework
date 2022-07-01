<?php

    namespace Core\Models;

    use Core\Models\DB;
    use Core\Helpers\Log;

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
                $result = call_user_func_array($arTask['class'] . '::' . $arTask['method'], $arTask['params']);
                //$this->DB->remove(self::TABLE, ['id' => $taskId]);
                $this->DB->update(
                    self::TABLE, ['id' => $taskId], [
                                   'active'      => self::VALUE_N,
                                   'in_progress' => self::VALUE_N,
                                   'executed'    => self::VALUE_Y,
                                   'status'      => self::STATUS_OK,
                                   'response'    => json_encode($result, JSON_UNESCAPED_UNICODE),
                               ]
                );
            } catch (\Throwable $t) {
                $this->DB->update(
                    self::TABLE, ['id' => $taskId], [
                                   'active'      => 'N',
                                   'in_progress' => 'N',
                                   'status'      => self::STATUS_ERROR,
                                   'response'    => $t->getMessage(),
                               ]
                );
                $executeStatus = false;
                echo $t->getMessage();
            }

            return $executeStatus;
        }
    }