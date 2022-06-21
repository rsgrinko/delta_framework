<?php

    namespace Core;

    class CoreException extends \Exception
    {
        /**
         * Некорректный идентификатор пользователя
         */
        const ERROR_INCORRECT_USER_ID = 100;

        /**
         * Ошибка создания пользователя
         */
        const ERROR_CREATE_USER = 200;

        /**
         * Возвращает обработанный callTrace текущего исключения
         *
         * @return array
         */
        public function generateCallTrace(): array
        {
            $trace = explode(PHP_EOL, $this->getTraceAsString());
            $trace = array_reverse($trace);
            array_shift($trace);
            $length = count($trace);
            $result = [];
            for ($i = 0; $i < $length; $i++) {
                $result[] = ($i + 1) . ')' . substr($trace[$i], strpos($trace[$i], ' '));
            }
            return $result;
        }

        /**
         * Отображение исключения пользователю
         *
         * @return void
         */
        public function showTrace(): void
        {
            echo '<div style="color: #b30000;background: #ffe2e2;padding: 10px;border: 1px solid #ffa0a0;margin: 10px;display: inline-block;">';
            echo '<span style="font-weight:bold; font-size: 1.1em;">'.$this->getMessage().'</span><br>';
            echo $this->getFile() . ': '.$this->getLine() . '<br>';
            $arTrace = $this->generateCallTrace();
            foreach($arTrace as $key => $value) {
                echo '<span>'.$value.'</span><br>';
            }
            echo '</div>';
        }
}
