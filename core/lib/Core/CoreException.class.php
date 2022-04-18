<?php

    namespace Core;

    class CoreException extends \Exception
    {

        /**
         * Возвращает обработанный callTrace текущего исключения
         *
         * @return array
         */
        function generateCallTrace(): array
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
}
