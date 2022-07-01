<?php

    namespace Core\Models;

    use Core\Models\DB;
    use Core\Helpers\Log;

    class MQ
    {
        const TABLE = 'threads';

        /**
         * @var DB|null $DB Объект базы
         */
        private $DB = null;

        public function __construct()
        {
            $this->DB = DB::getInstance();
        }

        public static function test($name, $age): int
        {
            return Log::logToFile('Тестирование сработало видимо.', 'MQ_test.log', func_get_args());
        }


        public function add($class, $method, $params): ?int
        {
            return $this->DB->addItem(
                self::TABLE, [
                               'active'      => 'N',
                               'in_progress' => 'N',
                               'attempts'    => '0',
                               'class'       => str_replace('\\', '\\\\', $class),
                               'method'      => $method,
                               'params'      => json_encode($params, JSON_UNESCAPED_UNICODE),
                           ]
            );
        }

        public function execute(int $taskId)
        {
            $arTask = $this->DB->getItem(self::TABLE, ['id' => $taskId]);
            $arTask['class'] = str_replace('\\\\', '\\', $arTask['class']);
            $arTask['params'] = json_decode($arTask['params'], true);
            $this->DB->update(
                self::TABLE, ['id' => $taskId], [
                               'active'      => 'Y',
                               'in_progress' => 'Y',
                               'attempts'    => ((int)$arTask['attempts'] + 1),
                           ]
            );

            try {

                $result = call_user_func_array($arTask['class'] . '::' . $arTask['method'], $arTask['params']);
                //$this->DB->remove(self::TABLE, ['id' => $taskId]);
                $this->DB->update(
                    self::TABLE, ['id' => $taskId], [
                                   'active' => 'N',
                                   'in_progress' => 'N',
                                   'executed' => 'Y',
                                   'response' => json_encode($result, JSON_UNESCAPED_UNICODE),
                               ]
                );
            } catch (\Throwable $t) {
                $this->DB->update(
                    self::TABLE, ['id' => $taskId], [
                                   'active'      => 'N',
                                   'in_progress' => 'N',
                                   'response'    => $t->getMessage(),
                               ]
                );
                echo $t->getMessage();
            }
        }
    }